package sender

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"

	"github.com/argoos/agent/internal/collector"
)

// Sender abstracts how a collected metric is dispatched.
type Sender interface {
	Send(m *collector.Metric) error
}

// FileSender writes one JSON line per metric to a file.
// Use path "stdout" to write to standard output instead.
type FileSender struct {
	path string
}

func NewFileSender(path string) *FileSender {
	return &FileSender{path: path}
}

func (f *FileSender) Send(m *collector.Metric) error {
	data, err := json.Marshal(m)
	if err != nil {
		return fmt.Errorf("marshal metric: %w", err)
	}

	w, cleanup, err := f.writer()
	if err != nil {
		return err
	}
	defer cleanup()

	_, err = fmt.Fprintf(w, "%s\n", data)
	return err
}

func (f *FileSender) writer() (io.Writer, func(), error) {
	if f.path == "stdout" {
		return os.Stdout, func() {}, nil
	}

	file, err := os.OpenFile(f.path, os.O_CREATE|os.O_APPEND|os.O_WRONLY, 0644)
	if err != nil {
		return nil, nil, fmt.Errorf("open output file %q: %w", f.path, err)
	}
	return file, func() { file.Close() }, nil
}

// HTTPSender POSTs metrics to the central server API with exponential-backoff retry.
type HTTPSender struct {
	serverURL string
	apiKey    string
	retries   int
	client    *http.Client
}

func NewHTTPSender(serverURL, apiKey string, retries int) *HTTPSender {
	return &HTTPSender{
		serverURL: serverURL,
		apiKey:    apiKey,
		retries:   retries,
		client:    &http.Client{Timeout: 10 * time.Second},
	}
}

func (h *HTTPSender) Send(m *collector.Metric) error {
	body, err := json.Marshal(m)
	if err != nil {
		return fmt.Errorf("marshal metric: %w", err)
	}

	var lastErr error
	for attempt := range h.retries {
		if attempt > 0 {
			time.Sleep(time.Duration(1<<(attempt-1)) * time.Second)
		}
		if lastErr = h.post(body); lastErr == nil {
			return nil
		}
	}
	return fmt.Errorf("all %d attempts failed, last error: %w", h.retries, lastErr)
}

func (h *HTTPSender) post(body []byte) error {
	req, err := http.NewRequest(http.MethodPost, h.serverURL, bytes.NewReader(body))
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", h.apiKey)

	resp, err := h.client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return fmt.Errorf("server returned %s", resp.Status)
	}
	return nil
}

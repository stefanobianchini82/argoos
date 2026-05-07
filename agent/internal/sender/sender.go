package sender

import (
	"encoding/json"
	"fmt"
	"io"
	"os"

	"github.com/argoos/agent/internal/collector"
)

// Sender abstracts how a collected metric is dispatched.
// Phase 1: FileSender — writes JSONL to a local file (or stdout).
// Phase 2: HTTPSender — POSTs to the central server API.
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

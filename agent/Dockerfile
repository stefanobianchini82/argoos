FROM golang:1.22-alpine AS builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-s -w" -o argoos-agent ./cmd/agent

# Phase 2: when HTTPS calls to the server are needed, uncomment the next line
# to bundle CA certificates so TLS verification works from the scratch image.
# RUN apk add --no-cache ca-certificates

FROM scratch
# Phase 2: COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=builder /app/argoos-agent /argoos-agent
ENTRYPOINT ["/argoos-agent"]

#!/bin/bash
docker build -t argoos-agent:latest . && \
  docker run --rm -e OUTPUT_FILE=stdout -e \
  COLLECT_INTERVAL=10 argoos-agent:latest
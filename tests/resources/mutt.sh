#!/bin/bash

LOG_DIR="$1"
shift
echo "$@" >> "$LOG_DIR/mutt"
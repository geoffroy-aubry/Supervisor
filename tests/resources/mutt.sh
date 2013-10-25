#!/usr/bin/env bash

LOG_DIR="$1"
shift
exec >> "$LOG_DIR/mutt"

echo -n 'mutt'
for i in "$@"; do
    case "$i" in
        -a|-e|-s|--) printf -- " $i"   ;;
        *)           printf -- " '$i'" ;;
    esac
done
echo

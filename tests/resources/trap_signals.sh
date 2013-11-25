#!/usr/bin/env bash

function interrupt {
    echo ">> SIGTERM signal received <<"
    exit 143
}

trap 'interrupt' SIGTERM

echo 'waiting signal… '
for i in $(seq 1 10); do echo -n "$i "; sleep 1; done
echo 'end of script'

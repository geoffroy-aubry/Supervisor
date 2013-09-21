#!/bin/bash

##
# Retourne dans RETVAL la date avec les centièmes de secondes au format 'YYYY-MM-DD HH:MM:SS CS\c\s'.
#
function getDateWithCS {
    local date_format=%Y-%m-%d\ %H:%M:%S
    local now=$(date "+$date_format")
    local cs=$(date +%N | sed 's/^\([0-9]\{2\}\).*$/\1/')
    RETVAL="$now ${cs}cs"
}

##
# Lock script against parallel run.
# Example:
#     if ! getLock "$(basename $0)"; then
#         echo 'Another instance is running' >&2
#         exit
#     fi
#
# @param string $1 lock name
#
function getLock () {
    local name="$1"
    exec 9>"/tmp/$name.lock"
    if ! flock -x -n 9; then
        return 1
    fi
}

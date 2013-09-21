#!/bin/bash

##
# Archive les logs de supervision en tar gzip quotidiens.
# Chaque jour compris entre J-$MAX_DAYS inclus et J-$MIN_DAYS inclus
# donnera lieu à un tar gzip si et seulement si il y a des logs appartenant
# à l'intervalle et si le tar gzip n'a pas déjà été généré.
#
# bash /usr/local/lib/common.db/supervisor/supervisor_archiver.sh 30 5
# CRON: 40 0 * * * root /bin/bash /usr/local/lib/common.db/supervisor/supervisor_archiver.sh 30 5

MAX_DAYS="$1"
MIN_DAYS="$2"

# Includes :
. $(dirname $0)/conf/config.inc.sh

cd "$LOG_DIR"
for i in $(seq "$MAX_DAYS" -1 "$MIN_DAYS"); do
    date="$(date -d "$i days ago" +%Y-%m-%d)"
    files="$(find . -type f -daystart -mtime $i | grep -v '/supervisor\(\.\|_\)' | sort)"
    nb="$(echo "$files" | wc -l)"
    tar_file="supervisor_archive_$date.tar.gz"
    echo "> Date: $date"
    if [ ! -z "$files" ] && [ "$nb" -gt 0 ] && [ ! -e "$tar_file" ]; then
        echo "$LOG_DIR/$tar_file"
        echo "$files" | xargs tar -czvf "$tar_file" | xargs rm
    fi
done
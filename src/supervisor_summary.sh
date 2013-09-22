#!/bin/bash

# /bin/bash ~/eclipse-workspace-3.8/himedia-common/lib/common.db/supervisor/supervisor_summary.sh


#set -o nounset
#set -o pipefail

# Includes :
. $(dirname $0)/conf/supervisor.sh

actions="START;OK;WARNING;ERROR;INIT ERROR"
max_nb_days=7
mail_msg=''

declare -A stats
days="$(cat "$SUPERVISOR_INFO_LOG_FILE" | cut -d' ' -f1 | uniq | tail -n$max_nb_days)"
for day in $days; do
    echo "Day $day:"
    mail_msg="$mail_msg<h1>Day $day</h1>"
    scripts="$(cat "$SUPERVISOR_INFO_LOG_FILE" | grep "^$day " | grep ";START$" | cut -d';' -f3 | sort | uniq)"
    for script in $scripts; do
        echo "  Script $script:"
        mail_msg="$mail_msg<h2>Script $script</h2>"

        IFS=';'
        for action in $actions; do
            stats[$action]="$(cat "$SUPERVISOR_INFO_LOG_FILE" | grep "^$day " | grep ";$action$" | cut -d';' -f3 | grep "$script" | wc -l)"
        done

        echo -n '    '
        mail_msg="$mail_msg<table border=1 cellspacing=0><tr><th></th><th>START</th><th>OK</th><th>WARNING</th><th>ERROR</th><th>INIT ERROR</th></tr><tr><th>#</th>"
        for action in $actions; do
            echo -n "$action: ${stats[$action]}, "
            mail_msg="$mail_msg<td>${stats[$action]}</td>"
        done
        unset IFS
        echo
        mail_msg="$mail_msg</tr></table>"
    done
done

mail_subject="$SUPERVISOR_MAIL_SUBJECT_PREFIX > Summary"
echo "$mail_msg" | $SUPERVISOR_MAIL_MUTT_CMD -e "$SUPERVISOR_MAIL_MUTT_CFG" -s "$mail_subject" -- $SUPERVISOR_MAIL_TO $INSTIGATOR_EMAIL
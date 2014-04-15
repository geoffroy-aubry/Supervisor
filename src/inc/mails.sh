#!/usr/bin/env bash

##
# Copyright © 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
#
# This file is part of Supervisor.
#
# Supervisor is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Supervisor is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with Supervisor.  If not, see <http://www.gnu.org/licenses/>
#



function getMailSubject () {
    local status="$1"
    echo "$SUPERVISOR_MAIL_SUBJECT_PREFIX${SCRIPT_NAME##*/} > $status ($EXECUTION_ID)"
}

function getMailInstigator () {
    if [ -z "$MAIL_INSTIGATOR" ]; then
        echo -n '<i style="font-weight:normal">not specified</i>' | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g'
    else
        echo -n "$MAIL_INSTIGATOR"
    fi
}

function getElapsedTime () {
    local pattern
    [[ $SUPERVISOR_OUTPUT_FORMAT == 'csv' ]] && pattern='^.' || pattern='^'
    local t0="$(cat "$SCRIPT_INFO_LOG_FILE" | head -n1 | $SUPERVISOR_SED_BIN "s/$pattern//" | $SUPERVISOR_AWK_BIN '{print $1" "$2}')"
    local t1="$(cat "$SCRIPT_INFO_LOG_FILE" | tail -n1 | $SUPERVISOR_SED_BIN "s/$pattern//" | $SUPERVISOR_AWK_BIN '{print $1" "$2}')"
    local seconds=$(( $(date -d "$t1" +%s) - $(date -d "$t0" +%s) ))
    [[ $seconds -eq 0 ]] && (( seconds=seconds+1 ))

    local elapsed_time
    if [ $seconds -ge 3600 ]; then
        elapsed_time="$(date -u -d "@$seconds" +'%-Hh %Mmin %Ss')"
    elif [ $seconds -ge 60 ]; then
        elapsed_time="$(date -u -d "@$seconds" +'%-Mmin %Ss')"
    else
        elapsed_time="$(date -u -d "@$seconds" +'%-Ss')"
    fi
    echo -n "$elapsed_time"
}

function getCmd () {
    local parameters="$(echo "$SCRIPT_PARAMETERS" \
        | $SUPERVISOR_SED_BIN -r \
            -e "s/ +(--[a-z0-9_-]+(=('[^']+'|\"[^\"]+\"|[^'\" ]*))?)/\\\n\1\\\n/ig" \
            -e 's/\n(\n|$)/\1/g')"
    echo -n "$SCRIPT_NAME\n$parameters\n$EXECUTION_ID\n$SCRIPT_ERROR_LOG_FILE\n2>>$SCRIPT_ERROR_LOG_FILE" \
        | $SUPERVISOR_SED_BIN -e 's/\\n *\\n/\\n/g' -e 's/\\n/\\n    /g' -e 's|\(/\)|\\\1|g'
}

function compressAttachedFiles () {
    tail -n 50 "$SUPERVISOR_INFO_LOG_FILE" | gzip > "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
    touch "$SCRIPT_INFO_LOG_FILE"  && gzip -c "$SCRIPT_INFO_LOG_FILE"  > "$SCRIPT_INFO_LOG_FILE.gz"
    touch "$SCRIPT_ERROR_LOG_FILE" && gzip -c "$SCRIPT_ERROR_LOG_FILE" > "$SCRIPT_ERROR_LOG_FILE.gz"
}

function removeAttachedFiles () {
    rm -f "$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz"
    rm -f "$SCRIPT_INFO_LOG_FILE.gz"
    rm -f "$SCRIPT_ERROR_LOG_FILE.gz"
}

function rawSendMail () {
    local mail_subject="$1"
    local mail_msg="$2"
    local attachment="$3"

    if [ -z "${SUPERVISOR_MAIL_TO// /}" ]; then
        CUI_displayMsg error "No recipients were specified! See <b>SUPERVISOR_MAIL_TO</b> variable in config file."
    else
        [ ! -z "$attachment" ] && attachment="-a $attachment"
        echo "$mail_msg" | $SUPERVISOR_MAIL_MUTT_CMD \
            -e "$SUPERVISOR_MAIL_MUTT_CFG" -s "$mail_subject" $attachment -- $SUPERVISOR_MAIL_TO$MAIL_INSTIGATOR
    fi
}

function sendMail () {
    local mail_subject="$1"
    local mail_msg="$2"
    local add_attachment="$3"
    local attachment="$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz $SCRIPT_INFO_LOG_FILE.gz$SUPERVISOR_MAIL_ADD_ATTACHMENT"
    compressAttachedFiles
    rawSendMail "$mail_subject" "$mail_msg" "$attachment $add_attachment"
    removeAttachedFiles
}

function parentSendMailOnError () {
    local script_name="$(echo "$SCRIPT_NAME" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local log_dir="$(dirname "$SUPERVISOR_INFO_LOG_FILE" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local error_msg="$(cat "$SCRIPT_ERROR_LOG_FILE" | $SUPERVISOR_SED_BIN 's:\(/\|\\\):\\\1:g' | $SUPERVISOR_AWK_BIN 1 ORS='\\n')"
    local mail_msg=$($SUPERVISOR_SED_BIN \
        -e "s/{{elapsed_time}}/$(getElapsedTime)/g" \
        -e "s/{{script}}/$script_name/g" \
        -e "s/{{exec_id}}/$EXECUTION_ID/g" \
        -e "s/{{server}}/$(hostname)/g" \
        -e "s/{{instigator}}/$(getMailInstigator)/g" \
        -e "s/{{cmd}}/$(getCmd)/g" \
        -e "s/{{log_dir}}/$log_dir/g" \
        -e "s/{{supervisor_info_log_file}}/$(basename "$SUPERVISOR_INFO_LOG_FILE")/g" \
        -e "s/{{script_info_log_file}}/$(basename "$SCRIPT_INFO_LOG_FILE")/g" \
        -e "s/{{script_error_log_file}}/$(basename "$SCRIPT_ERROR_LOG_FILE")/g" \
        -e "s/{{error_msg}}/$error_msg/g" \
        "$EMAIL_TEMPLATES_DIR/error.html" \
    )
    sendMail "$(getMailSubject ERROR)" "$mail_msg" "$SCRIPT_ERROR_LOG_FILE.gz"
}

function parentSendMailOnWarning () {
    local warning_context="$(cat "$SCRIPT_INFO_LOG_FILE" | grep -B2 --color=never -n '\[WARNING\]')"
    echo "$warning_context" | head -n1 | grep -vq '^1\(-\|:\)' && warning_context=$'--\n'"$warning_context"
    echo "$warning_context" | tail -n1 | grep -vq "^$(wc -l "$SCRIPT_INFO_LOG_FILE" \
        | $SUPERVISOR_AWK_BIN '{print $1}')" && warning_context="$warning_context"$'\n--'
    local warning_html="$(echo "$warning_context" \
        | $SUPERVISOR_SED_BIN -r \
            -e 's|^[0-9]+-(.*)$|<span style="color:#9b8861">\1</span>|' \
            -e 's|^--$|<span style="color:#9b8861;font-style:italic">[…]</span>|' \
            -e 's|^[0-9]+:||' \
    )"

    local plural
    [ "${#WARNING_MSG[*]}" -gt 1 ] && plural='s' || plural=''
    local script_name="$(echo "$SCRIPT_NAME" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local log_dir="$(dirname "$SUPERVISOR_INFO_LOG_FILE" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local warning_msg="$(echo "$warning_html" | $SUPERVISOR_SED_BIN 's:\(/\|\\\):\\\1:g' | $SUPERVISOR_AWK_BIN 1 ORS='\\n')"
    local mail_msg=$($SUPERVISOR_SED_BIN \
        -e "s/{{nb_warnings}}/${#WARNING_MSG[*]}/g" \
        -e "s/{{warning_plural}}/$plural/g" \
        -e "s/{{elapsed_time}}/$(getElapsedTime)/g" \
        -e "s/{{script}}/$script_name/g" \
        -e "s/{{exec_id}}/$EXECUTION_ID/g" \
        -e "s/{{server}}/$(hostname)/g" \
        -e "s/{{instigator}}/$(getMailInstigator)/g" \
        -e "s/{{cmd}}/$(getCmd)/g" \
        -e "s/{{log_dir}}/$log_dir/g" \
        -e "s/{{supervisor_info_log_file}}/$(basename "$SUPERVISOR_INFO_LOG_FILE")/g" \
        -e "s/{{script_info_log_file}}/$(basename "$SCRIPT_INFO_LOG_FILE")/g" \
        -e "s/{{warning_msg}}/$warning_msg/g" \
        "$EMAIL_TEMPLATES_DIR/warning.html" \
    )

    sendMail "$(getMailSubject WARNING)" "$mail_msg" ''
}

function parentSendMailOnSuccess () {
    local script_name="$(echo "$SCRIPT_NAME" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local log_dir="$(dirname "$SUPERVISOR_INFO_LOG_FILE" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local mail_msg=$($SUPERVISOR_SED_BIN \
        -e "s/{{elapsed_time}}/$(getElapsedTime)/g" \
        -e "s/{{script}}/$script_name/g" \
        -e "s/{{exec_id}}/$EXECUTION_ID/g" \
        -e "s/{{server}}/$(hostname)/g" \
        -e "s/{{instigator}}/$(getMailInstigator)/g" \
        -e "s/{{cmd}}/$(getCmd)/g" \
        -e "s/{{log_dir}}/$log_dir/g" \
        -e "s/{{supervisor_info_log_file}}/$(basename "$SUPERVISOR_INFO_LOG_FILE")/g" \
        -e "s/{{script_info_log_file}}/$(basename "$SCRIPT_INFO_LOG_FILE")/g" \
        "$EMAIL_TEMPLATES_DIR/success.html" \
    )
    sendMail "$(getMailSubject SUCCESS)" "$mail_msg" ''
}

function parentSendMailOnStartup () {
    local script_name="$(echo "$SCRIPT_NAME" | $SUPERVISOR_SED_BIN 's|\(/\)|\\\1|g')"
    local mail_msg=$($SUPERVISOR_SED_BIN \
        -e "s/{{date}}/$(date +'%Y-%m-%d, %H:%M:%S')/g" \
        -e "s/{{script}}/$script_name/g" \
        -e "s/{{exec_id}}/$EXECUTION_ID/g" \
        -e "s/{{server}}/$(hostname)/g" \
        -e "s/{{instigator}}/$(getMailInstigator)/g" \
        -e "s/{{cmd}}/$(getCmd)/g" \
        "$EMAIL_TEMPLATES_DIR/starting.html" \
    )
    rawSendMail "$(getMailSubject STARTING)" "$mail_msg" ''
}

function sendMailOnError () {
    parentSendMailOnError
}

function sendMailOnWarning () {
    parentSendMailOnWarning
}

function sendMailOnSuccess () {
    parentSendMailOnSuccess
}

function sendMailOnStartup () {
    parentSendMailOnStartup
}

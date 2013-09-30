#!/bin/bash

function getMailSubject () {
    local status="$1"
    echo "$SUPERVISOR_MAIL_SUBJECT_PREFIX${SCRIPT_NAME##*/} > $status ($EXECUTION_ID)"
}

function getMailInstigator () {
    echo -n '<br />Instigator: '
    [ -z "$MAIL_INSTIGATOR" ] && echo -n '<i>not specified</i>' || echo -n "$MAIL_INSTIGATOR"
    echo '<br /><br />'
}

function getMailMsgCmdAndServer () {
    echo "Executed command: <pre>\$ $SCRIPT_NAME $SCRIPT_PARAMETERS $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE 2>>$SCRIPT_ERROR_LOG_FILE</pre><br />"
    echo "Server: $(hostname)<br />"
}

function getMailMsgInfoLogFiles () {
    echo "Supervisor log file: $(dirname $SUPERVISOR_INFO_LOG_FILE)/<b>$(basename $SUPERVISOR_INFO_LOG_FILE)</b><br />"
    echo "Execution log file: $(dirname $SCRIPT_INFO_LOG_FILE)/<b>$(basename $SCRIPT_INFO_LOG_FILE)</b><br />"
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

function sendMail () {
    local mail_subject="$1"
    local mail_msg="$2"
    local attachment="$3"

    [ ! -z "$attachment" ] && attachment="-a $attachment"
    echo "$mail_msg" | $SUPERVISOR_MAIL_MUTT_CMD \
        -e "$SUPERVISOR_MAIL_MUTT_CFG" -s "$mail_subject" $attachment -- $SUPERVISOR_MAIL_TO$MAIL_INSTIGATOR
}

function parentSendMailOnError () {
    local mail_msg="<h3 style=\"color: #FF0000\">Error in execution of script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.</h3>\
$(getMailInstigator)$(getMailMsgCmdAndServer)$(getMailMsgInfoLogFiles)\
Error log file: $(dirname $SCRIPT_ERROR_LOG_FILE)/<b>$(basename $SCRIPT_ERROR_LOG_FILE)</b><br /><br />\
<b style=\"color: #FF0000\">Error:</b><br /><pre>$(cat $SCRIPT_ERROR_LOG_FILE)</pre>"
    local attachment="$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz $SCRIPT_INFO_LOG_FILE.gz$SUPERVISOR_MAIL_ADD_ATTACHMENT"
    compressAttachedFiles
    sendMail "$(getMailSubject ERROR)" "$mail_msg" "$attachment $SCRIPT_ERROR_LOG_FILE.gz"
    removeAttachedFiles
}

function parentSendMailOnWarning () {
    local plural
    [ "${#WARNING_MSG[*]}" -gt 1 ] && plural='s' || plural=''
    local warning_html=''
    for msg in "${WARNING_MSG[@]}"; do
        warning_html="$warning_html<li>$msg</li>"
    done

    local mail_msg="<h3 style=\"color: #FF8C00\">Warning in execution of script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.</h3>\
<b style=\"color: #FF8C00\">Completed with ${#WARNING_MSG[*]} warning$plural.</b>\
$(getMailInstigator)$(getMailMsgCmdAndServer)$(getMailMsgInfoLogFiles)\
Error log file: <i>N.A.</i><br /><br />\
<p style=\"color: #FF8C00\"><b>Warning$plural</b> <i>(see attached files for more details)</i>:<ol>$warning_html</ol></p>"
    local attachment="$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz $SCRIPT_INFO_LOG_FILE.gz$SUPERVISOR_MAIL_ADD_ATTACHMENT"
    compressAttachedFiles
    sendMail "$(getMailSubject WARNING)" "$mail_msg" "$attachment"
    removeAttachedFiles
}

function parentSendMailOnSuccess () {
    local mail_msg="Successful execution of script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.\
$(getMailInstigator)$(getMailMsgCmdAndServer)$(getMailMsgInfoLogFiles)\
Error log file: <i>N.A.</i><br />No warnings."
    local attachment="$SUPERVISOR_INFO_LOG_FILE.$EXECUTION_ID.gz $SCRIPT_INFO_LOG_FILE.gz$SUPERVISOR_MAIL_ADD_ATTACHMENT"
    compressAttachedFiles
    sendMail "$(getMailSubject SUCCESS)" "$mail_msg" "$attachment"
    removeAttachedFiles
}

function parentSendMailOnInit () {
    local mail_msg="<h3>Starting script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.</h3>\
$(getMailInstigator)$(getMailMsgCmdAndServer)\
You will receive another email at the end of execution."
    sendMail "$(getMailSubject STARTING)" "$mail_msg" ''
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

function sendMailOnInit () {
    parentSendMailOnInit
}

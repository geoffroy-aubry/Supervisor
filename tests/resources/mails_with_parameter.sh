#!/usr/bin/env bash

function sendMailOnInit () {
    local mail_msg="<h3>Starting script '<b>$SCRIPT_NAME</b>' with id '<b>$EXECUTION_ID</b>'.</h3>\
$(getMailInstigator)\
You will receive another email at the end of execution."
    local param="${SUPERVISOR_PREFIX_EXT_PARAM}ETL"
    local mail_subject="${!param}"
    rawSendMail "$mail_subject" "$mail_msg" ''
}

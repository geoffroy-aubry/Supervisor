#!/bin/bash

# /bin/bash ~/eclipse-workspace-4.2/himedia-common/lib/common.db/supervisor/supervisor.sh deployment.php tests tests_gitexport v4.12.0
# /bin/bash /home/gaubry/supervisor/supervisor.sh --add deployment.php tests tests_gitexport v4.12.0
# find /home/gaubry/supervisor -type f -name "*sh" -exec chmod +x {} \;;  ~/deployment/supervisor.sh deployment.php project1 dev
# CRON : * * * * * /bin/bash /home/gaubry/supervisor/supervisor.sh --do-cron 1>/dev/null 2>>/home/gaubry/supervisor/logs/supervisor.error.log
# CRON : * * * * * /bin/bash /home/gaubry/supervisor/supervisor_monitoring.sh 1>/dev/null 2>>/home/gaubry/supervisor/logs/supervisor.error.log

# echo "mail_msg" | mutt -e "set content_type=text/html" -s "mail_subject" -- geoff.abury@gmail.com gaubry@hi-media.com

set -o nounset
set -o pipefail
shopt -s extglob

# Includes :
. $(dirname $0)/../conf/config.sh
. $INC_DIR/common.sh

# Duplication du flux d'erreur :
exec 2> >(tee -a $SUPERVISOR_ERROR_LOG_FILE >&2)

# Globales :
SCRIPT_NAME=''
SCRIPT_PARAMETERS=''
EXECUTION_ID=''
INSTIGATOR_EMAIL=''
SUPERVISOR_MAIL_ADD_ATTACHMENT=''

##
# Contrôleur.
# @uses $SCRIPT_NAME, $SCRIPT_PARAMETERS, $EXECUTION_ID, $INSTIGATOR_EMAIL
#
function main () {
    local action="$1"

    # Si le 1er paramètre précise l'instigateur de l'action :
    local instigator='--instigator-email='
    if [ "${action:0:${#instigator}}" = "$instigator" ]; then
        INSTIGATOR_EMAIL="${action:${#instigator}}"
        shift
        action="$1"
    fi

    if [ "$action" = "--get-logs" ]; then
        shift
        getLogs "$1" "$2"
    elif [ "$action" = "--do-cron" ]; then
        shift
        doCronJob
    elif [ "${action:0:1}" = "-" ]; then
        die "Unknown action '$action'!"
    else
        EXECUTION_ID="$(date +'%Y%m%d%H%M%S')_$(printf '%05d' $RANDOM)"
        SCRIPT_NAME="$1"; shift
        SCRIPT_PARAMETERS="$@"
        runDemand
    fi
}

function getLogs () {
    local script_name="$1"
    local id="$2"
    local info=''
    local error=''

    [ -s "$LOG_DIR/$script_name.$id.info.log" ] && info="$(cat "$LOG_DIR/$script_name.$id.info.log" | sed 's/</\&lt;/g' | sed 's/\&/\&amp;/g')"
    [ -s "$LOG_DIR/$script_name.$id.error.log" ] && error="$(cat "$LOG_DIR/$script_name.$id.error.log" | sed 's/</\&lt;/g' | sed 's/\&/\&amp;/g')"
    echo '<?xml version="1.0" encoding="UTF-8"?>'
    echo "<logs><info>$info</info><error>$error</error></logs>"
}

function doCronJob () {
    local n="$SUPERVISOR_MAX_DEMANDS_PER_MINUTE"
    local max=60

    let "i=$max/$n"
    while [ "$max" -gt "$i" ]; do
        sleep $i
        #echo "$(date +\%s), => apres sleep $i > $0" >> /home/gaubry/testsupervisorcron.txt
        /bin/bash $0 --run-next 1>/dev/null

        let "max-=$i"
        let "n--"
        let "i=$max/$n"
    done
}

function runDemand () {
    checkScriptCalled
    initScriptLogs
    initExecutionOfScript
    nb_warnings=0
    warning_messages=()
    executeScript
    displayResult
}

#[ $# -eq 0 ] && displayHelp
main "$@"

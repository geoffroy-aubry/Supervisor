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

    if [ "$action" = "--add" ]; then
        shift
        SCRIPT_NAME="$1"; shift
        SCRIPT_PARAMETERS="$@"
        addDemand
    elif [ "$action" = "--get-queue" ]; then
        shift
        getQueue
    elif [ "$action" = "--get-logs" ]; then
        shift
        getLogs "$1" "$2"
    elif [ "$action" = "--do-cron" ]; then
        shift
        doCronJob
    elif [ "$action" = "--run-next" ]; then
        shift
        getNextToLaunch
    elif [ "${action:0:1}" = "-" ]; then
        die "Unknown action '$action'!"
    else
        EXECUTION_ID="$(date +'%Y%m%d%H%M%S')_$(printf '%05d' $RANDOM)"
        SCRIPT_NAME="$1"; shift
        SCRIPT_PARAMETERS="$@"
        addAndRunDemand
    fi
}

function getQueue () {
    execQuery "SELECT supervisor_demand_status_id FROM SUPERVISOR_DEMAND WHERE supervisor_demand_status_id IN (1, 2) ORDER BY date_insert ASC"

    if [ ! -z "$csv_result" ]; then
        local max="$SUPERVISOR_MAX_SIMULTANEOUS_DEMANDS"
        local nb_in_waiting=0

        echo "$csv_result" | while read line; do
            status="${line:1:1}"
            if [ "$status" = "$SUPERVISOR_STATUS_IN_PROGRESS" ]; then
                [ "$max" -gt "0" ] && let "max--"
                time_remaining='-'
            elif [ "$status" = "$SUPERVISOR_STATUS_WAITING" ]; then
                if [ "$max" -gt "0" ]; then
                    let "max--"
                    s="$(date "+%-S")"
                    let "interval=60 / $SUPERVISOR_MAX_DEMANDS_PER_MINUTE"
                    let "time_remaining=$nb_in_waiting*$interval + $interval-($s%$interval)"
                    let "nb_in_waiting++"
                else
                    time_remaining='?'
                fi
            else
                time_remaining='?'
            fi
            echo "$time_remaining;$line"
        done
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

function checkDemand () {
    local mask
    [ "$SCRIPT_NAME" = "deployment.php" ] && mask="$(convertList2CSV $1 $2)"'%' || mask='%'

    local count=$(checkBeforeAdd "$SCRIPT_NAME" "$mask")
    if [ "$count" != "0" ]; then
        local err_msg
        [ "$SCRIPT_NAME" = "deployment.php" ] && err_msg=" with project='$1' and env='$2'" || err_msg=''
        die "Script '$SCRIPT_NAME'$err_msg already waiting or in progress!"
    fi
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

function getNbDemandInProgress () {
    execQuery "\
        SELECT COUNT(*) as NB_IN_PROGRESS \
        FROM SUPERVISOR_DEMAND \
        WHERE SUPERVISOR_DEMAND_STATUS_ID=$SUPERVISOR_STATUS_IN_PROGRESS"
}

function getNextToLaunch () {
    local nb=$(getNbDemandInProgress)
    if [ "$nb" -lt "$SUPERVISOR_MAX_SIMULTANEOUS_DEMANDS" ]; then
        local next_supervisor_id=$(execQuery "\
            SELECT SUPERVISOR_DEMAND_ID \
            FROM SUPERVISOR_DEMAND \
            WHERE SUPERVISOR_DEMAND_STATUS_ID=$SUPERVISOR_STATUS_WAITING \
            ORDER BY DATE_INSERT ASC \
            LIMIT 1")
        if [ ! -z "$next_supervisor_id" ]; then
            runDemandByID "$next_supervisor_id"
        fi
    fi
}

function addDemand () {
    checkScriptCalled
    checkDemand $SCRIPT_PARAMETERS
    local date="$(date +'%Y-%m-%d %H:%M:%S')"

    local parameters=$(convertList2CSV $SCRIPT_PARAMETERS)
    SUPERVISOR_ID=$(execQuery "\
        INSERT INTO SUPERVISOR_DEMAND ( \
            DATE_INSERT, SCRIPT_NAME, PARAMETERS, INSTIGATOR_EMAIL, SUPERVISOR_DEMAND_STATUS_ID \
        ) VALUES('$date', '$SCRIPT_NAME', '$parameters', '$INSTIGATOR_EMAIL', $SUPERVISOR_STATUS_WAITING); \
        SELECT LAST_INSERT_ID()")
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

function checkBeforeRun () {
    execQuery2CSV " \
        SELECT SCRIPT_NAME, PARAMETERS, INSTIGATOR_EMAIL \
        FROM SUPERVISOR_DEMAND \
        WHERE SUPERVISOR_DEMAND_STATUS_ID=$SUPERVISOR_STATUS_WAITING \
        AND SUPERVISOR_DEMAND_ID=$SUPERVISOR_ID \
        LIMIT 1"
}

function runDemandByID () {
    SUPERVISOR_ID="$1"
    local csv_result
    csv_result="$(checkBeforeRun)"
    [ -z "$csv_result" ] && die "Unknown supervisor_id '$SUPERVISOR_ID' or script not in waiting status!"

    local -a list=($(convertCSV2List "$csv_result"))
    SCRIPT_NAME="${list[0]}"
    SCRIPT_PARAMETERS="$(convertCSV2List ${list[1]})"
    INSTIGATOR_EMAIL="${list[2]}"
    EXECUTION_ID="$(date +'%Y%m%d%H%M%S')_$(printf '%05d' $RANDOM)"
    runDemand
}

function addAndRunDemand () {
    [ ! -z "$DB_SUPERVISOR_DB" ] && addDemand
    runDemand
}

#[ $# -eq 0 ] && displayHelp
main "$@"

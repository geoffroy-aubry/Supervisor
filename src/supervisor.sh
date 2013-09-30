#!/bin/bash

# /bin/bash ~/eclipse-workspace-4.2/himedia-common/lib/common.db/supervisor/supervisor.sh deployment.php tests tests_gitexport v4.12.0
# /bin/bash /home/gaubry/supervisor/supervisor.sh --add deployment.php tests tests_gitexport v4.12.0
# find /home/gaubry/supervisor -type f -name "*sh" -exec chmod +x {} \;;  ~/deployment/supervisor.sh deployment.php project1 dev
# CRON : * * * * * /bin/bash /home/gaubry/supervisor/supervisor.sh --do-cron 1>/dev/null 2>>/home/gaubry/supervisor/logs/supervisor.error.log
# CRON : * * * * * /bin/bash /home/gaubry/supervisor/supervisor_monitoring.sh 1>/dev/null 2>>/home/gaubry/supervisor/logs/supervisor.error.log

# echo "mail_msg" | mutt -e "set content_type=text/html" -s "mail_subject" -- geoff.abury@gmail.com gaubry@hi-media.com

# Supervisor exit codes:
#
# 0 iff no error
# 65 Missing script name!
# 66 Script '…' not found!
# 67 Script '…' is not executable!
# 68 Exit code changed from 0 to 68 due to errors.
# 69 Another instance of '…' is still running with supervisor!
# 70 Config file missing: '…'
# 71 Customized mails file not found: '…'
# 72 Invalid Mutt command: '…'
# Any code not null returned by user script


set -o nounset
set -o pipefail
shopt -s extglob

# Globales :
CONFIG_FILE="$(dirname $0)/../conf/supervisor.sh"
SCRIPT_NAME=''
SCRIPT_PARAMETERS=''
EXECUTION_ID="$(date +'%Y%m%d%H%M%S')_$(printf '%05d' $RANDOM)"
MAIL_INSTIGATOR=''
SUPERVISOR_MAIL_ADD_ATTACHMENT=''
CUSTOMIZED_MAILS=''
SUPERVISOR_PREFIX_EXT_PARAM='EXT_'
EXIT_CODE=0
WARNING_MSG=()
ACTION='supervise'
SUMMARIZE_NB_DAYS=0

function getOpts () {
    local j=0
    local long_option=''
    local parameter
    local name
    local value

    for i in "$@"; do
        # Converting short option into long option:
        if [ ! -z "$long_option" ]; then
            i="$long_option=$i"
            long_option=''
        fi

        case $i in
            -c) long_option="--conf" ;;
            -p) long_option="--param" ;;

            --conf=*)             CONFIG_FILE=${i#*=} ;;
            --customized-mails=*) CUSTOMIZED_MAILS=${i#*=} ;;
            --instigator-email=*) MAIL_INSTIGATOR=' '${i#*=} ;;

            --param=*)
                parameter=${i#*=}
                name=$SUPERVISOR_PREFIX_EXT_PARAM${parameter%=*}
                value=${parameter#*=}
                declare -rg -- $name="$value"    # readonly and global
                ;;

            --summarize=*)
                ACTION='summarize'
                SUMMARIZE_NB_DAYS=${i#*=}
                ;;

            *)
                case $j in
                    0) SCRIPT_NAME="$i" ;;
                    1) SCRIPT_PARAMETERS="$i" ;;
                    *) ;;
                esac
                j=$(($j + 1))
                ;;
        esac
    done
}

getOpts "$@"
[ -f "$CONFIG_FILE" ] || die "Config file missing: '<b>$CONFIG_FILE</b>'" 70

# Includes:
. $(dirname $0)/../conf/supervisor-dist.sh
. $CONFIG_FILE
. $INC_DIR/common.sh
loadCustomizedMails

# to normalize string representation:
SUPERVISOR_LOG_TABULATION=$(echo -e "$SUPERVISOR_LOG_TABULATION")

# Duplicate stderr:
exec 2> >(tee -a $SUPERVISOR_ERROR_LOG_FILE >&2)

[ -x "$(echo "$SUPERVISOR_MAIL_MUTT_CMD" | cut -d' ' -f1)" ] \
    || die "Invalid Mutt command: '<b>$SUPERVISOR_MAIL_MUTT_CMD</b>'" 72

doAction

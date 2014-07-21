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

##
# Supervisor exit codes:
#
# 0 iff no error
# 65 Missing script name!
# 66 Script '…' not found!
# 67 Script '…' is not executable!
# 68 Exit code changed from 0 to 68 due to errors.
# 69 Another instance of '…' is still running with supervisor!
# 71 Customized mails file not found: '…'
# 72 Invalid Mutt command: '…'
# Any code not null returned by user script
#

# Treat unset variables and parameters other than the special parameters ‘@’ or ‘*’ as an error
# when performing parameter expansion. An error message will be written to the standard error,
# and a non-interactive shell will exit.
set -o nounset

# The return value of a pipeline is the value of the last (rightmost) command to exit with a non-zero status,
# or zero if all commands in the pipeline exit successfully:
set -o pipefail

shopt -s extglob

# Globales :
CONFIG_FILE=''
SCRIPT_NAME=''
SCRIPT_PARAMETERS=''
EXECUTION_ID="$(date +'%Y%m%d%H%M%S')_$(printf '%05d' $RANDOM)"
EXTRA_PARAM_MODE='only-value'
MAIL_INSTIGATOR=''
SUPERVISOR_MAIL_ADD_ATTACHMENT=''
CUSTOMIZED_MAILS=''
SUPERVISOR_PREFIX_EXT_PARAM='EXT_'
EXIT_CODE=0
WARNING_MSG=()
ACTION='help'
SUMMARIZE_NB_DAYS=0
MIN_DAYS_BEFORE_ARCHIVING=1
SCRIPT_INFO_LOG_FILE=''
SCRIPT_ERROR_LOG_FILE=''
ADD_MAIL_TO=''

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
            -h) ACTION='help' ;;
            -p) long_option="--param" ;;

            --archive=*)
                ACTION='archive'
                MIN_DAYS_BEFORE_ARCHIVING=${i#*=}
                ;;

            --conf=*)             CONFIG_FILE=${i#*=} ;;
            --customized-mails=*) CUSTOMIZED_MAILS=${i#*=} ;;
            --exec-id=*)          EXECUTION_ID=${i#*=} ;;

            --extra-param-mode=*)
                value=${i#*=}
                case $value in
                    only-value|with-name|none) EXTRA_PARAM_MODE=$value ;;
                    *)                         EXTRA_PARAM_MODE='only-value' ;;
                esac
                ;;

            --help)               ACTION='help' ;;
            --mail-instigator=*)  MAIL_INSTIGATOR=' '${i#*=} ;;
            --mail-to=*)          ADD_MAIL_TO="$ADD_MAIL_TO ${i#*=}" ;;
            --monitor)            ACTION='monitor' ;;

            --param=*)
                parameter=${i#*=}
                name=$SUPERVISOR_PREFIX_EXT_PARAM${parameter%=*}
                name="${name// /_}"
                value=${parameter#*=}
                readonly $name="$value"    # readonly and global
                ;;

            --summarize=*)
                ACTION='summarize'
                SUMMARIZE_NB_DAYS=${i#*=}
                ;;

            *)
                case $j in
                    0) SCRIPT_NAME="$i"; ACTION='supervise' ;;
                    1) SCRIPT_PARAMETERS="$i" ;;
                    *) ;;
                esac
                j=$(($j + 1))
                ;;
        esac
    done
}

getOpts "$@"

# Includes:

# Give the full dirname of the script no matter where it is being called from, and resolve any symlink.
# Credit: Dave Dopson, http://stackoverflow.com/a/246128/1813519
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
    DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
    SOURCE="$(readlink "$SOURCE")"
    # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located:
    [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
done
. "$( cd -P "$( dirname "$SOURCE" )" && pwd )"/../conf/supervisor-dist.sh
[ -z "$CONFIG_FILE" ] && CONFIG_FILE=$CONF_DIR/supervisor.sh
[ -f "$CONFIG_FILE" ] && . $CONFIG_FILE
. $INC_DIR/common.sh
loadCustomizedMails

# to normalize string representation:
SUPERVISOR_LOG_TABULATION=$(echo -e "$SUPERVISOR_LOG_TABULATION")

# Concatenate recipients sources:
SUPERVISOR_MAIL_TO="$SUPERVISOR_MAIL_TO $ADD_MAIL_TO"

# Duplicate stderr:
exec 2> >(tee -a $SUPERVISOR_ERROR_LOG_FILE >&2)

[ -x "$(echo "$SUPERVISOR_MAIL_MUTT_BIN" | cut -d' ' -f1)" ] \
    || die "Invalid Mutt command: '<b>$SUPERVISOR_MAIL_MUTT_BIN</b>'" 72

# Handle interruption signals:
function interrupt {
    echo
    CUI_displayMsg error "$1 signal received! SIGTERM transmitted to supervised script…"
    kill -TERM $pid
    wait $pid
}
trap 'interrupt SIGHUP'  SIGHUP
trap 'interrupt SIGINT'  SIGINT
trap 'interrupt SIGQUIT' SIGQUIT
trap 'interrupt SIGTERM' SIGTERM

doAction

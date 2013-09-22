#!/bin/bash

# PATHS
ROOT_DIR="{root_dir}"
CONF_DIR=$ROOT_DIR/conf
SRC_DIR=$ROOT_DIR/src
INC_DIR=$SRC_DIR/inc
LOG_DIR="{log_dir}"

SUPERVISOR_ERROR_LOG_FILE=$LOG_DIR/supervisor.error.log
SUPERVISOR_INFO_LOG_FILE=$LOG_DIR/supervisor.info.log
SUPERVISOR_MONITORING_LOG_FILE=$LOG_DIR/supervisor.monitoring.log

# Lock script against parallel run (0|1)
SUPERVISOR_LOCK_SCRIPT=0

# Space separated list of emails :
SUPERVISOR_MAIL_TO="gaubry@hi-media.com geoff.abury@gmail.com"
SUPERVISOR_MAIL_SUBJECT_PREFIX='[DW] '
SUPERVISOR_MAIL_MUTT_CMDS="set content_type=text/html; \
my_hdr From: Data Warehouse Supervisor <gaubry@hi-media.com>; \
my_hdr Reply-To: Geoffroy Aubry <gaubry@hi-media.com>"
SUPERVISOR_MAIL_SEND_ON_INIT=0
SUPERVISOR_MAIL_SEND_ON_SUCCESS=0
SUPERVISOR_MAIL_SEND_ON_WARNING=1
SUPERVISOR_MAIL_SEND_ON_ERROR=1

# Error and warning detection
SUPERVISOR_LOG_TABULATION='┆   '
SUPERVISOR_PREFIX_MSG='[SUPERVISOR] '

##
# Colors and decorations types.
# MUST define following types:
#     error, error_detail, help, info, normal, ok, processing, warning.
#
# For each type, message will be displayed as follows (.header and .bold are optional):
#     '<type.header><type>message with <type.bold>bold section<type>.\033[0m'
#
# Color codes :
#   - http://www.tux-planet.fr/les-codes-de-couleurs-en-bash/
#   - http://confignewton.com/wp-content/uploads/2011/07/bash_color_codes.png
#
# @var associative array
# @see src/inc/coloredUI.sh for more details.
#
declare -A CUI_COLORS=(
    [error]='\033[1;31m'
    [error.bold]='\033[1;33m'
    [error.header]='\033[1m\033[4;33m/!\\\033[0;37m '
    [error_detail]='\033[1;31m'
    [help]='\033[0;36m'
    [help.bold]='\033[1;36m'
    [help.header]='\033[1;36m(i) '
    [info]='\033[1;37m'
    [normal]='\033[0;37m'
    [ok]='\033[1;32m'
    [processing]='\033[1;30m'
    [warning]='\033[0;33m'
    [warning.bold]='\033[1;33m'
    #[warning.header]='\033[1m\033[4;33m/!\\\033[0;37m '
)

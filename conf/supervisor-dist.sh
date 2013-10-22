#!/bin/bash

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



# PATHS
ROOT_DIR=$(cd "$(dirname "$(readlink -f "$BASH_SOURCE")")/.." && pwd)
CONF_DIR=$ROOT_DIR/conf
SRC_DIR=$ROOT_DIR/src
INC_DIR=$SRC_DIR/inc
LOG_DIR='/var/log/supervisor'

# All these files must be in $LOG_DIR/ directory:
SUPERVISOR_ERROR_LOG_FILE=$LOG_DIR/supervisor.error.log
SUPERVISOR_INFO_LOG_FILE=$LOG_DIR/supervisor.info.log
SUPERVISOR_MONITORING_LOG_FILE=$LOG_DIR/supervisor.monitoring.log
SUPERVISOR_ARCHIVING_PATTERN=$LOG_DIR/supervisor_archive_%s.tar.gz

# Lock script against parallel run (0|1)
SUPERVISOR_LOCK_SCRIPT=1

# 1 = Do nothing
# 2 = Do not add timestamp when inner timestamp already exists
# 3 = Remove inner timestamp
SUPERVISOR_ABOVE_SUPERVISOR_STRATEGY=1

# Space separated list of emails :
SUPERVISOR_MAIL_TO="supervisor@xyz.com"
SUPERVISOR_MAIL_SUBJECT_PREFIX='[supervisor] '
SUPERVISOR_MAIL_MUTT_CMD='/usr/bin/mutt'
SUPERVISOR_MAIL_MUTT_CFG="set content_type=text/html; \
my_hdr From: Supervisor <supervisor@xyz.com>; \
my_hdr Reply-To: Supervisor <supervisor@xyz.com>"
SUPERVISOR_MAIL_SEND_ON_STARTUP=1
SUPERVISOR_MAIL_SEND_ON_SUCCESS=1
SUPERVISOR_MAIL_SEND_ON_WARNING=1
SUPERVISOR_MAIL_SEND_ON_ERROR=1

SUPERVISOR_LOG_TABULATION='\033[0;30m┆\033[0m   '
SUPERVISOR_PREFIX_MSG='[SUPERVISOR] '
SUPERVISOR_WARNING_TAG='[WARNING]'
SUPERVISOR_DEBUG_TAG='[DEBUG]'
SUPERVISOR_MAILTO_TAG='[MAILTO]'
SUPERVISOR_MAIL_ATTACHMENT_TAG='[MAIL_ATTACHMENT]'

# Expected output format: {'txt', 'csv'}
SUPERVISOR_OUTPUT_FORMAT='txt'
# Number of the output CSV's field containing messages to watch (1-based):
SUPERVISOR_CSV_FIELD_TO_SCAN=2
# Set the CSV field separator (one character only):
SUPERVISOR_CSV_FIELD_SEPARATOR=','
# Set the CSV field enclosure (one character only):
SUPERVISOR_CSV_FIELD_ENCLOSURE='"'

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
)

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



# PATHS
ROOT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../vendor/geoffroy-aubry/supervisor" && pwd )"
CONF_DIR="$ROOT_DIR/conf"
SRC_DIR="$ROOT_DIR/src"
INC_DIR="$SRC_DIR/inc"
TMP_DIR='/tmp/padocc'
LOG_DIR='/var/log/padocc'
EMAIL_TEMPLATES_DIR="$SRC_DIR/templates"

# All four following files must be in $LOG_DIR/ directory.

# Critical supervisor error, must remain empty:
# (must be in $LOG_DIR/ directory)
SUPERVISOR_ERROR_LOG_FILE=$LOG_DIR/supervisor.error.log

# Supervisor's activity:
# (must be in $LOG_DIR/ directory)
SUPERVISOR_INFO_LOG_FILE=$LOG_DIR/supervisor.info.log

# Monitoring log file used to send critical email notifications
# using an exponential backoff algorithm in minute increments:
# (must be in $LOG_DIR/ directory)
SUPERVISOR_MONITORING_LOG_FILE=$LOG_DIR/supervisor.monitoring.log

# File's pattern to archive logs per day (%s will be replaced by a date in the +%Y-%m-%d format):
# (must be in $LOG_DIR/ directory)
SUPERVISOR_ARCHIVING_PATTERN=$LOG_DIR/supervisor_archive_%s.tar.gz

# Lock script against parallel run (0|1), only available on Debian/Ubuntu Linux, otherwise leave 0:
SUPERVISOR_LOCK_SCRIPT=0

# Handling cascaded supervisors:
#     1 = Do nothing
#     2 = Do not add timestamp when inner timestamp already exists
#     3 = Remove inner timestamp
SUPERVISOR_ABOVE_SUPERVISOR_STRATEGY=3

# Path to GNU sed command (typically, Debian/Ubuntu: sed, FreeBSD/OS X: gsed):
SUPERVISOR_SED_BIN=sed

# Path to GNU awk command (typically, Debian/Ubuntu: awk, FreeBSD/OS X: gawk):
SUPERVISOR_AWK_BIN=awk

# Path to GNU ls command (typically, Debian/Ubuntu: ls, FreeBSD/OS X: gls):
SUPERVISOR_LS_BIN=ls

# Path to GNU date command (typically, Debian/Ubuntu: date, FreeBSD/OS X: gdate):
SUPERVISOR_DATE_BIN=date

# Path to md5 command (typically, Debian/Ubuntu: md5sum, FreeBSD/OS X: 'md5 -r'):
SUPERVISOR_MD5_BIN=md5sum

# Path to GNU tar command (typically, Debian/Ubuntu: tar, FreeBSD/OS X: gtar):
SUPERVISOR_TAR_BIN=tar

# Space separated list of emails :
SUPERVISOR_MAIL_TO="archi@hi-media.com"

# Prefix of all supervisor's emails subject:
SUPERVISOR_MAIL_SUBJECT_PREFIX='[PADOCC Supervisor DEV] '

# Location of Mutt (typically, Debian/Ubuntu: '/usr/bin/mutt', FreeBSD/OS X: '/usr/local/bin/mutt'):
SUPERVISOR_MAIL_MUTT_BIN='/usr/bin/mutt'

# Extra parameters for Mutt (mutt -e …):
SUPERVISOR_MAIL_MUTT_CFG="set content_type=text/html; \
my_hdr From: PADOCC Supervisor DEV <archi@hi-media.com>; \
my_hdr Reply-To: archi <archi@hi-media.com>"

# Send an email at startup (0|1):
SUPERVISOR_MAIL_SEND_ON_STARTUP=1

# Send an email on success (0|1):
SUPERVISOR_MAIL_SEND_ON_SUCCESS=1

# Send an email on warning (0|1):
SUPERVISOR_MAIL_SEND_ON_WARNING=1

# Send an email on error (0|1):
SUPERVISOR_MAIL_SEND_ON_ERROR=1

# String used as tabulation by supervised scripts:
# (tags must be at the beginning of a line or only preceded by tabulations)
SUPERVISOR_LOG_TABULATION='\033[0;30m┆\033[0m   '

# Prefix of all messages from Supervisor in $SCRIPT_INFO_LOG_FILE:
SUPERVISOR_PREFIX_MSG='[SUPERVISOR] '

# Warning tag syntax:
SUPERVISOR_WARNING_TAG='[WARNING]'

# Debug tag syntax (displayed in stdout if and only if SUPERVISOR_SHOW_DEBUG_TRACES is 1):
SUPERVISOR_DEBUG_TAG='[DEBUG]'

# Display messages beginning with SUPERVISOR_DEBUG_TAG if and only if set to 1
# (but messages are still present in `<script>_<exec_id>.info.log`).
SUPERVISOR_SHOW_DEBUG_MSG=0

# Tag syntax to add a new recipient:
SUPERVISOR_MAILTO_TAG='[MAILTO]'

# Tag syntax to add a new attachment:
SUPERVISOR_MAIL_ATTACHMENT_TAG='[MAIL_ATTACHMENT]'

# Expected output format: {'txt', 'csv'}
SUPERVISOR_OUTPUT_FORMAT='txt'

# Number of the output CSV's field containing messages to watch (1-based):
SUPERVISOR_CSV_FIELD_TO_SCAN=2

# Set the CSV field separator (one character only):
SUPERVISOR_CSV_FIELD_SEPARATOR=','

# Set the CSV field enclosure (one character only):
SUPERVISOR_CSV_FIELD_ENCLOSURE='"'

# Path to 'csv-parser.awk' CSV parser
# @see https://github.com/geoffroy-aubry/awk-csv-parser for more details.
SUPERVISOR_CSV_PARSER="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )/vendor/bin/csv-parser.awk"

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

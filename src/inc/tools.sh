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



##
# Retourne dans RETVAL la date avec les centièmes de secondes au format 'YYYY-MM-DD HH:MM:SS CS\c\s'.
#
function getDateWithCS {
    local date_format=%Y-%m-%d\ %H:%M:%S
    local now=$(date "+$date_format")
    local cs=$(date +%N | sed 's/^\([0-9]\{2\}\).*$/\1/')
    RETVAL="$now ${cs}cs"
}

##
# Lock script against parallel run.
# Example:
#     if ! getLock "$(basename $0)"; then
#         echo 'Another instance is running' >&2
#         exit
#     fi
#
# @param string $1 lock name
#
function getLock () {
    local name="$1"
    exec 9>"/tmp/$name.lock"
    if ! flock -x -n 9; then
        return 1
    fi
}

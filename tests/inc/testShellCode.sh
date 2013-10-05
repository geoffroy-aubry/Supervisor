#!/bin/bash

##
# Execute code calling functions of common.inc.sh after loading Shell config files.
# e.g.: /bin/bash testShellCode.sh 'process_options x -aV; isset_option a; echo $?'
#
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



# Parameters:
sCmds="$1"; shift

# Includes:
. $(dirname $0)/../../conf/supervisor-dist.sh
. $INC_DIR/common.sh

# Execution:
rFile="/tmp/file.$$.$RANDOM"
echo "$sCmds" > $rFile
. $rFile
rm -f $rFile

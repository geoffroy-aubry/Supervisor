#!/bin/bash

##
# Execute code calling functions of common.inc.sh after loading Shell config files.
# e.g.: /bin/bash testShellCode.sh 'process_options x -aV; isset_option a; echo $?'
#
# @author Geoffroy Aubry <geoffroy.aubry@free.fr>
# @author Laurent Toussaint <lt.laurent.toussaint@gmail.com>
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

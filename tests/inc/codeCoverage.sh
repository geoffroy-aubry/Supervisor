#!/bin/bash

##
# Usage: tests/inc/codeCoverage.sh <src_dir> <tests_dir>
# @copyright 2012-2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
# @copyright 2012 Laurent Toussaint <lt.laurent.toussaint@gmail.com>
# @license http://www.apache.org/licenses/LICENSE-2.0
#


set -o nounset
set -o pipefail

src_dir="$1"
tests_dir="$2"
rStats="/tmp/file.$$.$RANDOM"
rCovers="/tmp/file.$$.$RANDOM"

# Compute stats about Bash functions in a CSV file plus an extra line for total lines of code.
# CSV format: path:function_name:start_line:end_line:nb_of_line_of_code
# Use % instead of \000 in tr command, octal value doesn't work with sed command in mac os x
grep -E '^\s*function\s+([a-z0-9_-]+)\b|^\s*\}\s*$' --ignore-case --only-matching --line-number -r \
    --include=*.sh --exclude-dir="$tests_dir" --with-filename "$src_dir" \
    | sed -r 's#^./##' \
    | tr '\n' '%' \
    | sed -r 's/:([0-9]+):function[ ]+([a-zA-Z0-9_-]+)%[^:]+:([0-9]+):\}%/\t\2\t\1\t\3\'$'\n/g' \
    | sort \
    | awk -F'\t' 'BEGIN {sum=0} {diff=$4-$3; sum += diff; print $0"\t"diff} END {print sum}' \
    > $rStats

# Find all @shcovers annotations in test code and store them in a file.
# Format of annotation: @shcover path::function
# Example: @shcovers inc/common.inc.sh::assert_git_configured
grep -E '^\s*\*\s*@shcovers\s+.+::.' --ignore-case -r --no-filename --include=*Test.php --exclude-dir=tests/lib "$tests_dir" \
    | tr -d '\r' \
    | sed 's/^.*@shcovers[ ]*//' \
    | sed 's/::/\t/' \
    | sort | uniq \
    > $rCovers

iSum="$(grep -f $rCovers $rStats | awk -F'\t' 'BEGIN {sum=0} {sum+=$5} END {print sum}')"
iTotal="$(tail -n 1 $rStats)"
(( p=iSum*1000/iTotal ))
fPercent="${p:0:$((${#p}-1))}.${p:$((${#p}-1))}"

# Example: "Estimated Bash code coverage: .4% (6 of 1334 lines)."
echo -e "\n\033[1;33mEstimated Bash code coverage: \033[1;37m$fPercent%\033[0;33m ($iSum of $iTotal lines)."

echo -e "\n\033[1;32mBash covered functions:"
( echo -e 'Script\tFunction\tStart line\tEnd line\tLOC'; grep -f $rCovers $rStats ) \
    | column -t -s $'\t' \
    | awk '{if (NR == 1) print "\033[1;37m" $0 "\033[0m"; else print $0}' \
    | sed -r 's/^/    /'

echo -e "\n\033[1;31mBash uncovered functions:"
( echo -e 'Script\tFunction\tStart line\tEnd line\tLOC'; grep -f $rCovers -v $rStats | head -n-1 ) \
    | column -t -s $'\t' \
    | awk '{if (NR == 1) print "\033[1;37m" $0 "\033[0m"; else print $0}' \
    | sed -r 's/^/    /'

rm -f "$rStats"
rm -f "$rCovers"

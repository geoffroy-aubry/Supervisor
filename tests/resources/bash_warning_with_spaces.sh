#!/usr/bin/env bash

echo Title:
echo -e '    level 1'
echo -e '    [WARNING]message 1'
echo -e '        \033[1;33myellow level 2'
echo -e '          \033[1;31m  [WARNING]colored\033[0m message 2'
echo '  END with spaces  '
echo '  [WARNING]   message 3'

#!/bin/bash

echo '[MAILTO]test1@xyz.com'
echo -e '\033[0;30m┆\033[0m   \033[0;30m┆\033[0m     \033[0;30m  [MAILTO]  test2@xyz.com  test3@xyz.com \033[0m'
echo "It's an error!" >&2

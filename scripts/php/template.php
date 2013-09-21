<?php

echo 'Parameters: ' . implode('|', $argv) . "\n";
echo 'WARNING alert!' . "\n";
echo '...' . "\n";
throw new Exception('arghh');
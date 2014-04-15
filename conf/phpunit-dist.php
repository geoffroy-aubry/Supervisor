<?php

define('ROOT_DIR',      realpath(__DIR__ . '/..'));
define('SRC_DIR',       ROOT_DIR . '/src');
define('TESTS_DIR',     ROOT_DIR . '/tests');
define('RESOURCES_DIR', TESTS_DIR . '/resources');

// Typically, Debian/Ubuntu: '/bin/bash', FreeBSD/OS X: '/usr/local/bin/bash'
define('BASH_BIN', '/bin/bash');

// Typically, Debian/Ubuntu: 'sed', FreeBSD/OS X: 'gsed'
define('SED_BIN', 'sed');

// Typically, Debian/Ubuntu: 'ls', FreeBSD/OS X: 'gls'
define('LS_BIN', 'ls');

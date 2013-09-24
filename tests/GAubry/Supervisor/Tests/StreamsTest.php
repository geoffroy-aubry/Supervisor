<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class StreamsTest extends SupervisorTestCase
{
    /**
     */
    public function testWithoutScript ()
    {
        $aResult = $this->execSupervisor('');
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("NO SCRIPT;START\nNO SCRIPT;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\\ Missing script name!\n", $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testScriptNotFound ()
    {
        $sScriptPath = RESOURCES_DIR . '/not_exists';
        $aResult = $this->execSupervisor($sScriptPath);
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\ Script '$sScriptPath' not found!\n", $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testNotExecutableScript ()
    {
        $sScriptPath = RESOURCES_DIR . '/not_executable';
        $aResult = $this->execSupervisor($sScriptPath);
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\ Script '$sScriptPath' is not executable!\n", $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testEmptyExecutableScript ()
    {
        $sScriptName = 'empty_executable';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
OK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id']), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testBashColoredSimpleScript ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
%2\$sOK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], preg_replace(array('/^.*;\[SUPERVISOR\] .*$\n/m', '/^([0-9: -]{22}cs);/m'), array('', '$1, '), file_get_contents($aResult['script_info_path']))), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START
Title:
┆   level 1
┆   ┆   yellow level 2
  END with spaces" . '  ' . "
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testBashExit ()
    {
        $sScriptName = 'bash_exit_not_null.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ [SUPERVISOR] Exit code not null: 42
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("[SUPERVISOR] Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testBashStdErr ()
    {
        $sScriptName = 'bash_std_err.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ It's an error!
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("It's an error!\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testBashStdErrAndExit ()
    {
        $sScriptName = 'bash_std_err_and_exit_not_null.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ It's an error!
[SUPERVISOR] Exit code not null: 42
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("It's an error!\n[SUPERVISOR] Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testPhpExit ()
    {
        $sScriptName = 'php_exit_not_null.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ [SUPERVISOR] Exit code not null: 42
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("[SUPERVISOR] Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testPhpStdErr ()
    {
        $sScriptName = 'php_std_err.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ It's an error!
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("It's an error!\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testPhpException ()
    {
        $sScriptName = 'php_exception.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ PHP Fatal error:  Uncaught exception 'RuntimeException' with message 'It's an error!
' in $sScriptPath:4
Stack trace:
#0 {main}
  thrown in $sScriptPath on line 4
[SUPERVISOR] Exit code not null: 255
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("PHP Fatal error:  Uncaught exception 'RuntimeException' with message 'It's an error!
' in $sScriptPath:4
Stack trace:
#0 {main}
  thrown in $sScriptPath on line 4
[SUPERVISOR] Exit code not null: 255\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testPhpNotice ()
    {
        $sScriptName = 'php_notice.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ PHP Notice:  Undefined variable: b in $sScriptPath on line 4
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("PHP Notice:  Undefined variable: b in $sScriptPath on line 4
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testPhpFatalError ()
    {
        $sScriptName = 'php_fatal_error.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ PHP Fatal error:  Call to undefined function undefined_fct() in $sScriptPath on line 4
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
[SUPERVISOR] Exit code not null: 255
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("PHP Fatal error:  Call to undefined function undefined_fct() in $sScriptPath on line 4
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
[SUPERVISOR] Exit code not null: 255\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }
}

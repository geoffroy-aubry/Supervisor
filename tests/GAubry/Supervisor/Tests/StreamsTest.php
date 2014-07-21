<?php

/**
 * Copyright © 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 *
 * This file is part of Supervisor.
 *
 * Supervisor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Supervisor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Supervisor.  If not, see <http://www.gnu.org/licenses/>
 * Copyright (c) 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
 *
 * @copyright 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * @license http://www.gnu.org/licenses/lgpl.html
 */



namespace GAubry\Supervisor\Tests;

class StreamsTest extends SupervisorTestCase
{
    private function filterScriptInfo ($sScriptInfoPath)
    {
        return preg_replace(
            array(
                '/^.*;(┆   )*\s*\[(SUPERVISOR|DEBUG|MAILTO|MAIL_ATTACHMENT)\].*$\n/m',
                '/^([0-9: -]{22}cs);/m'
            ),
            array('', '$1, '),
            file_get_contents($sScriptInfoPath)
        );
    }

    private function getExpectedSupervisorStdOut ($sScriptPath, $sExecId, $sScriptInfoFiltered)
    {
        $sScriptName = strrchr($sScriptPath, '/');
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
%2\$sOK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: {$this->sTmpDir}$sScriptName.%1\$s.info.log";
        return sprintf($sExpectedStdOut, $sExecId, $sScriptInfoFiltered);
    }

    /**
     * @shcovers inc/common.sh::checkScriptCalled
     * @shcovers inc/common.sh::die
     * @shcovers inc/common.sh::initExecutionOfScript
     * @shcovers inc/common.sh::initScriptLogs
     * @shcovers inc/tools.sh::getDateWithCS
     */
    public function testWithoutScript ()
    {
        $aResult = $this->execSupervisor('');
        $sExpectedStdOut = "\nDescription
┆   Oversee script execution, recording stdout, stderr and exit code with timestamping,
┆   and ensure email notifications will be sent (on start, success, warning or error).";
        $this->assertContains($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals('', $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::checkScriptCalled
     * @shcovers inc/common.sh::die
     * @shcovers inc/common.sh::initExecutionOfScript
     * @shcovers inc/common.sh::initScriptLogs
     * @shcovers inc/tools.sh::getDateWithCS
     */
    public function testScriptNotFound ()
    {
        $sScriptPath = RESOURCES_DIR . '/not_exists';
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "\n(i) Starting script '$sScriptPath' with id '" . $aResult['exec_id'] . "'";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(66, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\ Script '$sScriptPath' not found!\n", $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::checkScriptCalled
     * @shcovers inc/common.sh::die
     * @shcovers inc/common.sh::initExecutionOfScript
     * @shcovers inc/common.sh::initScriptLogs
     * @shcovers inc/tools.sh::getDateWithCS
     */
    public function testNotExecutableScript ()
    {
        $sScriptPath = RESOURCES_DIR . '/not_executable';
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = "\n(i) Starting script '$sScriptPath' with id '" . $aResult['exec_id'] . "'";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(67, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\ Script '$sScriptPath' is not executable!\n", $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testEmptyExecutableScript ()
    {
        $sScriptName = 'empty_executable';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], '');
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     * @shcovers inc/common.sh::doAction
     */
    public function testBashColoredSimpleScript ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
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
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
/!\ [SUPERVISOR] Exit code not null: 42";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(42, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("[SUPERVISOR] Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
[SUPERVISOR] Exit code changed from 0 to 68 due to errors.";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(68, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("It's an error!\n[SUPERVISOR] Exit code changed from 0 to 68 due to errors.\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
[SUPERVISOR] Exit code not null: 42";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(42, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("It's an error!\n[SUPERVISOR] Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
/!\ [SUPERVISOR] Exit code not null: 42";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(42, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("[SUPERVISOR] Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
[SUPERVISOR] Exit code changed from 0 to 68 due to errors.";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(68, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("It's an error!\n[SUPERVISOR] Exit code changed from 0 to 68 due to errors.\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
' in $sScriptPath:7
Stack trace:
#0 {main}
  thrown in $sScriptPath on line 7
[SUPERVISOR] Exit code not null: 255";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(255, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("PHP Fatal error:  Uncaught exception 'RuntimeException' with message 'It's an error!
' in $sScriptPath:7
Stack trace:
#0 {main}
  thrown in $sScriptPath on line 7
[SUPERVISOR] Exit code not null: 255\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
/!\ PHP Notice:  Undefined variable: b in $sScriptPath on line 7
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
[SUPERVISOR] Exit code changed from 0 to 68 due to errors.";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(68, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("PHP Notice:  Undefined variable: b in $sScriptPath on line 7
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
[SUPERVISOR] Exit code changed from 0 to 68 due to errors.\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
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
/!\ PHP Fatal error:  Call to undefined function undefined_fct() in $sScriptPath on line 7
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
[SUPERVISOR] Exit code not null: 255";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(255, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("PHP Fatal error:  Call to undefined function undefined_fct() in $sScriptPath on line 7
PHP Stack trace:
PHP   1. {main}() $sScriptPath:0
[SUPERVISOR] Exit code not null: 255\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     * @shcovers inc/tools.sh::getLock
     * @group locks
     */
    public function testBlockingLocks ()
    {
        $sScriptName = 'bash_colored_simple_sleep.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $sCmdA = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_lock-A.sh' $sScriptPath";
        $sCmdB = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_lock-B.sh' $sScriptPath";
        $sCmd = "($sCmdA) > /dev/null 2>&1 & sleep .2 && ($sCmdB)";

        $aResult = $this->execSupervisor($sCmd, array('conf_lock-A.sh', 'conf_lock-B.sh'));
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ [SUPERVISOR] Another instance of '$sScriptName' is still running with supervisor!";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals(69, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("[SUPERVISOR] Another instance of '<b>$sScriptName</b>' is still running with supervisor!\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     * @shcovers inc/tools.sh::getLock
     */
    public function testWithoutLocks ()
    {
        $sScriptName = 'bash_colored_simple_sleep.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $sCmdA = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_without-lock-A.sh' $sScriptPath";
        $sCmdB = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_without-lock-B.sh' $sScriptPath";
        $sCmd = "($sCmdA) > /dev/null 2>&1 & sleep .2 && ($sCmdB)";

        $aResult = $this->execSupervisor($sCmd, array('conf_without-lock-A.sh', 'conf_without-lock-B.sh'));
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
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
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     * @shcovers inc/tools.sh::getLock
     * @group locks
     */
    public function testNonBlockingLocks ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $sCmdA = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_lock-A.sh' bash_colored_simple_sleep.sh";
        $sCmdB = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_lock-B.sh' $sScriptPath";
        $sCmd = "($sCmdA) > /dev/null 2>&1 & sleep .2 && ($sCmdB)";

        $aResult = $this->execSupervisor($sCmd, array('conf_lock-A.sh', 'conf_lock-B.sh'));
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
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
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testAdditionalParametersWithDefaultBehavior ()
    {
        $sScriptName = 'bash_additional_parameters.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("$sScriptPath 'one two'");
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Parameter: 'one'
Parameter: 'two'
Parameter: '" . $aResult['exec_id'] . "'
Parameter: '" . $aResult['script_err_path'] . "'
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testAdditionalParametersWithModeOnlyValue ()
    {
        $sScriptName = 'bash_additional_parameters.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("$sScriptPath --extra-param-mode=only-value 'one two'");
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Parameter: 'one'
Parameter: 'two'
Parameter: '" . $aResult['exec_id'] . "'
Parameter: '" . $aResult['script_err_path'] . "'
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testAdditionalParametersWithInvalidMode ()
    {
        $sScriptName = 'bash_additional_parameters.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("$sScriptPath --extra-param-mode=NOT-EXISTS 'one two'");
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Parameter: 'one'
Parameter: 'two'
Parameter: '" . $aResult['exec_id'] . "'
Parameter: '" . $aResult['script_err_path'] . "'
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testAdditionalParametersWithModeNone ()
    {
        $sScriptName = 'bash_additional_parameters.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("$sScriptPath --extra-param-mode=none 'one two'");
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Parameter: 'one'
Parameter: 'two'
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testAdditionalParametersWithModeWithName ()
    {
        $sScriptName = 'bash_additional_parameters.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("$sScriptPath --extra-param-mode=with-name 'one two'");
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Parameter: 'one'
Parameter: 'two'
Parameter: '--exec-id=" . $aResult['exec_id'] . "'
Parameter: '--error-log-file=" . $aResult['script_err_path'] . "'
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testDebugMessages ()
    {
        $sScriptName = 'bash_debug.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Title:
┆   level 1
┆   [DEBUG]debug message 1
┆   ┆   yellow level 2
┆   ┆       [DEBUG]debug colored message 2
  END with spaces" . '  ' . "
  [DEBUG]   debug message 3
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testWarningMessages ()
    {
        $sScriptName = 'bash_warning.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
%2\$s3 WARNINGS

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: {$this->sTmpDir}/$sScriptName.%1\$s.info.log";
        $sExpectedStdOut = sprintf($sExpectedStdOut, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Title:
┆   level 1
┆   [WARNING] utf-8 aïe
┆   ┆   yellow level 2
┆   ┆       [WARNING]colored message 2…
  END with spaces" . '  ' . "
  [WARNING]   message 3
[SUPERVISOR] WARNING (#3)\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;WARNING\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testWarningMessagesWithTrueTab ()
    {
        $sScriptName = 'bash_warning_with_tab.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_with_tabs.sh');
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
%2\$s3 WARNINGS

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: {$this->sTmpDir}/$sScriptName.%1\$s.info.log";
        $sExpectedStdOut = sprintf($sExpectedStdOut, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Title:
\tlevel 1
\t[WARNING]message 1
\t\tyellow level 2
\t\t    [WARNING]colored message 2
  END with spaces" . '  ' . "
  [WARNING]   message 3
[SUPERVISOR] WARNING (#3)\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;WARNING\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testWarningMessagesWithSpacesInsteadOfTab ()
    {
        $sScriptName = 'bash_warning_with_spaces.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_with_spaces.sh');
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
%2\$s3 WARNINGS

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: {$this->sTmpDir}/$sScriptName.%1\$s.info.log";
        $sExpectedStdOut = sprintf($sExpectedStdOut, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
Title:
    level 1
    [WARNING]message 1
        yellow level 2
            [WARNING]colored message 2
  END with spaces" . '  ' . "
  [WARNING]   message 3
[SUPERVISOR] WARNING (#3)\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;WARNING\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testBashWithMailTags ()
    {
        $sScriptName = 'bash_mail_to_tags.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
[MAILTO]test1@xyz.com
Title:
┆   level 1
┆   ┆       [MAILTO]  test2@xyz.com  test3@xyz.com" . ' ' . "
END
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     * @shcovers inc/common.sh::displayResult
     * @shcovers inc/common.sh::displayScriptMsg
     */
    public function testBashWithMailAttachmentTags ()
    {
        $sScriptName = 'bash_mail_attachment_tags.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
[MAIL_ATTACHMENT]/path/to/file1
Title:
┆   level 1
┆   ┆       [MAIL_ATTACHMENT]  /path/to/file2  /path/to/file3" . ' ' . "
END
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     */
    public function testInnerSupervisorWithStrategy1 ()
    {
        $sScriptName = 'bash_inner_supervisor.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath);
        $sSupervisorInfoFiltered = preg_replace('/^[0-9: -]{22}cs, /m', '…, ', $aResult['std_out']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '" . $aResult['exec_id'] . "'
…," . ' ' . "
…, (i) Starting script 'tests/resources/bash_colored_simple.sh' with id '20131002075605_28465'
…, 2013-10-02 07:56:05 69cs, Title:
…, 2013-10-02 07:56:05 70cs, ┆   level 1
…, 2013-10-02 07:56:05 71cs, ┆   ┆   yellow level 2
…, 2013-10-02 07:56:05 73cs,   END with spaces" . '  ' . "
…, OK
…," . ' ' . "
…, (i) Supervisor log file: /var/log/supervisor/supervisor.info.log
…, (i) Execution log file: /var/log/supervisor/bash_colored_simple.sh.20131002075605_28465.info.log
…," . ' ' . "
OK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/bash_inner_supervisor.sh." . $aResult['exec_id'] . ".info.log";
        $this->assertEquals($sExpectedStdOut, $sSupervisorInfoFiltered);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START

(i) Starting script 'tests/resources/bash_colored_simple.sh' with id '20131002075605_28465'
2013-10-02 07:56:05 69cs, Title:
2013-10-02 07:56:05 70cs, ┆   level 1
2013-10-02 07:56:05 71cs, ┆   ┆   yellow level 2
2013-10-02 07:56:05 73cs,   END with spaces" . '  ' . "
OK

(i) Supervisor log file: /var/log/supervisor/supervisor.info.log
(i) Execution log file: /var/log/supervisor/bash_colored_simple.sh.20131002075605_28465.info.log

[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     */
    public function testInnerSupervisorWithStrategy2 ()
    {
        $sScriptName = 'bash_inner_supervisor.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_strategy2.sh');
        $sSupervisorInfoFiltered = preg_replace('/^(?!2013-10-02 07:56:05)[0-9: -]{22}cs, /m', '…, ', $aResult['std_out']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '" . $aResult['exec_id'] . "'
…," . ' ' . "
…, (i) Starting script 'tests/resources/bash_colored_simple.sh' with id '20131002075605_28465'
2013-10-02 07:56:05 69cs, Title:
2013-10-02 07:56:05 70cs, ┆   level 1
2013-10-02 07:56:05 71cs, ┆   ┆   yellow level 2
2013-10-02 07:56:05 73cs,   END with spaces" . '  ' . "
…, OK
…," . ' ' . "
…, (i) Supervisor log file: /var/log/supervisor/supervisor.info.log
…, (i) Execution log file: /var/log/supervisor/bash_colored_simple.sh.20131002075605_28465.info.log
…," . ' ' . "
OK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/bash_inner_supervisor.sh." . $aResult['exec_id'] . ".info.log";
        $this->assertEquals($sExpectedStdOut, $sSupervisorInfoFiltered);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START

(i) Starting script 'tests/resources/bash_colored_simple.sh' with id '20131002075605_28465'
Title:
┆   level 1
┆   ┆   yellow level 2
  END with spaces" . '  ' . "
OK

(i) Supervisor log file: /var/log/supervisor/supervisor.info.log
(i) Execution log file: /var/log/supervisor/bash_colored_simple.sh.20131002075605_28465.info.log

[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::executeScript
     */
    public function testInnerSupervisorWithStrategy3 ()
    {
        $sScriptName = 'bash_inner_supervisor.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_strategy3.sh');
        $sSupervisorInfoFiltered = preg_replace('/^(?!2013-10-02 07:56:05)[0-9: -]{22}cs, /m', '…, ', $aResult['std_out']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '" . $aResult['exec_id'] . "'
…," . ' ' . "
…, (i) Starting script 'tests/resources/bash_colored_simple.sh' with id '20131002075605_28465'
…, Title:
…, ┆   level 1
…, ┆   ┆   yellow level 2
…,   END with spaces" . '  ' . "
…, OK
…," . ' ' . "
…, (i) Supervisor log file: /var/log/supervisor/supervisor.info.log
…, (i) Execution log file: /var/log/supervisor/bash_colored_simple.sh.20131002075605_28465.info.log
…," . ' ' . "
OK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/bash_inner_supervisor.sh." . $aResult['exec_id'] . ".info.log";
        $this->assertEquals($sExpectedStdOut, $sSupervisorInfoFiltered);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START

(i) Starting script 'tests/resources/bash_colored_simple.sh' with id '20131002075605_28465'
Title:
┆   level 1
┆   ┆   yellow level 2
  END with spaces" . '  ' . "
OK

(i) Supervisor log file: /var/log/supervisor/supervisor.info.log
(i) Execution log file: /var/log/supervisor/bash_colored_simple.sh.20131002075605_28465.info.log

[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }
}

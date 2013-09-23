<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SupervisorTest extends SupervisorTestCase
{
    /**
     */
    public function testWithoutScript ()
    {
        $aResult = $this->execSupervisor('', true);
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals('', $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("NO SCRIPT;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\\ Missing script name!\n", $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testWithNotExecutableScript ()
    {
        $sScript = RESOURCES_DIR . '/not_executable';
        $aResult = $this->execSupervisor($sScript, true);
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals('', $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("NO SCRIPT;INIT ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals("/!\ Script '$sScript' not found!\n", $aResult['supervisor_err_content']);
    }

    /**
     */
    public function testWithEmptyExecutableScript ()
    {
        $sScriptName = 'empty_executable';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, true);
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
    public function testWithBashExit ()
    {
        $sScriptName = 'bash_exit_not_null.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, true);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
/!\ Script '$sScriptPath' FAILED!

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log:
%2\$s
(i) Execution log file: $this->sTmpDir/$sScriptName.%1\$s.info.log
(i) Error log file: $this->sTmpDir/$sScriptName.%1\$s.error.log:
/!\ Exit code not null: 42
";
        $this->assertEquals(sprintf($sExpectedStdOut, $aResult['exec_id'], file_get_contents($aResult['supervisor_info_path'])), $aResult['std_out']);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] ERROR\n", $aResult['script_info_content']);
        $this->assertEquals("Exit code not null: 42\n", $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;ERROR\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }
}

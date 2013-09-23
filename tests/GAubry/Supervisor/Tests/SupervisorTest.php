<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SupervisorTest extends SupervisorTestCase
{
    /**
     */
    public function testWithoutScript ()
    {
        list($sExecId, $sStdOut, $sScriptInfo, $sSupervisorInfo, $sSupervisorErr) = $this->execSupervisor('', true);
        $this->assertEquals('', $sStdOut);
        $this->assertEquals('', $sScriptInfo);
        $this->assertEquals("NO SCRIPT;INIT ERROR\n", $sSupervisorInfo);
        $this->assertEquals("/!\\ Missing script name!\n", $sSupervisorErr);
    }

    /**
     */
    public function testWithNotExecutableScript ()
    {
        $sScript = RESOURCES_DIR . '/not_executable';
        list($sExecId, $sStdOut, $sScriptInfo, $sSupervisorInfo, $sSupervisorErr) = $this->execSupervisor($sScript, true);
        $this->assertEquals('', $sStdOut);
        $this->assertEquals('', $sScriptInfo);
        $this->assertEquals("NO SCRIPT;INIT ERROR\n", $sSupervisorInfo);
        $this->assertEquals("/!\ Script '$sScript' not found!\n", $sSupervisorErr);
    }

    /**
     */
    public function testWithEmptyExecutableScript ()
    {
        $sScript = RESOURCES_DIR . '/empty_executable';
        list($sExecId, $sStdOut, $sScriptInfo, $sSupervisorInfo, $sSupervisorErr) = $this->execSupervisor($sScript, true);
        $this->assertEquals("
(i) Starting script '$sScript' with id '$sExecId'
OK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/empty_executable.$sExecId.info.log
"
            , $sStdOut);
        $this->assertEquals("[SUPERVISOR] START\n[SUPERVISOR] OK\n", $sScriptInfo);
        $this->assertEquals("$sScript;START\n$sScript;OK\n", $sSupervisorInfo);
        $this->assertEquals('', $sSupervisorErr);
    }
}

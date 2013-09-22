<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SupervisorTest extends SupervisorTestCase
{
    /**
     */
    public function testWithoutScript ()
    {
        list($sExecId, $sStdOut, $sSupervisorInfo, $sSupervisorErr) = $this->execSupervisor('', true);
        $this->assertEquals('', $sStdOut);
        $this->assertEquals("NO SCRIPT;INIT ERROR\n", $sSupervisorInfo);
        $this->assertEquals("/!\\ Missing script name!\n", $sSupervisorErr);
    }

    /**
     */
    public function testWithNotExecutableScript ()
    {
        $sScript = RESOURCES_DIR . '/not_executable';
        list($sExecId, $sStdOut, $sSupervisorInfo, $sSupervisorErr) =
            $this->execSupervisor($sScript, true);
        $this->assertEquals('', $sStdOut);
        $this->assertEquals("NO SCRIPT;INIT ERROR\n", $sSupervisorInfo);
        $this->assertEquals("/!\ Script '$sScript' not found!\n", $sSupervisorErr);
    }

    /**
     */
    public function testWithEmptyExecutableScript ()
    {
        $sScript = RESOURCES_DIR . '/empty_executable';
        list($sExecId, $sStdOut, $sSupervisorInfo, $sSupervisorErr) =
            $this->execSupervisor($sScript, true);
        $this->assertEquals("
(i) Starting script '/home/geoffroy/eclipse-workspace-4.2/github.perso.supervisor/tests/resources/empty_executable' with id '$sExecId'
OK

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: $this->sTmpDir/empty_executable.$sExecId.info.log
"
            , $sStdOut);
        $this->assertEquals("$sScript;START
$sScript;OK\n", $sSupervisorInfo);
        $this->assertEquals('', $sSupervisorErr);
    }
}

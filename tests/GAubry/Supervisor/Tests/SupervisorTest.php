<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SupervisorTest extends SupervisorTestCase
{
    /**
     */
    public function testWithoutScript ()
    {
//         $this->setExpectedException('\RuntimeException', 'Exit code not nullcc: 1');
        list($aStdOut, $aSupervisorInfo, $aSupervisorErr) = $this->execSupervisor('', true);
        $this->assertEquals(array(), $aStdOut);
        $this->assertEquals(array('NO SCRIPT;INIT ERROR', ''), $aSupervisorInfo);
        $this->assertEquals(array('/!\ Missing script name!', ''), $aSupervisorErr);
    }

    /**
     */
    public function testWithNotExecutableScript ()
    {
//         $this->setExpectedException('\RuntimeException', 'Exit code not nullcc: 1');
        list($aStdOut, $aSupervisorInfo, $aSupervisorErr) =
            $this->execSupervisor(RESOURCES_DIR . '/not_executable', true);
        $this->assertEquals(array(), $aStdOut);
        $this->assertEquals(array('NO SCRIPT;INIT ERROR', ''), $aSupervisorInfo);
        $this->assertEquals(array("/!\ Script '" . RESOURCES_DIR . "/not_executable' not found!", ''), $aSupervisorErr);
    }
}

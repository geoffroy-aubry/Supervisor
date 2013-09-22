<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SupervisorTest extends SupervisorTestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        parent::setUp();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

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
}

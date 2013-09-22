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
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
    }

    /**
     */
    public function testX ()
    {
        $sCmd = "src/supervisor.sh tests/resources/simple.sh";
        $aResult = $this->exec($sCmd, true);
        $this->assertEquals(array(), $aResult);
    }
}

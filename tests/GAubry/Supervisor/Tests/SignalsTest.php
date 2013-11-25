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

use GAubry\Helpers\Helpers;

class SignalsTest extends SupervisorTestCase
{

    /**
     * Only SIGTERM tested:
     *   Non-builtin commands started by Bash have signal handlers set to the values inherited
     *   by the shell from its parent. When job control is not in effect, asynchronous commands
     *   ignore SIGINT and SIGQUIT in addition to these inherited handlers.
     *   Commands run as a result of command substitution ignore the keyboard-generated job control
     *   signals SIGTTIN, SIGTTOU, and SIGTSTP.
     * @see https://www.gnu.org/software/bash/manual/html_node/Signals.html
     *
     * @shcovers src/supervisor.sh::interrupt
     */
    public function testSigTerm ()
    {
        $sScriptName = 'trap_signals.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, '', true, true);

        $iPid = (int)$aResult['std_out'];
        usleep(20 * 1000);
        posix_kill($iPid, SIGTERM);
        sleep(1);

        $this->assertContains(";$sScriptPath;ERROR", file_get_contents($aResult['supervisor_info_path']));
        $this->assertEquals('', file_get_contents($aResult['supervisor_err_path']));
        $this->assertContains(">> SIGTERM signal received <<", file_get_contents($aResult['script_info_path']));
        $this->assertContains("[SUPERVISOR] ERROR", file_get_contents($aResult['script_info_path']));
        $this->assertEquals("[SUPERVISOR] Exit code not null: 143\n", file_get_contents($aResult['script_err_path']));
    }
}

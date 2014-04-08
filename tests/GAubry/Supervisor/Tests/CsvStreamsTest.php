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

class CsvStreamsTest extends SupervisorTestCase
{
    private function filterScriptInfo ($sScriptInfoPath)
    {
        return preg_replace(
            array(
                '/^.*;(┆   )*\s*\[(SUPERVISOR|DEBUG|MAILTO|MAIL_ATTACHMENT)\].*$\n/m',
                '/^\|([0-9: -]{22}cs)\|;/m'
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
     * @shcovers inc/common.sh::displayScriptMsg
     * @shcovers inc/common.sh::getScriptFormattedTimestamp
     */
    public function testBashCsvSimpleScript ()
    {
        $sScriptName = 'bash_csv_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_csv.sh');
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = $this->getExpectedSupervisorStdOut($sScriptPath, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
0;a;Title:;
1;b;level1;x
2;c;|yellow;level 2|;y
END
[SUPERVISOR] OK\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;OK\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }

    /**
     * @shcovers inc/common.sh::displayScriptMsg
     * @shcovers inc/common.sh::getScriptFormattedTimestamp
     */
    public function testCsvWarningMessages ()
    {
        $sScriptName = 'bash_csv_warning.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_csv.sh');
        $sScriptInfoFiltered = $this->filterScriptInfo($aResult['script_info_path']);
        $sExpectedStdOut = "
(i) Starting script '$sScriptPath' with id '%1\$s'
%2\$s2 WARNINGS

(i) Supervisor log file: $this->sTmpDir/supervisor.info.log
(i) Execution log file: {$this->sTmpDir}/$sScriptName.%1\$s.info.log";
        $sExpectedStdOut = sprintf($sExpectedStdOut, $aResult['exec_id'], $sScriptInfoFiltered);
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals("[SUPERVISOR] START
0;a;Title:;
1;b;level1;x
1;[WARNING] utf-8 aïe;level1;x
1;b;[WARNING] utf-8 message…;x
2;c;|yellow;level 2|;y
1;b;[WARNING] second message;x
END
[SUPERVISOR] WARNING (#2)\n", $aResult['script_info_content']);
        $this->assertEquals('', $aResult['script_err_content']);
        $this->assertEquals("$sScriptPath;START\n$sScriptPath;WARNING\n", $aResult['supervisor_info_content']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
    }
}

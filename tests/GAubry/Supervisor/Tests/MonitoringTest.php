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

class MonitoringTest extends SupervisorTestCase
{
    /**
     * @shcovers inc/common.sh::monitor
     * @shcovers inc/common.sh::doAction
     */
    public function testMonitorWithoutError ()
    {
        $aResult = $this->execSupervisor('--monitor', 'conf_mail-all.sh');
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['sent_mails']);
        $this->assertFileNotExists("$this->sTmpDir/supervisor.monitoring.log");
    }

    /**
     * @shcovers inc/common.sh::monitor
     */
    public function testMonitorWhenFirstMailWithError ()
    {
        $sSupervisorErrPath = $this->sTmpDir . '/supervisor.error.log';
        $sSupervisorErrContent = 'There is an error!';
        file_put_contents($sSupervisorErrPath, $sSupervisorErrContent);

        $aResult = $this->execSupervisor('--monitor', 'conf_mail-all.sh');
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);

        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.gz'"
                     . " '$this->sTmpDir/supervisor.error.log.gz'";
        $sMailSubjet = '[SUPERVISOR MONITORING] CRITICAL ERROR';
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '$sMailSubjet' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);

        $sMonitoringLogPath = "$this->sTmpDir/supervisor.monitoring.log";
        $sMonitoringContent = file_get_contents($sMonitoringLogPath);
        $sPattern = md5($sSupervisorErrContent) . ' 1 %s';
        $iNow = floor(microtime(true));
        $iNext = $iNow + 60 - 2;
        $sRegexp = '/^(' . sprintf($sPattern, $iNext-1) . '|' . sprintf($sPattern, $iNext)
                 . '|' . sprintf($sPattern, $iNext+1) . ')$/';
        $this->assertRegExp($sRegexp, $sMonitoringContent);
    }

    /**
     * @shcovers inc/common.sh::monitor
     */
    public function testMonitorWhenLessThan10MailsWithErrorButTooEarly ()
    {
        $sSupervisorErrPath = $this->sTmpDir . '/supervisor.error.log';
        $sSupervisorErrContent = 'There is an error!';
        file_put_contents($sSupervisorErrPath, $sSupervisorErrContent);

        $sMonitoringLogPath = "$this->sTmpDir/supervisor.monitoring.log";
        $sMonitoringContent = md5($sSupervisorErrContent) . ' 5 ' . (floor(microtime(true))+100);
        file_put_contents($sMonitoringLogPath, $sMonitoringContent);

        $aResult = $this->execSupervisor('--monitor', 'conf_mail-all.sh');
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['sent_mails']);
        $this->assertEquals($sMonitoringContent, file_get_contents($sMonitoringLogPath));
    }

    /**
     * @dataProvider providerTestMonitor
     * @shcovers inc/common.sh::monitor
     */
    public function testMonitorWithError ($iSentMails, $iNextIncrement)
    {
        $sSupervisorErrPath = $this->sTmpDir . '/supervisor.error.log';
        $sSupervisorErrContent = 'There is an error!';
        file_put_contents($sSupervisorErrPath, $sSupervisorErrContent);

        $sMonitoringLogPath = "$this->sTmpDir/supervisor.monitoring.log";
        $sMonitoringContent = md5($sSupervisorErrContent) . " $iSentMails " . (floor(microtime(true))-100);
        file_put_contents($sMonitoringLogPath, $sMonitoringContent);

        $aResult = $this->execSupervisor('--monitor', 'conf_mail-all.sh');
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);

        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.gz'"
                     . " '$this->sTmpDir/supervisor.error.log.gz'";
        $sMailSubjet = '[SUPERVISOR MONITORING] CRITICAL ERROR';
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '$sMailSubjet' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);

        $sPattern = md5($sSupervisorErrContent) . ' ' . ($iSentMails+1) . ' %s';
        $iNow = floor(microtime(true));
        $iNext = $iNow + $iNextIncrement - 2;
        $sRegexp = '/^(' . sprintf($sPattern, $iNext-1) . '|' . sprintf($sPattern, $iNext)
                 . '|' . sprintf($sPattern, $iNext+1) . ')$/';
        $this->assertRegExp($sRegexp, file_get_contents($sMonitoringLogPath));
    }

    public function providerTestMonitor ()
    {
        return array(
            array( 1, 60),
            array( 2, 60),
            array( 3, 60),
            array( 4, 60),
            array( 5, 60),
            array( 6, 60),
            array( 7, 60),
            array( 8, 60),
            array( 9, 10*60),
            array(10, 10*60),
            array(11, 10*60),
            array(12, 10*60),
            array(13, 10*60),
            array(14, 10*60),
            array(15, 10*60),
            array(16, 10*60),
            array(17, 10*60),
            array(18, 10*60),
            array(19, 3600),
            array(20, 3600),
            array(21, 3600),
            array(22, 3600),
            array(23, 3600),
            array(24, 3600),
            array(25, 3600),
            array(26, 3600),
            array(27, 3600),
            array(28, 3600),
            array(29, 6*3600),
            array(30, 6*3600),
            array(50, 6*3600),
        );
    }

    /**
     * @shcovers inc/common.sh::monitor
     */
    public function testMonitorWithAdditionalError ()
    {
        $sSupervisorErrPath = $this->sTmpDir . '/supervisor.error.log';
        $sSupervisorErrContent = 'There is an error!';
        file_put_contents($sSupervisorErrPath, $sSupervisorErrContent);

        $sMonitoringLogPath = "$this->sTmpDir/supervisor.monitoring.log";
        $sMonitoringContent = md5($sSupervisorErrContent) . ' 5 ' . (floor(microtime(true))+100);
        file_put_contents($sMonitoringLogPath, $sMonitoringContent);

        $sSupervisorErrContent .= "\nThere is a NEW error!";
        file_put_contents($sSupervisorErrPath, $sSupervisorErrContent);

        $aResult = $this->execSupervisor('--monitor', 'conf_mail-all.sh');
        $this->assertEquals('', $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);

        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.gz'"
                     . " '$this->sTmpDir/supervisor.error.log.gz'";
        $sMailSubjet = '[SUPERVISOR MONITORING] CRITICAL ERROR';
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '$sMailSubjet' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);

        $sMonitoringLogPath = "$this->sTmpDir/supervisor.monitoring.log";
        $sMonitoringContent = file_get_contents($sMonitoringLogPath);
        $sPattern = md5($sSupervisorErrContent) . ' 1 %s';
        $iNow = floor(microtime(true));
        $iNext = $iNow + 60 - 2;
        $sRegexp = '/^(' . sprintf($sPattern, $iNext-1) . '|' . sprintf($sPattern, $iNext)
        . '|' . sprintf($sPattern, $iNext+1) . ')$/';
        $this->assertRegExp($sRegexp, $sMonitoringContent);
    }

    // nouveau contenu => nouvelle erreur
}

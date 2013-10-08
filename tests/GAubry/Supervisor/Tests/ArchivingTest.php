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

class ArchivingTest extends SupervisorTestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        $sCmd =
            'bash -c \'for f in $(ls -1 "' . RESOURCES_DIR . '"/archiving/*.log | grep -v /supervisor.); do
                d="$(echo "$f" | sed -r "s/^.*([0-9]{14}).*$/\\1/")"
                touch -t ${d:0:-2} "$f"
            done\'';
        Helpers::exec($sCmd);
        $sCmd = 'for f in $(ls -1 "' . RESOURCES_DIR . '"/archiving/supervisor*.log); do touch -t 201310011200 "$f"; done';
        Helpers::exec($sCmd);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        parent::setUp();
        $sCmd = 'cp -a "' . RESOURCES_DIR . '/archiving" "' . $this->sTmpDir . '/archiving"';
        $this->exec($sCmd);
    }

    /**
     * @shcovers inc/common.sh::archive
     * @shcovers inc/common.sh::doAction
     */
    public function testArchivingWhenNothingToDo ()
    {
        $iMinDays = floor((date("U") - mktime(0, 0, 0, 9, 1, 2013))/(3600*24));
        $aResult = $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        $sExpectedStdOut = "
Archiving from 2013-09-21 to 2013-09-01 inclusive:
    No date to process…";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
        $this->assertEquals('', $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/common.sh::archive
     * @shcovers inc/common.sh::doAction
     */
    public function testArchivingWhen1stPass ()
    {
        $iMinDays = floor((date("U") - mktime(0, 0, 0, 9, 30, 2013))/(3600*24));
        $aResult = $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        $sExpectedStdOut = "
Archiving from 2013-09-21 to 2013-09-30 inclusive:
    2013-09-21 ⇒ archiving 10 files into $this->sTmpDir/supervisor_archive_2013-09-21.tar.gz
    2013-09-22 ⇒ archiving 9 files into $this->sTmpDir/supervisor_archive_2013-09-22.tar.gz
    2013-09-23 ⇒ no file to archive
    2013-09-24 ⇒ no file to archive
    2013-09-25 ⇒ archiving 6 files into $this->sTmpDir/supervisor_archive_2013-09-25.tar.gz
    2013-09-26 ⇒ archiving 2 files into $this->sTmpDir/supervisor_archive_2013-09-26.tar.gz
    2013-09-27 ⇒ no file to archive
    2013-09-28 ⇒ archiving 12 files into $this->sTmpDir/supervisor_archive_2013-09-28.tar.gz
    2013-09-29 ⇒ archiving 16 files into $this->sTmpDir/supervisor_archive_2013-09-29.tar.gz
    2013-09-30 ⇒ archiving 1 file into $this->sTmpDir/supervisor_archive_2013-09-30.tar.gz";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
        $this->assertEquals('', $aResult['sent_mails']);

        $sResult = $this->exec("tar --list -zf $this->sTmpDir/supervisor_archive_2013-09-25.tar.gz");
        $sExpectedResult = "php_notice.php.20130925182549_19962.info.log
php_notice.php.20130925182549_19962.error.log
php_notice.php.20130925182646_02998.info.log
php_notice.php.20130925182646_02998.error.log
php_notice.php.20130925182719_29103.info.log
php_notice.php.20130925182719_29103.error.log";
        $this->assertEquals($sExpectedResult, $sResult);

        $sResult = $this->exec("tar --list -zf $this->sTmpDir/supervisor_archive_2013-09-30.tar.gz");
        $sExpectedResult = "bash_colored_simple.sh.20130930155451_04516.info.log";
        $this->assertEquals($sExpectedResult, $sResult);

        $sResult = $this->exec("ls -1 $this->sTmpDir/archiving | grep .log");
        $sExpectedResult = "supervisor.error.log\nsupervisor.info.log";
        $this->assertEquals($sExpectedResult, $sResult);
    }

    /**
     * @shcovers inc/common.sh::archive
     * @shcovers inc/common.sh::doAction
     */
    public function testArchivingWhen2ndPass ()
    {
        $iMinDays = floor((date("U") - mktime(0, 0, 0, 9, 30, 2013))/(3600*24));
        $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        unlink("$this->sTmpDir/supervisor_archive_2013-09-28.tar.gz");
        $aResult = $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        $sExpectedStdOut = "
Archiving from 2013-10-01 to 2013-09-30 inclusive:
    No date to process…";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
        $this->assertEquals('', $aResult['sent_mails']);

        $sResult = $this->exec("ls -1 $this->sTmpDir/archiving | grep .log");
        $sExpectedResult = "supervisor.error.log\nsupervisor.info.log";
        $this->assertEquals($sExpectedResult, $sResult);
    }

    /**
     * @shcovers inc/common.sh::archive
     * @shcovers inc/common.sh::doAction
     */
    public function testArchivingWhen2ndPassWithRegeneratedLogs ()
    {
        $iMinDays = floor((date("U") - mktime(0, 0, 0, 9, 30, 2013))/(3600*24));
        $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        unlink("$this->sTmpDir/supervisor_archive_2013-09-28.tar.gz");
        $this->exec('cp -a "' . RESOURCES_DIR . '/archiving/"* "' . $this->sTmpDir . '/archiving"');
        $aResult = $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        $sExpectedStdOut = "
Archiving from 2013-09-21 to 2013-09-30 inclusive:
    2013-09-21 ⇒ already archived into $this->sTmpDir/supervisor_archive_2013-09-21.tar.gz
    2013-09-22 ⇒ already archived into $this->sTmpDir/supervisor_archive_2013-09-22.tar.gz
    2013-09-23 ⇒ no file to archive
    2013-09-24 ⇒ no file to archive
    2013-09-25 ⇒ already archived into $this->sTmpDir/supervisor_archive_2013-09-25.tar.gz
    2013-09-26 ⇒ already archived into $this->sTmpDir/supervisor_archive_2013-09-26.tar.gz
    2013-09-27 ⇒ no file to archive
    2013-09-28 ⇒ archiving 12 files into $this->sTmpDir/supervisor_archive_2013-09-28.tar.gz
    2013-09-29 ⇒ already archived into $this->sTmpDir/supervisor_archive_2013-09-29.tar.gz
    2013-09-30 ⇒ already archived into $this->sTmpDir/supervisor_archive_2013-09-30.tar.gz";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
        $this->assertEquals('', $aResult['sent_mails']);

        $sResult = $this->exec("tar --list -zf $this->sTmpDir/supervisor_archive_2013-09-25.tar.gz");
        $sExpectedResult = "php_notice.php.20130925182549_19962.info.log
php_notice.php.20130925182549_19962.error.log
php_notice.php.20130925182646_02998.info.log
php_notice.php.20130925182646_02998.error.log
php_notice.php.20130925182719_29103.info.log
php_notice.php.20130925182719_29103.error.log";
        $this->assertEquals($sExpectedResult, $sResult);

        $sResult = $this->exec("tar --list -zf $this->sTmpDir/supervisor_archive_2013-09-30.tar.gz");
        $sExpectedResult = "bash_colored_simple.sh.20130930155451_04516.info.log";
        $this->assertEquals($sExpectedResult, $sResult);

        $sResult = $this->exec("ls -1 $this->sTmpDir/archiving | grep .log");
        $sExpectedResult = "bash_colored_simple.sh.20130930155451_04516.info.log
bash_std_err_with_mail_to.20130929182339_27780.error.log
bash_std_err_with_mail_to.20130929182339_27780.info.log
bash_std_err_with_mail_to.sh.20130929182345_30991.error.log
bash_std_err_with_mail_to.sh.20130929182345_30991.info.log
bash_warning.sh.20130929104235_09518.error.log
bash_warning.sh.20130929104235_09518.info.log
bash_warning.sh.20130929110613_28133.error.log
bash_warning.sh.20130929110613_28133.info.log
bash_warning.sh.20130929110708_20900.error.log
bash_warning.sh.20130929110708_20900.info.log
bash_warning.sh.20130929110713_16295.error.log
bash_warning.sh.20130929110713_16295.info.log
bash_warning.sh.20130929110745_11453.error.log
bash_warning.sh.20130929110745_11453.info.log
bash_warning.sh.20130929110817_14911.error.log
bash_warning.sh.20130929110817_14911.info.log
php_exception.php.20130926105318_07271.error.log
php_exception.php.20130926105318_07271.info.log
php_notice.php.20130925182549_19962.error.log
php_notice.php.20130925182549_19962.info.log
php_notice.php.20130925182646_02998.error.log
php_notice.php.20130925182646_02998.info.log
php_notice.php.20130925182719_29103.error.log
php_notice.php.20130925182719_29103.info.log
simple.sh.20130922105102_16642.error.log
simple.sh.20130922105102_16642.info.log
supervisor.error.log
supervisor.info.log
test.sh.20130921145030_17286.info.log
test.sh.20130921170840_16461.info.log
test.sh.20130921170941_06322.info.log
test.sh.20130921171036_10264.info.log
test.sh.20130921171042_05972.info.log
test.sh.20130921171056_16283.info.log
test.sh.20130921171134_27762.info.log
test.sh.20130921171211_10757.info.log
test.sh.20130921171316_27572.error.log
test.sh.20130921171316_27572.info.log
test.sh.20130922085733_11423.info.log
test.sh.20130922095045_20963.info.log
test.sh.20130922095742_27057.info.log
test.sh.20130922095814_04179.info.log
test.sh.20130922095845_13822.info.log
test.sh.20130922095903_18667.info.log
test.sh.20130922095936_17858.info.log";
        $this->assertEquals($sExpectedResult, $sResult);
    }

/**
     * @shcovers inc/common.sh::archive
     * @shcovers inc/common.sh::doAction
     */
    public function testPartialArchiving ()
    {
        $iMinDays = floor((date("U") - mktime(0, 0, 0, 9, 28, 2013))/(3600*24));
        $aResult = $this->execSupervisor("--archive=$iMinDays", 'archiving/conf.sh');
        $sExpectedStdOut = "
Archiving from 2013-09-21 to 2013-09-28 inclusive:
    2013-09-21 ⇒ archiving 10 files into $this->sTmpDir/supervisor_archive_2013-09-21.tar.gz
    2013-09-22 ⇒ archiving 9 files into $this->sTmpDir/supervisor_archive_2013-09-22.tar.gz
    2013-09-23 ⇒ no file to archive
    2013-09-24 ⇒ no file to archive
    2013-09-25 ⇒ archiving 6 files into $this->sTmpDir/supervisor_archive_2013-09-25.tar.gz
    2013-09-26 ⇒ archiving 2 files into $this->sTmpDir/supervisor_archive_2013-09-26.tar.gz
    2013-09-27 ⇒ no file to archive
    2013-09-28 ⇒ archiving 12 files into $this->sTmpDir/supervisor_archive_2013-09-28.tar.gz";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
        $this->assertEquals('', $aResult['supervisor_err_content']);
        $this->assertEquals('', $aResult['sent_mails']);

        $sResult = $this->exec("ls -1 $this->sTmpDir/archiving | grep .log");
        $sExpectedResult = "bash_colored_simple.sh.20130930155451_04516.info.log
bash_std_err_with_mail_to.20130929182339_27780.error.log
bash_std_err_with_mail_to.20130929182339_27780.info.log
bash_std_err_with_mail_to.sh.20130929182345_30991.error.log
bash_std_err_with_mail_to.sh.20130929182345_30991.info.log
bash_warning.sh.20130929104235_09518.error.log
bash_warning.sh.20130929104235_09518.info.log
bash_warning.sh.20130929110613_28133.error.log
bash_warning.sh.20130929110613_28133.info.log
bash_warning.sh.20130929110708_20900.error.log
bash_warning.sh.20130929110708_20900.info.log
bash_warning.sh.20130929110713_16295.error.log
bash_warning.sh.20130929110713_16295.info.log
bash_warning.sh.20130929110745_11453.error.log
bash_warning.sh.20130929110745_11453.info.log
bash_warning.sh.20130929110817_14911.error.log
bash_warning.sh.20130929110817_14911.info.log
supervisor.error.log
supervisor.info.log";
        $this->assertEquals($sExpectedResult, $sResult);
    }
}

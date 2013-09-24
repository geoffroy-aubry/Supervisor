<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SentMailsTest extends SupervisorTestCase
{
    public function testInitMailWithPhpNotice ()
    {
        $sScriptName = 'php_notice.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-init.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testAllMailsWithPhpNotice ()
    {
        $sScriptName = 'php_notice.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testAllMailsWithPhpFatalError ()
    {
        $sScriptName = 'php_fatal_error.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testWarningErrorMailsWithPhpNotice ()
    {
        $sScriptName = 'php_notice.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-warning-error.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testAllMailsWithBashColoredSimple ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > SUCCESS ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testAllMailsWithInstigator ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("--instigator-email=toto@fr $sScriptPath", 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com' 'toto@fr'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > SUCCESS ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testWarningErrorMailsWithBashColoredSimple ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-warning-error.sh');
        $this->assertEquals('', $aResult['sent_mails']);
    }

    public function testMailsWithoutScript ()
    {
        $sScriptName = '';
        $sScriptPath = '';
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testMailsWithScriptNotFound ()
    {
        $sScriptName = 'not_exists';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    public function testMailsWithNotExecutableScript ()
    {
        $sScriptName = 'not_executable';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }
}

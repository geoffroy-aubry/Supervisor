<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SentMailsTest extends SupervisorTestCase
{
    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::sendMail
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnSuccess
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnSuccess
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnSuccess
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnSuccess
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::sendMail
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     *
     * @shcovers inc/mails.sh::parentSendMailOnWarning
     * @shcovers inc/mails.sh::sendMailOnWarning
     */
    public function testWarningErrorMailsWithBashColoredSimple ()
    {
        $sScriptName = 'bash_colored_simple.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-warning-error.sh');
        $this->assertEquals('', $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
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

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnError
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnError
     */
    public function testBlockingLocks ()
    {
        $sScriptName = 'bash_colored_simple_sleep2.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $sCmdA = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_lock-A.sh' $sScriptPath";
        $sCmdB = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/conf_lock-B.sh' $sScriptPath";
        $sCmd = "($sCmdA) > /dev/null 2>&1 & sleep .2 && ($sCmdB)";

        $aResult = $this->execSupervisor($sCmd, array('conf_lock-A.sh', 'conf_lock-B.sh'));
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz' '$this->sTmpDir/$sScriptName.$sExecId.error.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > ERROR ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/common.sh::loadCustomizedMails
     */
    public function testCustomMailWithParameter ()
    {
        $sScriptName = 'php_notice.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $sParameters = "--param=ETL=maChaîne\ ETL"
                     . " --customized-mails=" . RESOURCES_DIR . "/mails_with_parameter.sh $sScriptPath";
        $aResult = $this->execSupervisor($sParameters, 'conf_mail-init.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s 'maChaîne ETL' -- 'abc@def.com' 'ghi@jkl.com'";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/common.sh::loadCustomizedMails
     */
    public function testCustomMailWithMultipleParameters ()
    {
        $sScriptName = 'php_notice.php';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $sParameters = "--param=ETL=maChaîne\ ETL --param=etl=lower-case"
                     . " --customized-mails=" . RESOURCES_DIR . "/mails_with_multiple_parameters.sh $sScriptPath";
        $aResult = $this->execSupervisor($sParameters, 'conf_mail-init.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s 'maChaîne ETL.lower-case' -- 'abc@def.com' 'ghi@jkl.com'";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnWarning
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnWarning
     */
    public function testAllMailsWithWarnings ()
    {
        $sScriptName = 'bash_warning.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor("--instigator-email=toto@fr $sScriptPath", 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com' 'toto@fr'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
                        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > WARNING ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnSuccess
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnSuccess
     */
    public function testMailsWithMailTags ()
    {
        $sScriptName = 'bash_mail_to_tags.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAddMailTo = " 'test1@xyz.com' 'test2@xyz.com' 'test3@xyz.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > SUCCESS ($sExecId)' $sAttachment -- $sMailTo$sAddMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }

    /**
     * @shcovers inc/mails.sh::getMailInstigator
     * @shcovers inc/mails.sh::getMailMsgCmdAndServer
     * @shcovers inc/mails.sh::getMailSubject
     * @shcovers inc/mails.sh::parentSendMailOnInit
     * @shcovers inc/mails.sh::sendMail
     * @shcovers inc/mails.sh::sendMailOnInit
     *
     * @shcovers inc/mails.sh::compressAttachedFiles
     * @shcovers inc/mails.sh::getMailMsgInfoLogFiles
     * @shcovers inc/mails.sh::parentSendMailOnSuccess
     * @shcovers inc/mails.sh::removeAttachedFiles
     * @shcovers inc/mails.sh::sendMailOnSuccess
     */
    public function testMailsWithMailAttachmentTags ()
    {
        $sScriptName = 'bash_mail_attachment_tags.sh';
        $sScriptPath = RESOURCES_DIR . "/$sScriptName";
        $aResult = $this->execSupervisor($sScriptPath, 'conf_mail-all.sh');
        $sExecId = $aResult['exec_id'];
        $sMailTo = "'abc@def.com' 'ghi@jkl.com'";
        $sAttachment = "-a '$this->sTmpDir/supervisor.info.log.$sExecId.gz' '$this->sTmpDir/$sScriptName.$sExecId.info.log.gz'"
                     . " '/path/to/file1' '/path/to/file2' '/path/to/file3'";
        $sExpectedMails = "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > STARTING ($sExecId)' -- $sMailTo\n"
        . "mutt -e 'set content_type=text/html' -s '[DW] $sScriptName > SUCCESS ($sExecId)' $sAttachment -- $sMailTo";
        $this->assertEquals($sExpectedMails, $aResult['sent_mails']);
    }
}

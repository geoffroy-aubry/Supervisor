<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;
use GAubry\Helpers\Debug;

class SupervisorTestCase extends \PHPUnit_Framework_TestCase
{
    protected $sTmpDir;

    private function copyConfigFile ($sConfigFilename)
    {
        $sContent = file_get_contents(RESOURCES_DIR . '/' . $sConfigFilename);
        $aReplace = array(
            '{root_dir}' => ROOT_DIR,
            '{log_dir}' => $this->sTmpDir
        );
        $sContent = strtr($sContent, $aReplace);
        file_put_contents($this->sTmpDir . '/' . $sConfigFilename, $sContent);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $this->sTmpDir = sys_get_temp_dir() . '/supervisor-test.' . date("Ymd-His")
                       . '.' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        mkdir($this->sTmpDir);
        $this->copyConfigFile('conf.sh');
    }

    protected function execSupervisor ($sParameters, $sConfigFilename = '', $bStripBashColors = true)
    {
        if (empty($sConfigFilename)) {
            $sConfigFilename = 'conf.sh';
        } else {
            $this->copyConfigFile($sConfigFilename);
        }
        $sCmd = SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/$sConfigFilename' $sParameters";
        try {
            $sStdOut = $this->exec($sCmd, $bStripBashColors);
        } catch (\RuntimeException $oException) {
            if (substr($oException->getMessage(), 0, strlen('Exit code not null: ')) == 'Exit code not null: ') {
                $sStdOut = '';
            } else {
                throw $oException;
            }
        }

        $sAnyDate   = '2012-07-18 15:01:46 32cs';
        $sAnyExecId = '20120718150145_17543';

        $sExecId = '';
        $sScriptName = '';
        $sSupervisorInfoPath = $this->sTmpDir . '/supervisor.info.log';
        $sSupervisorInfo = $this->stripBashColors(implode('', file($sSupervisorInfoPath)), $bStripBashColors);
        $aFilteredSupervisorInfo = array();
        foreach(explode("\n", $sSupervisorInfo) as $sLine) {
            if (empty($sLine)) {
                $aFilteredSupervisorInfo[] = '';
            } else {
                if (empty($sExecId)) {
                    $sExecId = substr($sLine, strlen("$sAnyDate;"), strlen($sAnyExecId));
                    $sFullScriptName = strstr(substr($sLine, strlen("$sAnyDate;$sAnyExecId;")), ';', true);
                    $sScriptName = substr(strrchr($sFullScriptName, '/'), 1);
                }
                $aFilteredSupervisorInfo[] = substr($sLine, strlen("$sAnyDate;$sAnyExecId;"));
            }
        }

        $sSupervisorErrPath = $this->sTmpDir . '/supervisor.error.log';
        $sSupervisorErr = $this->stripBashColors(implode("\n", file($sSupervisorErrPath)), $bStripBashColors);

        $sScriptInfoPath = "$this->sTmpDir/$sScriptName.$sExecId.info.log";
        if (is_file($sScriptInfoPath)) {
            $sScriptInfoContent = preg_replace('/^[^;]+;/m', '', file_get_contents($sScriptInfoPath));
        } else {
            $sScriptInfoContent = '';
        }

        $sScriptErrPath = "$this->sTmpDir/$sScriptName.$sExecId.error.log";
        if (is_file($sScriptErrPath)) {
            $sScriptErrContent = preg_replace('/^[^;]+;/m', '', file_get_contents($sScriptErrPath));
        } else {
            $sScriptErrContent = '';
        }

        $sSentMailsPath = $this->sTmpDir . '/mutt';
        if (is_file($sSentMailsPath)) {
            $sSentMailsContent = trim(file_get_contents($sSentMailsPath));
        } else {
            $sSentMailsContent = '';
        }

        return array(
            'exec_id'                 => $sExecId,
            'std_out'                 => $sStdOut,
            'script_info_path'        => $sScriptInfoPath,
            'script_info_content'     => $sScriptInfoContent,
            'script_err_path'         => $sScriptErrPath,
            'script_err_content'      => $sScriptErrContent,
            'supervisor_info_path'    => $sSupervisorInfoPath,
            'supervisor_info_content' => implode("\n", $aFilteredSupervisorInfo),
            'supervisor_err_path'     => $sSupervisorErrPath,
            'supervisor_err_content'  => $sSupervisorErr,
            'sent_mails'              => $sSentMailsContent
        );
    }

    protected function stripBashColors ($sMsg, $bStripBashColors = true)
    {
        if ($bStripBashColors) {
            $sMsg = Helpers::stripBashColors($sMsg);
        } else {
            $sMsg = str_replace("\033", '\033', $sMsg);
        }
        return $sMsg;
    }

    protected function exec ($sCmd, $bStripBashColors = true)
    {
        try {
            $aResult = Helpers::exec($sCmd);
        } catch (\RuntimeException $oException) {
            if ($oException->getMessage() != '') {
                $sMsg = $oException->getMessage();
                if ($bStripBashColors) {
                    $sMsg = Helpers::stripBashColors($sMsg);
                }
            } else {
                $sMsg = '-- no message --';
            }
            throw new \RuntimeException($sMsg, $oException->getCode(), $oException);
        }
        $sResult = $this->stripBashColors(implode("\n", $aResult), $bStripBashColors);
        return $sResult;
    }
}

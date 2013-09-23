<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;
use GAubry\Helpers\Debug;

class SupervisorTestCase extends \PHPUnit_Framework_TestCase
{
    protected $sTmpDir;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $this->sTmpDir = sys_get_temp_dir() . '/supervisor-test.' . date("Ymd-His")
                       . '.' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        mkdir($this->sTmpDir);
        $sContent = file_get_contents(RESOURCES_DIR . '/conf.sh');
        $aReplace = array(
            '{root_dir}' => ROOT_DIR,
            '{log_dir}' => $this->sTmpDir
        );
        $sContent = strtr($sContent, $aReplace);
        file_put_contents($this->sTmpDir . '/conf.sh', $sContent);
    }

    protected function execSupervisor ($sParameters, $bStripBashColors = true)
    {
        $sCmd = SRC_DIR . "/supervisor.sh -c $this->sTmpDir/conf.sh $sParameters";
        try {
            $sStdOut = $this->exec($sCmd, $bStripBashColors);
        } catch (\RuntimeException $oException) {
            if (substr($oException->getMessage(), 0, strlen('Exit code not null: ')) == 'Exit code not null: ') {
                $sStdOut = '';
            } else {
                throw $oException;
            }
        }

        $sAnyDate = '2012-07-18 15:01:46 32cs';
        $sAnyExecId = '20120718150145_17543';

        $sExecId = '';
        $sScriptName = '';
        $aSupervisorInfo = file($this->sTmpDir . '/supervisor.info.log');
        $sSupervisorInfo = $this->stripBashColors(implode('', $aSupervisorInfo), $bStripBashColors);
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

        $aSupervisorErr = file($this->sTmpDir . '/supervisor.error.log');
        $sSupervisorErr = $this->stripBashColors(implode("\n", $aSupervisorErr), $bStripBashColors);

        $sScriptInfoName = "$this->sTmpDir/$sScriptName.$sExecId.info.log";
        if (is_file($sScriptInfoName)) {
            $sScriptInfo = preg_replace('/^[^;]+;/m', '', file_get_contents($sScriptInfoName));
        } else {
            $sScriptInfo = '';
        }

        return array(
            $sExecId,
            $sStdOut,
            $sScriptInfo,
            implode("\n", $aFilteredSupervisorInfo),
            $sSupervisorErr
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

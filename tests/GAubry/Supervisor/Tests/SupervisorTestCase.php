<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;
use GAubry\Helpers\Debug;

class SupervisorTestCase extends \PHPUnit_Framework_TestCase
{
    private $sTmpDir;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $this->sTmpDir = sys_get_temp_dir() . '/supervisor-test.' . date("Ymd-His") . '.' . getmypid();
        mkdir($this->sTmpDir);
        $sContent = file_get_contents('tests/resources/conf.sh');
        $aReplace = array(
            '{root_dir}' => __DIR__ . '/../../../..',
            '{log_dir}' => $this->sTmpDir
        );
        $sContent = strtr($sContent, $aReplace);
        file_put_contents($this->sTmpDir . '/conf.sh', $sContent);
    }

    protected function execSupervisor ($sParameters, $bStripBashColors = true)
    {
        $sCmd = "src/supervisor.sh -c $this->sTmpDir/conf.sh $sParameters";
        try {
            $aStdOut = $this->exec($sCmd, $bStripBashColors);
        } catch (\RuntimeException $oException) {
            if (substr($oException->getMessage(), 0, strlen('Exit code not null: ')) == 'Exit code not null: ') {
                $aStdOut = array();
            } else {
                throw $oException;
            }
        }

        $aSupervisorInfo = file($this->sTmpDir . '/supervisor.info.log');
        $aSupervisorInfo = $this->stripBashColors($aSupervisorInfo, $bStripBashColors);
        $aFilteredSupervisorInfo = array();
        foreach($aSupervisorInfo as $sLine) {
            if (empty($sLine)) {
                $aFilteredSupervisorInfo[] = '';
            } else {
                $aFilteredSupervisorInfo[] = substr($sLine, strlen('2012-07-18 15:01:46 32cs;20120718150145_17543;'));
            }
        }

        $aSupervisorErr = file($this->sTmpDir . '/supervisor.error.log');
        $aSupervisorErr = $this->stripBashColors($aSupervisorErr, $bStripBashColors);

        return array($aStdOut, $aFilteredSupervisorInfo, $aSupervisorErr);
    }

    protected function stripBashColors (array $aData, $bStripBashColors = true)
    {
        $sMsg = implode("\n", $aData);
        if ($bStripBashColors) {
            $sMsg = Helpers::stripBashColors($sMsg);
        } else {
            $sMsg = str_replace("\033", '\033', $sMsg);
        }
        return explode("\n", $sMsg);
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
        return $this->stripBashColors($aResult, $bStripBashColors);
    }
}

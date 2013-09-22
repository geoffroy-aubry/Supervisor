<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SupervisorTestCase extends \PHPUnit_Framework_TestCase
{
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
        $sMsg = implode("\n", $aResult);
        if ($bStripBashColors) {
            $sMsg = Helpers::stripBashColors($sMsg);
        } else {
            $sMsg = str_replace("\033", '\033', $sMsg);
        }
        return explode("\n", $sMsg);
    }
}

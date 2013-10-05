<?php

namespace GAubry\Supervisor\Tests;

use GAubry\Helpers\Helpers;

class SummaryTest extends SupervisorTestCase
{
    /**
     * @shcovers inc/common.sh::summarize
     * @shcovers inc/common.sh::doAction
     */
    public function testSummarize ()
    {
        $aResult = $this->execSupervisor('--summarize=7', 'summarize/conf.sh');
        $sExpectedStdOut = "
Date        Script                                         Start  OK  Warning  Error  Init error
2013-09-29  NO SCRIPT                                      2      0   0        0      2
2013-09-29  tests/resources/bash_std_err_with_mail_to      2      0   0        1      1
2013-09-29  tests/resources/bash_std_err_with_mail_to.sh   1      0   0        1      0
2013-09-29  tests/resources/bash_warning.sh                51     2   47       0      0
2013-09-28  tests/resources/bash_debug.sh                  7      7   0        0      0
2013-09-28  tests/resources/bash_warning.sh                10     0   9        0      0
2013-09-26  NO SCRIPT                                      1      0   0        0      1
2013-09-26  tests/resources/php_exception.php              1      0   0        1      0
2013-09-25  tests/resources/php_notice.php                 3      0   0        3      0
2013-09-22  examples/test.sh                               11     11  0        0      0
2013-09-22  tests/resources/empty_executable               1      1   0        0      0
2013-09-22  tests/resources/simple.sh                      9      5   0        4      0
2013-09-21  examples/test.sh                               15     14  0        0      2
2013-03-13  /usr/local/lib/data_warehouse/core/dw-job.php  1      0   0        1      0";
        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
    }
}

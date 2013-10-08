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

        // I didn't understand why i need this for travis-ci while it works well on localhost:
        echo "\nA: "; var_dump($aResult['std_out']);
        echo "\nB: "; var_dump(ctype_xdigit($aResult['std_out']));
        if (ctype_xdigit($aResult['std_out'])) {
            echo "\nC: "; var_dump(pack("H*" , $aResult['std_out']));
            echo "\nD: "; var_dump(preg_replace('/\\0330/', '0', pack("H*" , $aResult['std_out'])));
            $aResult['std_out'] = preg_replace('/\\0330/', '0', pack("H*" , $aResult['std_out']));
        }

        $this->assertEquals($sExpectedStdOut, $aResult['std_out']);
        $this->assertEquals(0, $aResult['exit_code']);
    }
}

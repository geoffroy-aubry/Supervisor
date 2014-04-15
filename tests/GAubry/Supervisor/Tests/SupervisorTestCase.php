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
        if (! file_exists($this->sTmpDir . '/' . $sConfigFilename)
            && dirname($this->sTmpDir . '/' . $sConfigFilename) != $this->sTmpDir
        ) {
            mkdir(dirname($this->sTmpDir . '/' . $sConfigFilename), 0777, true);
        }
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

    protected function execSupervisor (
        $sParameters,
        $mConfigFilename = '',
        $bStripBashColors = true,
        $bBackgroundJob = false
    ) {
        if (is_string($mConfigFilename)) {
            if (empty($mConfigFilename)) {
                $mConfigFilename = 'conf.sh';
            } else {
                $this->copyConfigFile($mConfigFilename);
            }
            $sCmd = BASH_PATH . ' ' . SRC_DIR . "/supervisor.sh -c '$this->sTmpDir/$mConfigFilename' $sParameters";
        } else {
            foreach ($mConfigFilename as $sConfigFilename) {
                $this->copyConfigFile($sConfigFilename);
            }
            $sCmd = $sParameters;
        }

        if ($bBackgroundJob) {
            $sTmpPath = tempnam($this->sTmpDir, 'paralleljob_');
            $sCmd = "( $sCmd 1>$sTmpPath 2>&1 ) & echo \$! && sleep .1";
        }

        $iExitCode = 0;
        $sOutputPath = tempnam($this->sTmpDir, 'stdout-');
        try {
            $sStdOut = $this->exec($sCmd, $bStripBashColors, $sOutputPath);
        } catch (\RuntimeException $oException) {
            if (substr($oException->getMessage(), 0, strlen('Exit code not null: ')) == 'Exit code not null: ') {
                $sStdOut = $this->stripBashColors(rtrim(file_get_contents($sOutputPath)), $bStripBashColors);
                $iExitCode = $oException->getCode();
            } else {
                throw $oException;
            }
        }

        $sAnyDate   = '2012-07-18 15:01:46 32cs';
        $sAnyExecId = '20120718150145_17543';

        $sExecId = '';
        $sScriptName = '';
        $sSupervisorInfoPath = $this->sTmpDir . '/supervisor.info.log';
        if (file_exists($sSupervisorInfoPath)) {
            $sSupervisorInfo = $this->stripBashColors(implode('', file($sSupervisorInfoPath)), $bStripBashColors);
        } else {
            $sSupervisorInfo = '';
        }
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
            'exit_code'               => $iExitCode,
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

    /**
     * Exécute du code appelant et retourne la sortie d'exécution sous forme d'une chaîne de caractères.
     * L'éventuelle coloration Shell est enlevée.
     * Les fichiers de configuration Shell sont préalablement chargés.
     *
     * En cas d'erreur shell (code d'erreur <> 0), lance une exception incluant le message d'erreur.
     *
     * Par exemple : $this->shellCodeCall('process_options x -aV; isset_option a; echo \$?');
     * Attention à l'échappement des dollars ($).
     *
     * @param string $sCmd
     * @param bool $bStripBashColors Supprime ou non la coloration Bash de la chaîne retournée
     * @return string sortie d'exécution sous forme d'une chaîne de caractères.
     * @throws \RuntimeException en cas d'erreur shell
     */
    protected function shellCodeCall ($sCmd, $bStripBashColors = true)
    {
        $sShellCodeCall = BASH_PATH . ' ' . TESTS_DIR . '/inc/testShellCode.sh "' . $sCmd . '"';
        return $this->exec($sShellCodeCall, $bStripBashColors);
    }

    protected function exec ($sCmd, $bStripBashColors = true, $sOutputPath = '')
    {
        try {
            $aResult = Helpers::exec($sCmd, $sOutputPath);
        } catch (\RuntimeException $oException) {
            if ($oException->getMessage() != '') {
                $sMsg = $this->stripBashColors($oException->getMessage(), $bStripBashColors);
            } else {
                $sMsg = '-- no message --';
            }
            throw new \RuntimeException($sMsg, $oException->getCode(), $oException);
        }
        $sResult = $this->stripBashColors(rtrim(implode("\n", $aResult)), $bStripBashColors);
        return $sResult;
    }
}

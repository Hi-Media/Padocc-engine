#!/usr/bin/env php
<?php

namespace Himedia\Padocc;

use GetOptionKit\GetOptionKit;

require(__DIR__ . '/inc/bootstrap.php');
/** @var $aConfig array */
/** @var $argv array */

/**
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
 */

// Set options:
$sActions = 'deploy, deploy-wos, dequeue, enqueue, get-env, get-status, get-queue, get-latest-deployments';
$oGetopt = new GetOptionKit();
$oGetopt->add('action:', "Choose between: $sActions");
$oGetopt->add('env:', 'Project\'s environment to deploy');
$oGetopt->add('project:', 'Project\'s name');
$oGetopt->add('exec-id:', 'Execution Id');
$oGetopt->add('p|param+', 'External parameters');
$oGetopt->add('xml:', 'XML project configuration');
//$oGetopt->printOptions();

// Extract command line parameters
$aCLIParameters    = $oGetopt->parse($argv);
$sAction           = (isset($aCLIParameters['action'])  ? $aCLIParameters['action']->value  : '');
$sXmlProjectPath   = (isset($aCLIParameters['xml'])     ? $aCLIParameters['xml']->value     : '');
$aRawExtParameters = (isset($aCLIParameters['param'])   ? $aCLIParameters['param']->value   : array());
$sProjectName      = (isset($aCLIParameters['project']) ? $aCLIParameters['project']->value : '');
$sEnvName          = (isset($aCLIParameters['env'])     ? $aCLIParameters['env']->value     : '');
$sExecId           = (isset($aCLIParameters['exec-id']) ? $aCLIParameters['exec-id']->value : '');
$aExtParameters = array();
foreach ($aRawExtParameters as $sData) {
    list($sName, $sValue) = explode('=', $sData, 2);
    if (preg_match("/^('|\").+\\1$/", $sValue) === 1) {
        $sValue = substr($sValue, 1, -1);
    }
    $aExtParameters[$sName] = $sValue;
}
$sRollbackID = '';

$oPadocc = new Padocc($aConfig);

// Controller:
switch ($sAction) {
    case 'get-env':
        $aEnv = $oPadocc->getEnvAndExtParameters($sXmlProjectPath);
        var_dump($aEnv);
        break;

    case 'get-status':
        $sContent = $oPadocc->getStatus($sExecId);
        var_dump($sContent);
        break;

    case 'get-queue':
        $sContent = $oPadocc->getQueueAndRunning();
        var_dump($sContent);
        break;

    case 'get-latest-deployments':
        $sContent = $oPadocc->getLatestDeployments($sProjectName, $sEnvName);
        var_dump($sContent);
        break;

    case 'deploy':
        $oPadocc->run($sXmlProjectPath, $sEnvName, $sExecId, $aExtParameters, $sRollbackID);
        break;

    case 'deploy-wos':
        $oPadocc->runWOSupervisor($sXmlProjectPath, $sEnvName, $sExecId, $aExtParameters, $sRollbackID);
        break;

    case 'enqueue':
        $sExecId = $oPadocc->enqueue($sXmlProjectPath, $sEnvName, $aExtParameters);
        var_dump($sExecId);
        break;

    case 'dequeue':
        $oPadocc->dequeue();
        break;

    default:
        $sMsg = "Must choose action between following: $sActions!\n";
        file_put_contents('php://stderr', $sMsg, E_USER_ERROR);
}


// /usr/bin/php -q ~/deployment/deployment.php tests tests_gitexport v4.12.0 `date +'%Y%m%d%H%M%S'` \
//		/tmp/deployment.php.xxx.error.log

// CRON * * * * * date +\%s > /home/gaubry/cron_heartbeat.txt

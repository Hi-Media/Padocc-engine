#!/usr/bin/php
<?php

// TODO vérifier présence de /var/log/apache2/error.padocc.log et access.padocc.log
// 192.168.27.103  padocc.hi-media-techno.com

namespace Himedia\Padocc;

use GAubry\Logger\ColoredIndentedLogger;
use GAubry\Shell\ShellAdapter;
use GetOptionKit\GetOptionKit;
use Himedia\Padocc\Task\Base\Target;

require(__DIR__ . '/inc/bootstrap.php');
/** @var $aConfig array */

// Set options:
$oGetopt = new GetOptionKit();
$oGetopt->add('xml:', 'XML project configuration');
$oGetopt->add('env:', 'Project\'s environment to deploy');
$oGetopt->add('p|param+', 'External parameters');
$oGetopt->add('deploy', 'Deploy specified project');
$oGetopt->add('get-env', 'Get project\'s environments and their external parameters');
//$oGetopt->printOptions();

// Extract command line parameters
$aCLIParameters = $oGetopt->parse($argv);
$sXmlProjectPath = $aCLIParameters['xml']->value;
$aRawExtParameters = (isset($aCLIParameters['param']) ? $aCLIParameters['param']->value : array());
$aExtParameters = array();
foreach ($aRawExtParameters as $sData) {
    list($sName, $sValue) = explode('=', $sData, 2);
    $aExtParameters[$sName] = $sValue;
}
$sEnvName = $aCLIParameters['env']->value;
$sExecutionID = 0;
$sRollbackID = '';

// Controller:
if (isset($aCLIParameters['get-env'])) {
    $aEnv = Target::getAvailableEnvsList($sXmlProjectPath);
    var_dump($aEnv);

} elseif (isset($aCLIParameters['deploy'])) {
    // Build dependency injection container
    $oLogger      = new ColoredIndentedLogger($aConfig['GAubry\Logger\ColoredIndentedLogger']);
    $oShell       = new ShellAdapter($oLogger);
    $oDIContainer = new DIContainer();
    $oDIContainer
        ->setLogger($oLogger)
        ->setShellAdapter($oShell)
        ->setPropertiesAdapter(new Properties\Adapter($oShell, $aConfig['GAubry\Logger\ColoredIndentedLogger']))
        ->setNumberingAdapter(new Numbering\Adapter())
        ->setConfig($aConfig['Himedia\Padocc']);

    $oDeployment = new Deployment($oDIContainer);
    $oDeployment->run($sXmlProjectPath, $sEnvName, $sExecutionID, $aExtParameters, $sRollbackID);

} else {
    $sMsg = "Must choose action between following: deploy, get-env!\n";
    file_put_contents('php://stderr', $sMsg, E_USER_ERROR);
}


// /usr/bin/php -q ~/deployment/deployment.php tests tests_gitexport v4.12.0 `date +'%Y%m%d%H%M%S'` \
//		/tmp/deployment.php.xxx.error.log

// CRON * * * * * date +\%s > /home/gaubry/cron_heartbeat.txt

#!/usr/bin/env php
<?php

// OK: /var/log/apache2/error.padocc.log et access.padocc.log
// 192.168.27.103  padocc.hi-media-techno.com

/*
 * TODO lors de l'install :
        – ssh indefero.hi-media-techno.com => pour ajouter indefero aux .ssh/known_hosts
        – ajouter user padocc dans Aura pour les projets concernés
        – ajouter padocc dans indefero sur les projets concernés
        – créer conf/padocc-ssh contenant la clé privée SSH
        – installer mutt: apt-get install mutt
 */

/*
 * Déploiement de Padocc sur redmine :
rsync -axz --delete --stats -e ssh --cvs-exclude --exclude=conf/padocc.php --exclude=conf/padocc-ssh --exclude=conf/supervisor.sh \
    /home/gaubry/PhpstormProjects/Padocc-engine/ \
    gaubry@192.168.27.103:/var/www/padocc/ \
&& ssh gaubry@192.168.27.103 "sudo mkdir -p /var/padocc/archives /var/padocc/repositories /var/log/padocc /tmp/padocc/locks"
 */

/*
 * Test de déploiement :
root# src/padocc.php --deploy --xml=/home/gaubry/dw-payment.xml --env=dev -p ref="stable" --param=u=i=g
 */

/*
 * Apache2 VirtualHost: /etc/apache2/sites-available/padocc.hi-media-techno.com
<VirtualHost 127.0.0.1:80>
        ServerName padocc.hi-media-techno.com

        DocumentRoot /var/www/padocc/web
        <Directory /var/www/padocc/web >
                Options FollowSymLinks  MultiViews -Indexes
                AllowOverride None
                Order allow,deny
                allow from all
        </Directory>

        ErrorLog /var/log/apache2/error.padocc.log
        CustomLog /var/log/apache2/access.padocc.log combined
</VirtualHost>
 */

namespace Himedia\Padocc;

use GetOptionKit\GetOptionKit;

require(__DIR__ . '/inc/bootstrap.php');
/** @var $aConfig array */
/** @var $argv array */

// Set options:
$oGetopt = new GetOptionKit();
$oGetopt->add('xml:', 'XML project configuration');
$oGetopt->add('env:', 'Project\'s environment to deploy');
$oGetopt->add('action:', 'Choose between: deploy, deploy-wos, enqueue, get-env');
$oGetopt->add('p|param+', 'External parameters');
//$oGetopt->printOptions();

// Extract command line parameters
$aCLIParameters = $oGetopt->parse($argv);
$sXmlProjectPath = $aCLIParameters['xml']->value;
$aRawExtParameters = (isset($aCLIParameters['param']) ? $aCLIParameters['param']->value : array());
$aExtParameters = array();
foreach ($aRawExtParameters as $sData) {
    list($sName, $sValue) = explode('=', $sData, 2);
    if (preg_match("/^('|\").+\\1$/", $sValue) === 1) {
        $sValue = substr($sValue, 1, -1);
    }
    $aExtParameters[$sName] = $sValue;
}
$sEnvName = $aCLIParameters['env']->value;
$sExecutionID = 0;
$sRollbackID = '';

$oPadocc = new Padocc($aConfig);

// Controller:
if ($aCLIParameters['action']->value == 'get-env') {
    $aEnv = $oPadocc->getEnvAndExtParameters($sXmlProjectPath);
    var_dump($aEnv);

} elseif ($aCLIParameters['action']->value == 'deploy') {
    $oPadocc->run($sXmlProjectPath, $sEnvName, $sExecutionID, $aExtParameters, $sRollbackID);
//    $sOutput = Helpers::exec($sCmd);

} elseif ($aCLIParameters['action']->value == 'deploy-wos') {
    $oPadocc->runWOSupervisor($sXmlProjectPath, $sEnvName, $sExecutionID, $aExtParameters, $sRollbackID);

} elseif ($aCLIParameters['action']->value == 'enqueue') {
    $oPadocc->enqueue($sXmlProjectPath, $sEnvName, $aExtParameters);

} else {
    $sMsg = "Must choose action between following: deploy, deploy-wos, get-env!\n";
    file_put_contents('php://stderr', $sMsg, E_USER_ERROR);
}


// /usr/bin/php -q ~/deployment/deployment.php tests tests_gitexport v4.12.0 `date +'%Y%m%d%H%M%S'` \
//		/tmp/deployment.php.xxx.error.log

// CRON * * * * * date +\%s > /home/gaubry/cron_heartbeat.txt

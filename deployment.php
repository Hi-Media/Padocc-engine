<?php

// /usr/bin/php -q ~/deployment/deployment.php tests all_tests `date +'%Y%m%d%H%M%S'` /tmp/deployment.php.xxx.error.log
// tail -fn 500 /tmp/deployment.php.xxx.error.log
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/test/dest/*
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/deployment_test/*

// PHPUnit : aller dans /tests, puis : phpunit
// http://gaubry.dev.twenga.com/deployment/report/index.html

// ln -s /home/gaubry/deployment/deployment.php /home/gaubry/supervisor/scripts/php/deployment.php
// CRON * * * * * date +\%s > /home/gaubry/cron_heartbeat.txt

// BOC : deploy/wudEPR3c
// Sur deploy-02 : mysql -u supervisor -ps53eBREw supervisor -h localhost

// alias mprodaai='mysql -u aai -pa19AI03 aai -h maai-01.twenga.com --prompt="maai-01.twenga.com:aai>"'
// alias mdevaai='mysql -u twengadevb2c -ptopprodu devaai1 -h supermicro04 --prompt="supermicro04:devaai1>"'

// cp /home/prod/backup_extranet.twenga.com/inc/config.php /home/httpd/extranet.twenga.com/inc
// cp /home/prod/backup_extranet.twenga.com/fwt/config.php /home/httpd/extranet.twenga.com/fwt

/*
chmod +r supervisor -R
chmod +r deployment -R
chmod 777 supervisor/logs -R
chmod 777 deployment/resources -R
*/

// TODO implémenter rollback
// TODO si fatal error, demander au supervisor de proposer un rollback ?
// TODO s'assurer que si jamais Deployment plante sans envoyer de mail, alors Supervisor le fera !
// TODO description des tâches
// TODO xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="biblio10.xsd"
// TODO les sous-target pourraient être préfixées par un underscore
// TODO passer l'ID du superviseur sur un timestamp+random
// TODO passer les types de paramètres de tâches en champ de bits
// TODO mieux différencier les différents types d'exception
// TODO mettre en forme le retour des appels rsync ?
// TODO Clarification de l'affichage : début et fin de tâche, de target
// TODO Que doit retourner Shell_Adapter->exec() : array ou string ?
// TODO test multi rsync
// TODO Le chemin menant au fichier de configuration INI ou CFG est en absolu pour l'instant. Le passer en relatif ?
// TODO imposer des attributs desc et les remonter dans le nom des tâches ?
// TODO détester call cycliques !
// TODO initProperties() présent dans Target.class.php et Call.class.php...
// TODO surveiller SUPERVISOR_ERROR_LOG_FILE
// TODO AAI MAILTO
// TODO archivage des logs
// TODO connecteur CVS ne gère que le trunk
// TODO remonter heartbeat dans AAI
// TODO add cron supervisor errors
// TODO langues : https://admin.twenga.com/translation_tool/build_language_files2.php?project=rts
// TODO lib cURL : see curl_setopt_array
// TODO AAI bien gérer qd second ajout (projet, env) refusé.
// TODO migrer Gitexport et Cvsexport dans task/extended ET avec CamelCase
// TODO multi rsync n'effectue pas les mkdir en parallèle
// TODO pas de gestion robuste des erreurs qd appel direct (par AAI) de deployment.php. ex: php /home/aai/deployment/deployment.php --getProjectsEnvsList
// TODO valeur par défaut pour les attributs ?
// TODO tout comme on log les "Check '1.1_Task_Base_Gitexport' task...", on pourrait logger les run()...

/*
~/.muttrc
my_hdr From: AA Supervisor <devaa@twenga.com>
my_hdr Reply-To: Dev AA <devaa@twenga.com>

echo "message" | mutt -e "set content_type=text/html" -s "subject" -- geoffroy.aubry@twenga.com
 */

/*
 * Features :
 *  - load sh config files
 *  - XML project config file
 *  - handling all errors and exception
 *  - task très concises et intelligentes
 */


include_once(__DIR__ . '/conf/config.inc.php');
include_once(DEPLOYMENT_LIB_DIR . '/error.inc.php');
include_once(DEPLOYMENT_LIB_DIR . '/bootstrap.inc.php');

if (function_exists('xdebug_disable')) {
	xdebug_disable();
}

// On supprime le 1er paramètre correspondant au nom du script courant :
$argc--;
array_shift($argv);

if ($argc == 1 && $argv[key($argv)] === "--getProjectsEnvsList") {
	errorInit(0, null);
	$oDeployment = new Deployment();
	$aProjectsEnvsList = $oDeployment->getProjectsEnvsList();
	echo json_encode($aProjectsEnvsList);
} else if ($argc < 4) {
	file_put_contents('php://stderr', 'Missing parameters! Example: /usr/bin/php -q ~/deployment/deployment.php project1 dev 20110518121106 /tmp/deployment.php.20110518121106.error.log', E_USER_ERROR);
	exit(1);
} else {
	$sErrorLogFile = $argv[count($argv)-1];
	$sExecutionID = $argv[count($argv)-2];
	$sProjectName = $argv[0];
	$sEnvName = $argv[1];

	errorInit(0, $sErrorLogFile);
	$oDeployment = new Deployment();
	$oDeployment->run($sProjectName, $sEnvName, $sExecutionID);
}

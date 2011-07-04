<?php

// /usr/bin/php -q ~/deployment/deployment.php tests all_tests `date +'%Y%m%d%H%M%S'` /tmp/deployment.php.xxx.error.log
// tail -fn 500 /tmp/deployment.php.xxx.error.log
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/test/dest/*
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/deployment_test/*
// PHPUnit : aller dans /tests, puis : phpunit
// ln -s /home/gaubry/deployment/deployment.php /home/gaubry/supervisor/scripts/php/deployment.php

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

<?php

// /usr/bin/php -q ~/deployment/scripts/php/deployment.php tests all_tests `date +'%Y%m%d%H%M%S'` /home/gaubry/deployment/logs/deployment.php.xxx.error.log
// tail -fn 500 /home/gaubry/deployment/logs/deployment.php.xxx.error.log
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/test/dest/*
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/deployment_test/*

// TODO implémenter rollback
// TODO si fatal error, demander au supervisor de proposer un rollback ?
// TODO description des tâches
// TODO classe log !! notamment pour ne plus avoir d'affichage en unittest, et éventuellement un autre affiche si dans superviseur
// TODO xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="biblio10.xsd"
// TODO PHPUnit : bootstrap
// TODO les sous-target pourraient être préfixées par un underscore
// TODO passer l'ID du superviseur sur un timestamp+random
// TODO passer les types de paramètres de tâches en champ de bits
// TODO mieux différencier les différents types d'exception
// TODO mettre en forme le retour des appels rsync ?
// TODO Clarification de l'affichage : début et fin de tâche, de target
// TODO Que doit retourner Shell->exec() : array ou string ?

/*
 * Features :
 *  - load sh config files
 *  - recursive rollback (coming soon)
 *  - XML project config file
 *  - handling all errors
 */


include_once(__DIR__ . '/deployment/conf/config.inc.php');
include_once(DEPLOYMENT_INC_DIR . '/error.inc.php');

if (function_exists('xdebug_disable')) {
	xdebug_disable();
}

set_include_path(
	DEPLOYMENT_CORE_DIR . '/' . PATH_SEPARATOR
	. DEPLOYMENT_INC_DIR . '/' . PATH_SEPARATOR
	. get_include_path());
spl_autoload_register(function($sClass) {
	$sPath = str_replace('_', '/', $sClass) . '.class.php';
	$iPos = strrpos($sPath, '/');
	$sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);
	include_once($sPath);
});

// On supprime le 1er paramètre correspondant au nom du script courant :
$argc--;
array_shift($argv);

if ($argc < 4) {
	throw new Exception('Example: /usr/bin/php -q ~/deployment/scripts/php/deployment.php project1 dev 20110518121106 /home/gaubry/deployment/logs/deployment.php.20110518121106.error.log');
} else {
	$sErrorLogFile = $argv[count($argv)-1];
	$sExecutionID = $argv[count($argv)-2];
	$sProjectName = $argv[0];
	$sTargetName = $argv[1];
}

errorInit(0, $sErrorLogFile);

new Deployment($sProjectName, $sTargetName, $sExecutionID);

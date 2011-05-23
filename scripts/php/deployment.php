<?php

// /usr/bin/php -q ~/deployment/scripts/php/deployment.php tests all_tests `date +'%Y%m%d%H%M%S'` /home/gaubry/deployment/logs/deployment.php.xxx.error.log
// tail -fn 500 /home/gaubry/deployment/logs/deployment.php.xxx.error.log
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/test/dest/*
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/deployment_test/*

// TODO implémenter rollback
// TODO si fatal error, demander au supervisor de proposer un rollback ?
// TODO permettre fournir ini ou cfg à l'appel
// TODO description des tâches
// TODO classe log

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

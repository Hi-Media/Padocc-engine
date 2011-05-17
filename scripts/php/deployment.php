<?php

// /usr/bin/php -q ~/deployement/

include_once(__DIR__ . '/deployment/conf/config.inc.php');
include_once(DEPLOYMENT_CORE_DIR . '/Shell.class.php');
include_once(DEPLOYMENT_CORE_DIR . '/Deployment.class.php');

// On supprime le 1er paramètre correspondant au nom du script courant :
$argc--;
array_shift($argv);

set_include_path(DEPLOYMENT_CORE_DIR . '/' . PATH_SEPARATOR . get_include_path());
spl_autoload_register(function($sClass) {
	$sPath = str_replace('_', '/', $sClass) . '.class.php';
	$iPos = strrpos($sPath, '/');
	$sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);
	include_once($sPath);
});

echo 'Parameters: ' . implode('|', $argv) . "\n";
echo 'WARNING alert!' . "\n";
echo '...' . "\n";

/*$result = Shell::exec('ls -la | wc -l');
var_dump($result);*/

if ($argc < 2) {
	throw new Exception('You must specify both a project name and a environment!');
} else {
	$sProjectName = $argv[0];
	$sEnvName = $argv[1];
	$oDeployment = new Deployment($sProjectName, $sEnvName);
}
<?php

// /usr/bin/php -q ~/deployment/scripts/php/deployment.php project1 dev

error_reporting(-1);
include_once(__DIR__ . '/deployment/conf/config.inc.php');

set_include_path(DEPLOYMENT_CORE_DIR . '/' . PATH_SEPARATOR . get_include_path());
spl_autoload_register(function($sClass) {
	$sPath = str_replace('_', '/', $sClass) . '.class.php';
	$iPos = strrpos($sPath, '/');
	$sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);
	include_once($sPath);
});

// On supprime le 1er paramètre correspondant au nom du script courant :
$argc--;
array_shift($argv);

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
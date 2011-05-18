<?php

include_once(__DIR__ . '/../conf/config.inc.php');
include_once(CLI_INC_DIR . '/error.inc.php');

// On supprime le 1er paramètre correspondant au nom du script courant :
$argc--;
array_shift($argv);

// On traite le paramètre fichier de log et l'ID d'exécution :
if ($argc < 3) {
	throw new Exception('Missing PHP script to execute or execution ID or missing error log file!');
} else {
	$argc--;
	$GLOBALS['ERROR_LOG_FILE'] = array_pop($argv);
	errorInit(0, $ERROR_LOG_FILE);

	$argc--;
	$GLOBALS['EXECUTION_ID'] = array_pop($argv);
}

// Exécution du script PHP :
$script_name = CLI_PHP_SCRIPTS_DIR . '/' . $argv[0];
include_once($script_name);
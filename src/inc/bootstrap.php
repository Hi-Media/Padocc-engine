<?php

/**
 * Bootstrap.
 *
 * @author Geoffroy AUBRY <geoffroy.aubry@hi-media.com>
 */

use GAubry\ErrorHandler\ErrorHandler;
use GAubry\Helpers\Helpers;

if (! file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    echo "\033[1m\033[4;33m/!\\\033[0;37m "
        . "You must set up the project dependencies, run the following commands:" . PHP_EOL
        . "    \033[0;33mcomposer install\033[0;37m or \033[0;33mphp composer.phar install\033[0;37m." . PHP_EOL
        . PHP_EOL
        . "If needed, to install \033[1;37mcomposer\033[0;37m locally: "
            . "\033[0;37m\033[0;33mcurl -sS https://getcomposer.org/installer | php\033[0;37m" . PHP_EOL
            . "Or check http://getcomposer.org/doc/00-intro.md#installation-nix for more information." . PHP_EOL
            . PHP_EOL;
    exit(1);
}

$oLoader = require __DIR__ . '/../../vendor/autoload.php';

// Main config:
$aDefaultConfig = require_once(__DIR__ . '/../../conf/padocc-dist.php');
if (file_exists(__DIR__ . '/../../conf/padocc.php')) {
    $aUserConfig = require_once(__DIR__ . '/../../conf/padocc.php');
} else {
    $aUserConfig = array();
}
$aConfig = Helpers::arrayMergeRecursiveDistinct($aDefaultConfig, $aUserConfig);

set_include_path(
//    $aConfig['Himedia\Padocc']['dir']['root'] . PATH_SEPARATOR .
//    $aConfig['Himedia\DW']['dir']['lib'] . PATH_SEPARATOR .
    get_include_path()
);


// Additional parameters from Supervisor: $EXECUTION_ID $SCRIPT_ERROR_LOG_FILE
// Où $EXECUTION_ID est de la forme : 20121031153331_32037
if (isset($argv) && count($argv) > 2 && preg_match('/\d{14}_\d{5}/', $argv[count($argv)-2]) === 1) {
    $aConfig['GAubry\ErrorHandler']['error_log_path'] = array_pop($argv);
    $aConfig['Himedia\DW']['exec_id'] = array_pop($argv);
    $argc -= 2;
} else {
    $aConfig['Himedia\DW']['exec_id'] = md5(microtime().rand());
}

$oErrorHandler = new ErrorHandler($aConfig['GAubry\ErrorHandler']);
$oErrorHandler->addExcludedPath(__DIR__ . '/../../vendor/corneltek');

date_default_timezone_set('UTC');

// TODO à déplacer…
// Check répertoires et fichiers de logs :
//foreach (array('log', 'tmp', 'locks', 'archives') as $sDir) {
//    $oShell->mkdir($aConfig['Himedia\Padocc']['dir'][$sDir]);
//}


<?php

define('DEPLOYMENT_ROOT_DIR', realpath(__DIR__ . '/../'));
define('DEPLOYMENT_CONF_DIR', DEPLOYMENT_ROOT_DIR . '/conf');
define('DEPLOYMENT_CORE_DIR', DEPLOYMENT_ROOT_DIR . '/core');
define('DEPLOYMENT_LIB_DIR', DEPLOYMENT_ROOT_DIR . '/lib');
define('DEPLOYMENT_TESTS_DIR', DEPLOYMENT_ROOT_DIR . '/tests');
define('DEPLOYMENT_TASKS_DIR', DEPLOYMENT_CORE_DIR . '/task');
define('DEPLOYMENT_RESOURCES_DIR', DEPLOYMENT_ROOT_DIR . '/resources');
define('DEPLOYMENT_REPOSITORIES_DIR', '$HOME/deployment_repositories');
define('DEPLOYMENT_DEBUG_MODE', 1);	// 1 ou 0
define('DEPLOYMENT_BASH_PATH', '/bin/bash');

/**
 * Nombre de secondes avant timeout lors d'une connexion SSH.
 * @var int
 * @see Shell_Interface
 */
define('DEPLOYMENT_SSH_CONNECTION_TIMEOUT', 10);

define('DEPLOYMENT_JSMIN_BIN_PATH', DEPLOYMENT_LIB_DIR . '/minifier/jsmin/jsmin');

/**
 * Chemin du répertoire temporaire système utilisable par l'application.
 * @var string
 */
define('DEPLOYMENT_TMP_DIR', '/tmp');

/**
 * Nombre maximal d'exécutions shell rsync en parallèle.
 * @var int
 * @see Shell_Interface::sync()
 */
define('DEPLOYMENT_RSYNC_MAX_NB_PROCESSES', 3);

define('DEPLOYMENT_SYMLINK_MAX_NB_RELEASES', 20);

/**
 * Suffixe concaténé au base directory pour obtenir le nom du répertoire regroupant les différentes releases.
 * @var string
 */
define('DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX', '_releases');

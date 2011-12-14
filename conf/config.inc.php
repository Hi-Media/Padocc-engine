<?php

/**
 * Répertoire racine de l'application.
 * @var string
 */
define('DEPLOYMENT_ROOT_DIR', realpath(__DIR__ . '/../'));

/**
 * Répertoire des fichiers de configuration de l'application elle-même.
 * À ne pas confondre avec DEPLOYMENT_RESOURCES_DIR.
 * @var string
 */
define('DEPLOYMENT_CONF_DIR', DEPLOYMENT_ROOT_DIR . '/conf');

/**
 * Répertoire noyau de l'application.
 * @var string
 */
define('DEPLOYMENT_CORE_DIR', DEPLOYMENT_ROOT_DIR . '/core');

/**
 * Répertoire des bibliothèques utilisées par l'application.
 * @var string
 */
define('DEPLOYMENT_LIB_DIR', DEPLOYMENT_ROOT_DIR . '/lib');

/**
 * Répertoire contenant les tests unitaires de l'application.
 * @var string
 */
define('DEPLOYMENT_TESTS_DIR', DEPLOYMENT_ROOT_DIR . '/tests');

/**
 * Répertoire des tâches de déploiement de l'application.
 * @var string
 */
define('DEPLOYMENT_TASKS_DIR', DEPLOYMENT_CORE_DIR . '/task');

/**
 * Répertoire des ressources de l'application, principalement les configurations de déploiement projet.
 * @var string
 */
define('DEPLOYMENT_RESOURCES_DIR', DEPLOYMENT_ROOT_DIR . '/resources');

/**
 * Répertoire de stockage intermédiaire des dépôts CVS ou Git.
 * @var string
 */
define('DEPLOYMENT_REPOSITORIES_DIR', '$HOME/deployment_repositories');

/**
 * Chemin vers le shell bash.
 * @var string
 */
define('DEPLOYMENT_BASH_PATH', '/bin/bash');

/**
 * Nombre de secondes avant timeout lors d'une connexion SSH.
 * @var int
 * @see Shell_Interface
 */
define('DEPLOYMENT_SSH_CONNECTION_TIMEOUT', 10);

/**
 * Chemin vers le binaire jsmin.
 * @var string
 */
define('DEPLOYMENT_JSMIN_BIN_PATH', DEPLOYMENT_LIB_DIR . '/minifier/jsmin/jsmin');

/**
 * Chemin du répertoire temporaire système utilisable par l'application.
 * @var string
 */
define('DEPLOYMENT_TMP_DIR', '/tmp');

/**
 * Nombre maximal de processus lancés en parallèle par parallelize.inc.sh.
 * @var int
 * @see Shell_Interface::parallelize()
 */
define('DEPLOYMENT_PARALLELIZATION_MAX_NB_PROCESSES', 10);

/**
 * Nombre maximal d'exécutions shell rsync en parallèle.
 * Prioritaire sur DEPLOYMENT_PARALLELIZATION_MAX_NB_PROCESSES.
 * @var int
 * @see Shell_Interface::sync()
 * @see DEPLOYMENT_PARALLELIZATION_MAX_NB_PROCESSES
 */
define('DEPLOYMENT_RSYNC_MAX_NB_PROCESSES', 5);

/**
 * Nombre maximal de déploiement à garder dans les répertoires de releases.
 * @var int
 * @see DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX
 * @see Task_Base_Environment::_removeOldestReleasesInOneDirectory
 */
define('DEPLOYMENT_SYMLINK_MAX_NB_RELEASES', 20);

/**
 * Suffixe concaténé au base directory pour obtenir le nom du répertoire regroupant les différentes releases.
 * @var string
 */
define('DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX', '_releases');

/**
 * Login du web service de génération des fichiers de langue.
 * @var string
 */
define('DEPLOYMENT_LANGUAGE_WS_LOGIN', 'translator');

/**
 * Password du web service de génération des fichiers de langue.
 * @var string
 */
define('DEPLOYMENT_LANGUAGE_WS_PASSWORD', '616DyKM3');

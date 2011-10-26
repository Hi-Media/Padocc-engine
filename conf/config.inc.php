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
 * Nombre maximal d'exécutions shell rsync en parallèle.
 * @var int
 * @see Shell_Interface::sync()
 */
define('DEPLOYMENT_RSYNC_MAX_NB_PROCESSES', 3);

/**
 * Nombre maximal de déploiement à garder dans les répertoires de release.
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

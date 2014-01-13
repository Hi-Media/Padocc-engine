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
define('DEPLOYMENT_CORE_DIR', DEPLOYMENT_ROOT_DIR . '');

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
define('DEPLOYMENT_REPOSITORIES_DIR', '/tmp/deployment_repositories');

/**
 * Chemin vers le shell bash.
 * @var string
 */
define('DEPLOYMENT_BASH_PATH', '/bin/bash');

/**
 * Nombre de secondes avant timeout lors d'une connexion SSH.
 * @var int
 * @see ShellInterface
 */
define('DEPLOYMENT_SSH_CONNECTION_TIMEOUT', 10);

/**
 * Chemin vers le binaire jsmin.
 * @var string
 */
define('DEPLOYMENT_JSMIN_BIN_PATH', DEPLOYMENT_LIB_DIR . '/Minifier/jsmin/jsmin');

/**
 * Chemin du répertoire temporaire système utilisable par l'application.
 * @var string
 */
define('DEPLOYMENT_TMP_DIR', '/tmp');

/**
 * Nombre maximal de processus lancés en parallèle par parallelize.sh.
 * @var int
 * @see ShellInterface::parallelize()
 */
define('DEPLOYMENT_PARALLELIZATION_MAX_NB_PROCESSES', 10);

/**
 * Nombre maximal d'exécutions shell rsync en parallèle.
 * Prioritaire sur DEPLOYMENT_PARALLELIZATION_MAX_NB_PROCESSES.
 * @var int
 * @see ShellInterface::sync()
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



$sRootDir = realpath(__DIR__ . '/..');
$aDirs = array(
    'root'     => $sRootDir,
    'conf'     => $sRootDir . '/conf',
    'lib'      => $sRootDir . '/lib',
    'src'      => $sRootDir . '/src',
    'inc'      => $sRootDir . '/src/inc',
    'tests'    => $sRootDir . '/tests',
    'tmp'      => '/tmp/padocc',
    'locks'    => '/tmp/padocc/locks',
    'log'      => '/var/log/padocc',
    'vendor'   => $sRootDir . '/vendor',
    'archives' => '/var/padocc/archives'
);

return array(
    'Himedia\Padocc' => array(
        /**
         * Identifiant unique d'exécution, optionnellement transmis en paramètre lors de l'appel du script.
         * @var string
         */
        'exec_id' => '',

        'dir' => $aDirs,

        /**
         * Suffixe concaténé au base directory pour obtenir le nom du répertoire regroupant les différentes releases.
         * @var string
         */
        'symlink_releases_dir_suffix' => '_releases',

        /**
         * Chemin vers le shell bash.
         * @var string
         */
        'bash_path' => '/bin/bash',

        /**
         * Chemin vers le bin cURL.
         * @var string
         */
        'curl_path' => '/usr/bin/curl',

        /**
         * Options cURL lors des appels en ligne de commande.
         * @var string
         */
        'curl_options' => '-L --silent --retry 2 --retry-delay 2 --max-time 5',

        /**
         * Nombre maximal de processus lancés en parallèle par parallelize.sh.
         * @var int
         */
        'parallelization_max_nb_processes' => 10
    ),
    'GAubry\ErrorHandler'     => array(
        'display_errors'      => true,
        'error_log_path'      => $aDirs['log'] . '/padocc.error.log',
        'error_level'         => -1,
        'auth_error_suppr_op' => false
    ),
    'GAubry\Logger\ColoredIndentedLogger' => array(
        // http://en.wikipedia.org/wiki/ANSI_escape_code#CSI_codes
        'colors' => array(
            'normal'             => '',
            'main_section'       => "\033[1;37m",
            'population'         => "\033[1;35m",
            'population_details' => "\033[2;35m",
            'interval'           => "\033[0;36m",
            'db_update'          => "\033[1;32m",
            'db_update_details'  => "\033[2;32m",
            'processing'         => "\033[0;34m",
            'warning'            => "\033[0;33m",
            'error'              => "\033[1m\033[4;33m/!\\\033[0;37m \033[1;31m",
            'debug'              => "\033[0;30m",
            'parameters'         => "\033[1;33m",
            'parameter'          => "\033[1;37m",
            'process'            => "\033[1;36m",
            'process_details'    => "\033[0;36m",
        ),
        'base_indentation'     => "\033[0;30m┆\033[0m   ",
        'indent_tag'           => '+++',
        'unindent_tag'         => '---',
        'min_message_level'    => 'info',
        'reset_color_sequence' => "\033[0m",
        'color_tag_prefix'     => 'C.'
    ),
    'GAubry\Logger\CSVLogger' => array(
        'indent_tag'        => '+++',
        'unindent_tag'      => '---',
        'min_message_level' => 'info',
        'color_tag_prefix'  => 'C.',
        'csv_delimiter'     => ',',
        'csv_enclosure'     => '"'
    ),
    'GAubry\Shell' => array(
        // (string) Path of Bash:
        'bash_path' => '/bin/bash',

        // (string) List of '-o option' options used for all SSH and SCP commands:
        'ssh_options' => '-o ServerAliveInterval=10 -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes',

        // (int) Maximal number of command shells launched simultaneously (parallel processes):
        'parallelization_max_nb_processes' => 10,

        // (int) Maximal number of parallel RSYNC (overriding 'parallelization_max_nb_processes'):
        'rsync_max_nb_processes' => 5,

        // (array) List of exclusion patterns for RSYNC command (converted into list of '--exclude <pattern>'):
        'default_rsync_exclude' => array(
            '.bzr/', '.cvsignore', '.git/', '.gitignore', '.svn/', 'cvslog.*', 'CVS', 'CVS.adm'
        )
    )
);

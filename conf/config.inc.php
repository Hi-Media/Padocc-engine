<?php

define('DEPLOYMENT_ROOT_DIR', realpath(__DIR__ . '/../'));
define('DEPLOYMENT_CONF_DIR', DEPLOYMENT_ROOT_DIR . '/conf');
define('DEPLOYMENT_CORE_DIR', DEPLOYMENT_ROOT_DIR . '/core');
define('DEPLOYMENT_LIB_DIR', DEPLOYMENT_ROOT_DIR . '/lib');
define('DEPLOYMENT_TESTS_DIR', DEPLOYMENT_ROOT_DIR . '/tests');
define('DEPLOYMENT_TASKS_DIR', DEPLOYMENT_CORE_DIR . '/task');
define('DEPLOYMENT_RESOURCES_DIR', DEPLOYMENT_ROOT_DIR . '/resources');
define('DEPLOYMENT_REPOSITORIES_DIR', '$HOME/deployment_repositories');
define('DEPLOYMENT_BACKUP_DIR', '$HOME/deployment_backup');
define('DEPLOYMENT_DEBUG_MODE', 1);	// 1 ou 0
define('DEPLOYMENT_BASH_PATH', '/bin/bash');
define('DEPLOYMENT_RSYNC_MAX_NB_PROCESSES', 3);
define('DEPLOYMENT_SYMLINK_MAX_NB_RELEASES', 20);
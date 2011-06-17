<?php

define('DEPLOYMENT_ROOT_DIR', realpath(__DIR__ . '/../'));
define('DEPLOYMENT_CONF_DIR', DEPLOYMENT_ROOT_DIR . '/conf');
define('DEPLOYMENT_CORE_DIR', DEPLOYMENT_ROOT_DIR . '/core');
define('DEPLOYMENT_INC_DIR', DEPLOYMENT_ROOT_DIR . '/inc');
define('DEPLOYMENT_TESTS_DIR', DEPLOYMENT_ROOT_DIR . '/tests');
define('DEPLOYMENT_TASKS_DIR', DEPLOYMENT_CORE_DIR . '/task');
define('DEPLOYMENT_PROJECTS_DIR', DEPLOYMENT_ROOT_DIR . '/projects');
define('DEPLOYMENT_BACKUP_DIR', '$HOME/deployment_backup');

define('DEPLOYMENT_SHELL_INCLUDE', '. ' . DEPLOYMENT_CONF_DIR . '/config.inc.sh');

define('DEPLOYMENT_DEBUG_MODE', 1);
define('DEPLOYMENT_BASH_PATH', '/bin/bash');
define('DEPLOYMENT_RSYNC_MAX_NB_PROCESSES', 3);
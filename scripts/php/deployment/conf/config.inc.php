<?php

define('DEPLOYMENT_ROOT_DIR', realpath(__DIR__ . '/../'));
define('DEPLOYMENT_CONF_DIR', DEPLOYMENT_ROOT_DIR . '/conf');
define('DEPLOYMENT_CORE_DIR', DEPLOYMENT_ROOT_DIR . '/core');
define('DEPLOYMENT_TASKS_DIR', DEPLOYMENT_CORE_DIR . '/task');
define('DEPLOYMENT_PROJECTS_DIR', DEPLOYMENT_ROOT_DIR . '/projects');

define('DEPLOYMENT_SHELL_INCLUDE', '. ' . DEPLOYMENT_CONF_DIR . '/config.inc.sh');
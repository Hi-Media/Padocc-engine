<?php

set_include_path(
	get_include_path()
	. PATH_SEPARATOR . DEPLOYMENT_CORE_DIR . '/'
	. PATH_SEPARATOR . DEPLOYMENT_LIB_DIR . '/'
);

spl_autoload_register(function($sClass) {
	$sPath = str_replace('_', '/', $sClass) . '.class.php';
	$iPos = strrpos($sPath, '/');
	$sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);
	include_once($sPath);
});

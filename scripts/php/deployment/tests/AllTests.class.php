<?php

include_once(__DIR__ . '/../..' . '/deployment/conf/config.inc.php');
set_include_path(
	DEPLOYMENT_CORE_DIR . '/' . PATH_SEPARATOR
	. DEPLOYMENT_INC_DIR . '/' . PATH_SEPARATOR
	. get_include_path());
spl_autoload_register(function($sClass) {
	$sPath = str_replace('_', '/', $sClass) . '.class.php';
	$iPos = strrpos($sPath, '/');
	$sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);
	include_once($sPath);
});

echo "XXX";
include_once(DEPLOYMENT_TESTS_DIR . '/core/task/base/CopyTest.class.php');

class AllTests {

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('YYYPHPUnit');
		$suite->addTestSuite('CopyTest');
		return $suite;
	}
}
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

class AllTests {

	public static function suite() {
		$files = self::getFiles(DEPLOYMENT_TESTS_DIR, '*Test.class.php');
		//var_dump($files);
		$suite = new PHPUnit_Framework_TestSuite('Deployment');
		foreach ($files as $file) {
			include_once($file);
			$class = basename($file, '.class.php');
			$suite->addTestSuite($class);
		}
		return $suite;
	}

	/**
	 * Recursive version of glob.
	 *
	 * @param string $path Directory to start with.
	 * @param mixed $patterns Pattern to glob for, or an array of patterns.
	 * @return array containing all pattern-matched files.
	 */
	public static function getFiles ($path, $patterns) {
		if ($path{strlen($path) - 1} == '/')
			$path = substr($path, 0, -1);
		$path = escapeshellcmd($path);

		// Get the list of all matching files currently in the directory.
		if ( ! is_array($patterns))
			$patterns = array($patterns);
		$files = array();
		foreach ($patterns as $pattern) {
			$files = array_merge($files, glob($path.'/'.$pattern, 0));
		}

		// Then get a list of all directories in this directory, and
		// run ourselves on the resulting array.  This is the
		// recursion step, which will not execute if there are no
		// directories.
		$paths = glob($path.'/*', GLOB_ONLYDIR|GLOB_NOSORT);
		foreach ($paths as $path) {
			$sub_files = self::getFiles($path, $patterns, 0);
			$files = array_merge($files, $sub_files);
		}

		return $files;
	}
}
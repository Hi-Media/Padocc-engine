<?php

include_once(__DIR__ . '/..' . '/conf/config.inc.php');
include_once(DEPLOYMENT_LIB_DIR . '/bootstrap.inc.php');

class AllTests {

	public static function suite() {
		$files = self::getFiles(DEPLOYMENT_TESTS_DIR, '*Test.class.php');
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
<?php

function errorInit ($display_errors=0, $error_log_path=NULL, $error_reporting=-1) {
	error_reporting($error_reporting);
	ini_set('display_errors', $display_errors);
	ini_set('log_errors', true);
	ini_set('html_errors', false);
	ini_set('display_startup_errors',true);
	if ($error_log_path !== NULL) {
		ini_set('error_log', $error_log_path);
	}
	ini_set('ignore_repeated_errors', true);
	ini_set('max_execution_time', 0);

	// Make sure we have a timezone for date functions.
	// It is not safe to rely on the system's timezone settings. Please use the date.timezone setting, the TZ environment variable or the date_default_timezone_set() function.
	if (ini_get('date.timezone') == '') {
		date_default_timezone_set('Europe/Paris');
	}
}

/**
 * Recence les répertories exclus du spectre de ce handler.
 * @see addExcludePathErrorHandler()
 */
$GLOBALS['_error_handler_exclude_path'] = array();

/**
 * Customized error handler function: throws an Exception with the message error if @ operator not used and error source is not in excluded paths.
 *
 * @param int $errno level of the error raised.
 * @param string $errstr the error message.
 * @param string $errfile the filename that the error was raised in.
 * @param int $errline the line number the error was raised at.
 * @return boolean true, then the normal error handler does not continues.
 * @see addExcludePathErrorHandler()
 */
function errorHandler ($errno, $errstr, $errfile, $errline) {
	global $_error_handler_exclude_path;

	static $errorType = array(
		E_ERROR => 'ERROR',
		E_WARNING => 'WARNING',
		E_PARSE => 'PARSING ERROR',
		E_NOTICE => 'NOTICE',
		E_CORE_ERROR => 'CORE ERROR',
		E_CORE_WARNING => 'CORE WARNING',
		E_COMPILE_ERROR => 'COMPILE ERROR',
		E_COMPILE_WARNING => 'COMPILE WARNING',
		E_USER_ERROR => 'USER ERROR',
		E_USER_WARNING => 'USER WARNING',
		E_USER_NOTICE => 'USER NOTICE',
		E_STRICT => 'STRICT NOTICE',
		E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
	);

	// Si l'erreur provient d'un répertoire exclu de ce handler, alors l'ignorer.
	foreach ($_error_handler_exclude_path as $excluded_path) {
		if (stripos($errfile, $excluded_path) === 0) {
			return true;
		}
	}

	if (error_reporting() == 0) {
		if (LOG_ERROR_SUPPRESSED)
			;//$debug->log("ERROR SUPRESSED WITH AN @ -- $errstr, $errfile, $errline");
	} else {
		$msg = "[from error handler] $errorType[$errno] -- $errstr, in file: '$errfile', line $errline";
		$e = new ErrorException($msg, 0, $errno, $errfile, $errline);
		if (ini_get('display_errors') == 0 && $errno != E_ERROR) {
			error_log($e);
		} else {
			throw $e;
		}
	}
	return true;
}

/**
 * Exclu un répertoire du spectre de ce handler.
 * Utile par exemple pour exclure une librairie codée en PHP4 et donc dépréciée.
 * Le '/' en fin de chaîne n'est pas obligatoire.
 *
 * @param string $path
 */
function addExcludePathErrorHandler ($path) {
	global $_error_handler_exclude_path;

	if (substr($path, -1) !== '/') {
		$path .= '/';
	}
	$path = realpath($path);
	if ( ! in_array($path, $_error_handler_exclude_path)) {
		$_error_handler_exclude_path[] = $path;
	}
}

/**
 *
 * @param Exception $e
 */
function exceptionHandler ($e) {
	if (ini_get('display_errors') == 0) {
		echo '<div class="exception_handler_message">Une erreur d\'exécution est apparue.<br />'
			. 'Nous sommes désolés pour la gêne occasionée.</div>';
	}
	_my_error_log($e);
}

/**
 *
 * @param mixed $error
 */
function _my_error_log ($error) {
	if (ini_get('display_errors') != 0) {
    	print_r($error);
	}
	if (is_array($error) || (is_object($error) && ! ($error instanceof Exception))) {
		$error = print_r($error, true);
	}
    error_log($error);
}

set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');

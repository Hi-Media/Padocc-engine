<?php

/**
 * Gestionnaire d'erreurs et d'exceptions, web ou CLI.
 *  - Transforme les erreurs en exceptions et bénéficie ainsi de la trace d'exécution.
 *  - En mode CLI, redirige les erreurs et exceptions sur le canal d'erreur (STDERR)
 *    et quitte avec le code d'erreur de l'exception ou un par défaut.
 *
 * NB : bien prendre soin en mode CLI lorsque l'on crée des exceptions de spécifier
 * un code d'erreur non nul. Exemple : new Exception('...', 1)
 *
 * TODO mieux gérer opérateur @
 * TODO mieux gérer level error à partir duquel convertir en exception
 * TODO parler du excluded path (Smarty, AdoDB)
 * TODO conventions codage Twenga
 * TODO attention errorHandler() stop le script qd exception, sinon juste error_log()
 * TODO SPL
 * TODO shutdown function pour fatals ?
 *
 * @author Geoffroy AUBRY
 */
class ErrorHandler {
	public static $errorTypes = array(
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

	private static $iDefaultErrorCode = 1;

	private $display_errors;
	private $error_log_path;
	private $error_reporting;
	private $bIsRunningFromCLI;

	/**
	 * Recense les répertoires exclus du spectre de ce handler.
	 *
	 * @var array
	 * @see addExcludedPath()
	 */
	private $excluded_paths;

	public function __construct ($display_errors=true, $error_log_path='', $error_reporting=-1) {
		$this->display_errors = $display_errors;
		$this->error_log_path = $error_log_path;
		$this->error_reporting = $error_reporting;
		$this->excluded_paths = array();
		$this->bIsRunningFromCLI = defined('STDIN');

		error_reporting($error_reporting);
		ini_set('display_errors', $display_errors);
		ini_set('log_errors', true);
		ini_set('html_errors', false);
		ini_set('display_startup_errors',true);
		if ($error_log_path !== '') {
			ini_set('error_log', $error_log_path);
		}
		ini_set('ignore_repeated_errors', true);
		ini_set('max_execution_time', 0);

		// Make sure we have a timezone for date functions.
		// It is not safe to rely on the system's timezone settings. Please use the date.timezone setting, the TZ environment variable or the date_default_timezone_set() function.
		if (ini_get('date.timezone') == '') {
			date_default_timezone_set('Europe/Paris');
		}

		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));
	}

	/**
	 * Exclu un répertoire du spectre de ce handler.
	 * Utile par exemple pour exclure une librairie codée en PHP4 et donc dépréciée.
	 * Le '/' en fin de chaîne n'est pas obligatoire.
	 *
	 * @param string $path
	 */
	public function addExcludedPath ($path) {
		if (substr($path, -1) !== '/') {
			$path .= '/';
		}
		$path = realpath($path);
		if ( ! in_array($path, $this->excluded_paths)) {
			$this->excluded_paths[] = $path;
		}
	}

	/**
	 * Customized error handler function: throws an Exception with the message error if @ operator not used and error source is not in excluded paths.
	 *
	 * @param int $errno level of the error raised.
	 * @param string $errstr the error message.
	 * @param string $errfile the filename that the error was raised in.
	 * @param int $errline the line number the error was raised at.
	 * @return boolean true, then the normal error handler does not continues.
	 * @see addExcludedPath()
	 */
	public function errorHandler ($errno, $errstr, $errfile, $errline) {
		// Si l'erreur provient d'un répertoire exclu de ce handler, alors l'ignorer.
		foreach ($this->excluded_paths as $excluded_path) {
			if (stripos($errfile, $excluded_path) === 0) {
				return true;
			}
		}

		if (error_reporting() == 0) {
			if (LOG_ERROR_SUPPRESSED)
				;//$debug->log("ERROR SUPRESSED WITH AN @ -- $errstr, $errfile, $errline");
		} else {
			$msg = "[from error handler] " . self::$errorTypes[$errno] . " -- $errstr, in file: '$errfile', line $errline";
			$e = new ErrorException($msg, self::$iDefaultErrorCode, $errno, $errfile, $errline);
			if ( ! $this->display_errors && $errno != E_ERROR) {
				$this->error_log($e);
			} else {
				throw $e;
			}
		}
		return true;
	}

	/**
	 *
	 * @param Exception $e
	 */
	public function exceptionHandler (Exception $e) {
		if ( ! $this->display_errors && ini_get('error_log') !== '' && ! $this->bIsRunningFromCLI) {
			echo '<div class="exception_handler_message">Une erreur d\'exécution est apparue.<br />'
				. 'Nous sommes désolés pour la gêne occasionée.</div>';
		}
		$this->error_log($e);
	}

	/**
	 *
	 * @param mixed $error
	 */
	public function error_log ($error) {
		if ($this->display_errors) {
			if ($this->bIsRunningFromCLI) {
				file_put_contents('php://stderr', $error . "\n", E_USER_ERROR);
				$iErrorCode = ($error instanceof Exception ? $error->getCode() : self::$iDefaultErrorCode);
				exit($iErrorCode);
			} else {
				print_r($error);
			}
		}

		if ( ! empty($this->error_log_path)) {
			if (is_array($error) || (is_object($error) && ! ($error instanceof Exception))) {
				$error = print_r($error, true);
			}
		    error_log($error);
		}
	}
}

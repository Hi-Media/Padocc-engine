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
class ErrorHandler
{
    public static $aErrorTypes = array(
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

    private $bDisplayErrors;
    private $sErrorLogPath;
    private $iErrorReporting;
    private $bIsRunningFromCLI;

    /**
     * Recense les répertoires exclus du spectre de ce handler.
     *
     * @var array
     * @see addExcludedPath()
     */
    private $aExcludedPaths;

    public function __construct ($bDisplayErrors=true, $sErrorLogPath='', $iErrorReporting=-1)
    {
        $this->bDisplayErrors = $bDisplayErrors;
        $this->sErrorLogPath = $sErrorLogPath;
        $this->iErrorReporting = $iErrorReporting;
        $this->aExcludedPaths = array();
        $this->bIsRunningFromCLI = defined('STDIN');

        error_reporting($iErrorReporting);
        ini_set('display_errors', $bDisplayErrors);
        ini_set('log_errors', true);
        ini_set('html_errors', false);
        ini_set('display_startup_errors', true);
        if ($sErrorLogPath !== '') {
            ini_set('error_log', $sErrorLogPath);
        }
        ini_set('ignore_repeated_errors', true);
        ini_set('max_execution_time', 0);

        // Make sure we have a timezone for date functions. It is not safe to rely on the system's timezone settings.
        // Please use the date.timezone setting, the TZ environment variable
        // or the date_default_timezone_set() function.
        if (ini_get('date.timezone') == '') {
            date_default_timezone_set('Europe/Paris');
        }

        set_error_handler(array($this, '_errorHandler'));
        set_exception_handler(array($this, '_exceptionHandler'));
    }

    /**
     * Exclu un répertoire du spectre de ce handler.
     * Utile par exemple pour exclure une librairie codée en PHP4 et donc dépréciée.
     * Le '/' en fin de chaîne n'est pas obligatoire.
     *
     * @param string $sPath
     */
    public function addExcludedPath ($sPath)
    {
        if (substr($sPath, -1) !== '/') {
            $sPath .= '/';
        }
        $sPath = realpath($sPath);
        if ( ! in_array($sPath, $this->aExcludedPaths)) {
            $this->aExcludedPaths[] = $sPath;
        }
    }

    /**
     * Customized error handler function: throws an Exception with the message error if @ operator not used
     * and error source is not in excluded paths.
     *
     * @param int $iErrNo level of the error raised.
     * @param string $sErrStr the error message.
     * @param string $sErrFile the filename that the error was raised in.
     * @param int $iErrLine the line number the error was raised at.
     * @return boolean true, then the normal error handler does not continues.
     * @see addExcludedPath()
     */
    protected function _errorHandler ($iErrNo, $sErrStr, $sErrFile, $iErrLine)
    {
        // Si l'erreur provient d'un répertoire exclu de ce handler, alors l'ignorer.
        foreach ($this->aExcludedPaths as $sExcludedPath) {
            if (stripos($sErrFile, $sExcludedPath) === 0) {
                return true;
            }
        }

        if (error_reporting() == 0) {
            if (LOG_ERROR_SUPPRESSED)
                ;//$debug->log("ERROR SUPRESSED WITH AN @ -- $errstr, $errfile, $errline");
        } else {
            $msg = "[from error handler] " . self::$aErrorTypes[$iErrNo]
                 . " -- $sErrStr, in file: '$sErrFile', line $iErrLine";
            $oException = new ErrorException($msg, self::$iDefaultErrorCode, $iErrNo, $sErrFile, $iErrLine);
            //if ( ! $this->display_errors && $errno != E_ERROR) {
            //	$this->error_log($e);
            //} else {
                throw $oException;
            //}
        }
        return true;
    }

    /**
     *
     * @param Exception $oException
     */
    protected function _exceptionHandler (Exception $oException)
    {
        if ( ! $this->bDisplayErrors && ini_get('error_log') !== '' && ! $this->bIsRunningFromCLI) {
            echo '<div class="exception_handler_message">Une erreur d\'exécution est apparue.<br />'
                . 'Nous sommes désolés pour la gêne occasionée.</div>';
        }
        $this->errorLog($oException);
    }

    /**
     *
     * @param mixed $mError
     */
    public function errorLog ($mError)
    {
        if ($this->bDisplayErrors) {
            if ($this->bIsRunningFromCLI) {
                file_put_contents('php://stderr', $mError . "\n", E_USER_ERROR);
                $iErrorCode = ($mError instanceof Exception ? $mError->getCode() : self::$iDefaultErrorCode);
                exit($iErrorCode);
            } else {
                print_r($mError);
            }
        }

        if ( ! empty($this->sErrorLogPath)) {
            if (is_array($mError) || (is_object($mError) && ! ($mError instanceof Exception))) {
                $mError = print_r($mError, true);
            }
            error_log($mError);
        }
    }
}

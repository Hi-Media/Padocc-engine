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
 * TODO shutdown function pour fatals ?
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY
 */
class ErrorHandler
{

    /**
     * Traduction des codes d'erreurs PHP.
     * @var array
     * @see internalErrorHandler()
     */
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

    /**
     * Code d'erreur accompagnant les exceptions générées par internalErrorHandler() et log().
     * @var int
     * @see internalErrorHadler()
     * @see log()
     */
    private static $_iDefaultErrorCode = 1;

    /**
     * Doit-on afficher les erreurs (à l'écran ou dans le canal d'erreur en mode CLI).
     * @var bool
     */
    private $_bDisplayErrors;

    /**
     * Chemin du fichier de log d'erreur.
     * @var string
     */
    private $_sErrorLogPath;

    /**
     * Est-on en mode CLI.
     * @var bool
     */
    private $_bIsRunningFromCLI;

    /**
     * Recense les répertoires exclus du spectre du gestionnaire interne d'erreur.
     *
     * @var array
     * @see addExcludedPath()
     */
    private $_aExcludedPaths;

    /**
     * Constructeur.
     *
     * @param bool $bDisplayErrors affiche ou non les erreurs à l'écran ou dans le canal d'erreur en mode CLI
     * @param string $sErrorLogPath chemin du fichier de log d'erreur
     * @param int $iErrorReporting Seuil de remontée d'erreur, transmis à error_reporting()
     */
    public function __construct ($bDisplayErrors=true, $sErrorLogPath='', $iErrorReporting=-1)
    {
        $this->_bDisplayErrors = $bDisplayErrors;
        $this->_sErrorLogPath = $sErrorLogPath;
        $this->_aExcludedPaths = array();
        $this->_bIsRunningFromCLI = defined('STDIN');

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

        set_error_handler(array($this, 'internalErrorHandler'));
        set_exception_handler(array($this, 'internalExceptionHandler'));
    }

    /**
     * Exclu un répertoire du spectre du gestionnaire interne d'erreur.
     * Utile par exemple pour exclure une librairie codée en PHP4 et donc dépréciée.
     * Le '/' en fin de chaîne n'est pas obligatoire.
     *
     * @param string $sPath
     * @see internalErrorHandler()
     */
    public function addExcludedPath ($sPath)
    {
        if (substr($sPath, -1) !== '/') {
            $sPath .= '/';
        }
        $sPath = realpath($sPath);
        if ( ! in_array($sPath, $this->_aExcludedPaths)) {
            $this->_aExcludedPaths[] = $sPath;
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
    public function internalErrorHandler ($iErrNo, $sErrStr, $sErrFile, $iErrLine)
    {
        // Si l'erreur provient d'un répertoire exclu de ce handler, alors l'ignorer.
        foreach ($this->_aExcludedPaths as $sExcludedPath) {
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
            $oException = new ErrorException($msg, self::$_iDefaultErrorCode, $iErrNo, $sErrFile, $iErrLine);
            //if ( ! $this->display_errors && $errno != E_ERROR) {
            //	$this->error_log($e);
            //} else {
                throw $oException;
            //}
        }
        return true;
    }

    /**
     * Gestionnaire d'exception.
     * Log systématiquement l'erreur.
     *
     * @param Exception $oException
     * @see log()
     */
    public function internalExceptionHandler (Exception $oException)
    {
        if ( ! $this->_bDisplayErrors && ini_get('error_log') !== '' && ! $this->_bIsRunningFromCLI) {
            echo '<div class="exception_handler_message">Une erreur d\'exécution est apparue.<br />'
                . 'Nous sommes désolés pour la gêne occasionée.</div>';
        }
        $this->log($oException);
    }

    /**
     * Log l'erreur spécifiée dans le fichier de log si défini.
     * Si l'affichage des erreurs est activé, alors envoi l'erreur sur le canal d'erreur en mode CLI,
     * ou réalise un print_r sinon.
     *
     * @param mixed $mError Erreur à loguer, tableau ou objet.
     */
    public function log ($mError)
    {
        if ($this->_bDisplayErrors) {
            if ($this->_bIsRunningFromCLI) {
                file_put_contents('php://stderr', $mError . "\n", E_USER_ERROR);
                $iErrorCode = ($mError instanceof Exception ? $mError->getCode() : self::$_iDefaultErrorCode);
                exit($iErrorCode);
            } else {
                print_r($mError);
            }
        }

        if ( ! empty($this->_sErrorLogPath)) {
            if (is_array($mError) || (is_object($mError) && ! ($mError instanceof Exception))) {
                $mError = print_r($mError, true);
            }
            error_log($mError);
        }
    }
}

<?php

/**
 * Ajoute la notion d'indentation au log de messages de base.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY
 */
class Logger_IndentedDecorator implements Logger_IndentedInterface
{

    /**
     * Logger sous-jacent.
     * @var Logger_Interface
     */
    private $_oLogger;

    /**
     * Valeur d'un niveau d'indentation.
     * @var string
     */
    private $_sBaseIndentation;

    /**
     * Niveau de l'indentation courante (commence à 0).
     * @var int
     */
    private $_iIndentationLevel;

    /**
     * Constructeur.
     *
     * @param Logger_Interface instance de logger sous-jacente
     * @param string $sBaseIndentation chaîne correspondant à une identation
     */
    public function __construct (Logger_Interface $oLogger, $sBaseIndentation)
    {
        $this->_oLogger = $oLogger;
        $this->_sBaseIndentation = $sBaseIndentation;
        $this->_iIndentationLevel = 0;
    }

    /**
     * Log le message spécifié si son importance égale au moins le seuil transmis au constructeur,
     * et le fait précéder de la chaîne correspondant au niveau d'indentation courant.
     *
     * @param string $sMessage message à loguer
     * @param int $iLevel importance du message
     * @return Logger_Interface $this
     */
    public function log ($sMessage, $iLevel=self::INFO)
    {
        $sDecoratedMessage = str_repeat($this->_sBaseIndentation, $this->_iIndentationLevel) . $sMessage;
        return $this->_oLogger->log($sDecoratedMessage, $iLevel);
    }

    /**
     * Ajoute un niveau d'indentation pour tous les logs de messages à venir.
     *
     * @return Logger_IndentedInterface $this
     * @see log()
     */
    public function indent ()
    {
        $this->_iIndentationLevel++;
        return $this;
    }

    /**
     * Retire un niveau d'indentation (s'il en reste) pour tous les logs de messages à venir.
     *
     * @return Logger_IndentedInterface $this
     * @see log()
     */
    public function unindent ()
    {
        $this->_iIndentationLevel = max(0, $this->_iIndentationLevel-1);
        return $this;
    }
}

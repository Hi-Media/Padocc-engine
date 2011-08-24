<?php

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

    public function __construct (Logger_Interface $oLogger, $sBaseIndentation)
    {
        $this->_oLogger = $oLogger;
        $this->_sBaseIndentation = $sBaseIndentation;
        $this->_iIndentationLevel = 0;
    }

    public function log ($sMessage, $iLevel=self::INFO)
    {
        $sDecoratedMessage = str_repeat($this->_sBaseIndentation, $this->_iIndentationLevel) . $sMessage;
        return $this->_oLogger->log($sDecoratedMessage, $iLevel);
    }

    public function indent ()
    {
        $this->_iIndentationLevel++;
        return $this;
    }

    public function unindent ()
    {
        $this->_iIndentationLevel = max(0, $this->_iIndentationLevel-1);
        return $this;
    }
}
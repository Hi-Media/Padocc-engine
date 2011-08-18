<?php

class Logger_IndentedDecorator implements Logger_IndentedInterface
{

    /**
     * Logger sous-jacent.
     * @var Logger_Interface
     */
    private $oLogger;

    /**
     * Valeur d'un niveau d'indentation.
     * @var string
     */
    private $sBaseIndentation;

    /**
     * Niveau de l'indentation courante (commence à 0).
     * @var int
     */
    private $iIndentationLevel;

    public function __construct (Logger_Interface $oLogger, $sBaseIndentation)
    {
        $this->oLogger = $oLogger;
        $this->sBaseIndentation = $sBaseIndentation;
        $this->iIndentationLevel = 0;
    }

    public function log ($sMessage, $iLevel=self::INFO)
    {
        $sDecoratedMessage = str_repeat($this->sBaseIndentation, $this->iIndentationLevel) . $sMessage;
        $this->oLogger->log($sDecoratedMessage, $iLevel);
    }

    public function indent ()
    {
        $this->iIndentationLevel++;
    }

    public function unindent ()
    {
        $this->iIndentationLevel = max(0, $this->iIndentationLevel-1);
    }
}
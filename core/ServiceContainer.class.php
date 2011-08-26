<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY
 */
class ServiceContainer
{

    /**
     * Logger.
     * @var Logger_IndentedInterface
     */
    private $_oLogger;

    /**
     * Properties.
     * @var Properties_Interface
     */
    private $_oProperties;

    /**
     * Adaptateur Shell.
     * @var Shell_Interface
     */
    private $_oShell;

    /**
     * Adaptateur de numérotation.
     * @var Numbering_Interface
     */
    private $_oNumbering;

    public function __construct ()
    {
        $this->_oLogger = NULL;
        $this->_oProperties = NULL;
        $this->_oShell = NULL;
        $this->_oNumbering = NULL;
    }

    public function setLogAdapter (Logger_IndentedInterface $oLogger)
    {
        $this->_oLogger = $oLogger;
        return $this;
    }

    public function setPropertiesAdapter (Properties_Interface $oProperties)
    {
        $this->_oProperties = $oProperties;
        return $this;
    }

    public function setShellAdapter (Shell_Interface $oShell)
    {
        $this->_oShell = $oShell;
        return $this;
    }

    public function setNumberingAdapter (Numbering_Interface $oNumbering)
    {
        $this->_oNumbering = $oNumbering;
        return $this;
    }

    public function getLogAdapter ()
    {
        return $this->_oLogger;
    }

    public function getPropertiesAdapter ()
    {
        return $this->_oProperties;
    }

    public function getShellAdapter ()
    {
        return $this->_oShell;
    }

    public function getNumberingAdapter ()
    {
        return $this->_oNumbering;
    }
}

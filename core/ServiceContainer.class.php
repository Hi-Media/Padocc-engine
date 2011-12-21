<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
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

    /**
     * Constructeur.
     */
    public function __construct ()
    {
        $this->_oLogger = NULL;
        $this->_oProperties = NULL;
        $this->_oShell = NULL;
        $this->_oNumbering = NULL;
    }

    /**
     * Enregistre l'adapteur de log.
     *
     * @param Logger_IndentedInterface $oLogger
     * @return ServiceContainer $this
     */
    public function setLogAdapter (Logger_IndentedInterface $oLogger)
    {
        $this->_oLogger = $oLogger;
        return $this;
    }

    /**
     * Enregistre l'adapteur de propriétés.
     *
     * @param Properties_Interface $oProperties
     * @return ServiceContainer $this
     */
    public function setPropertiesAdapter (Properties_Interface $oProperties)
    {
        $this->_oProperties = $oProperties;
        return $this;
    }

    /**
     * Enregistre l'adapteur de commandes Shell.
     *
     * @param Shell_Interface $oShell
     * @return ServiceContainer $this
     */
    public function setShellAdapter (Shell_Interface $oShell)
    {
        $this->_oShell = $oShell;
        return $this;
    }

    /**
     * Enregistre l'adapteur de numérotation.
     *
     * @param Numbering_Interface $oNumbering
     * @return ServiceContainer $this
     */
    public function setNumberingAdapter (Numbering_Interface $oNumbering)
    {
        $this->_oNumbering = $oNumbering;
        return $this;
    }

    /**
     * Retourne l'adapteur de log enregistré.
     *
     * @return Logger_IndentedInterface l'adapteur de log enregistré.
     */
    public function getLogAdapter ()
    {
        return $this->_oLogger;
    }

    /**
     * Retourne l'adapteur de propriétés enregistré.
     *
     * @return Properties_Interface l'adapteur de propriétés enregistré.
     */
    public function getPropertiesAdapter ()
    {
        return $this->_oProperties;
    }

    /**
     * Retourne l'adapteur de commandes Shell enregistré.
     *
     * @return Shell_Interface l'adapteur de commandes Shell enregistré.
     */
    public function getShellAdapter ()
    {
        return $this->_oShell;
    }

    /**
     * Retourne l'adapteur de numérotation enregistré.
     *
     * @return Numbering_Interface l'adapteur de numérotation enregistré.
     */
    public function getNumberingAdapter ()
    {
        return $this->_oNumbering;
    }
}

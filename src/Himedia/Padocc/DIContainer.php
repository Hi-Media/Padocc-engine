<?php

namespace Himedia\Padocc;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Numbering\NumberingInterface;
use Himedia\Padocc\Properties\PropertiesInterface;
use Psr\Log\LoggerInterface;

/**
 * Simple container for depency injection.
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class DIContainer
{

    /**
     * Logger.
     * @var LoggerInterface
     */
    private $oLogger;

    /**
     * Properties.
     *
     * @var PropertiesInterface
     */
    private $oProperties;

    /**
     * Adaptateur Shell.
     *
     * @var ShellAdapter
     */
    private $oShell;

    /**
     * Adaptateur de numérotation.
     * @var NumberingInterface
     */
    private $oNumbering;

    /**
     * @var array
     */
    private $aConfig;

    /**
     * Constructeur.
     */
    public function __construct ()
    {
        $this->oLogger     = null;
        $this->oProperties = null;
        $this->oShell      = null;
        $this->oNumbering  = null;
        $this->aConfig     = array();
    }

    /**
     * Enregistre l'adapteur de log.
     *
     * @param LoggerInterface $oLogger
     * @return DIContainer $this
     */
    public function setLogAdapter (LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
        return $this;
    }

    /**
     * Retourne l'adapteur de log enregistré.
     *
     * @return LoggerInterface l'adapteur de log enregistré.
     * @throws \RuntimeException if no logger set.
     */
    public function getLogAdapter ()
    {
        if ($this->oLogger === null) {
            throw new \RuntimeException('No logger set!');
        }
        return $this->oLogger;
    }

    /**
     * Enregistre l'adapteur de propriétés.
     *
     * @param PropertiesInterface $oProperties
     * @return DIContainer $this
     */
    public function setPropertiesAdapter (PropertiesInterface $oProperties)
    {
        $this->oProperties = $oProperties;
        return $this;
    }

    /**
     * Retourne l'adapteur de propriétés enregistré.
     *
     * @return PropertiesInterface l'adapteur de propriétés enregistré.
     * @throws \RuntimeException if no properties instance set.
     */
    public function getPropertiesAdapter ()
    {
        if ($this->oProperties === null) {
            throw new \RuntimeException('No Properties instance set!');
        }
        return $this->oProperties;
    }

    /**
     * Enregistre l'adapteur de commandes Shell.
     *
     * @param ShellAdapter $oShell
     * @return DIContainer $this
     */
    public function setShellAdapter (ShellAdapter $oShell)
    {
        $this->oShell = $oShell;
        return $this;
    }

    /**
     * Retourne l'adapteur de commandes Shell enregistré.
     *
     * @return ShellAdapter l'adapteur de commandes Shell enregistré.
     * @throws \RuntimeException if no Shell adapter set.
     */
    public function getShellAdapter ()
    {
        if ($this->oShell === null) {
            throw new \RuntimeException('No Shell adapter set!');
        }
        return $this->oShell;
    }

    /**
     * Enregistre l'adapteur de numérotation.
     *
     * @param NumberingInterface $oNumbering
     * @return DIContainer $this
     */
    public function setNumberingAdapter (NumberingInterface $oNumbering)
    {
        $this->oNumbering = $oNumbering;
        return $this;
    }

    /**
     * Retourne l'adapteur de numérotation enregistré.
     *
     * @return NumberingInterface l'adapteur de numérotation enregistré.
     * @throws \RuntimeException if no Numbering adapter set.
     */
    public function getNumberingAdapter ()
    {
        if ($this->oNumbering === null) {
            throw new \RuntimeException('No Numbering adapter set!');
        }
        return $this->oNumbering;
    }

    /**
     * @param array $aConfig
     * @return DIContainer current instance.
     */
    public function setConfig (array $aConfig)
    {
        $this->aConfig = $aConfig;
        return $this;
    }

    /**
     * @return array
     * @throws \RuntimeException if no config set.
     */
    public function getConfig ()
    {
        if ($this->aConfig === array()) {
            throw new \RuntimeException('No config set!');
        }
        return $this->aConfig;
    }
}

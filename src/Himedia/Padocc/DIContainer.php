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
class DIContainer implements DIContainerInterface
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
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->oLogger = $logger;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if no logger set.
     */
    public function getLogger()
    {
        if ($this->oLogger === null) {
            throw new \RuntimeException('No LoggerInterface instance set!');
        }
        return $this->oLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function setPropertiesAdapter(PropertiesInterface $propertiesAdapter)
    {
        $this->oProperties = $propertiesAdapter;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if no properties instance set.
     */
    public function getPropertiesAdapter()
    {
        if ($this->oProperties === null) {
            throw new \RuntimeException('No PropertiesInterface instance set!');
        }
        return $this->oProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function setShellAdapter(ShellAdapter $shellAdapter)
    {
        $this->oShell = $shellAdapter;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if no Shell adapter set.
     */
    public function getShellAdapter()
    {
        if ($this->oShell === null) {
            throw new \RuntimeException('No ShellAdapter instance set!');
        }
        return $this->oShell;
    }

    /**
     * {@inheritdoc}
     */
    public function setNumberingAdapter(NumberingInterface $numberingAdapter)
    {
        $this->oNumbering = $numberingAdapter;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if no Numbering adapter set.
     */
    public function getNumberingAdapter()
    {
        if ($this->oNumbering === null) {
            throw new \RuntimeException('No NumberingInterface instance set!');
        }
        return $this->oNumbering;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        $this->aConfig = $config;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if no config set.
     */
    public function getConfig()
    {
        if ($this->aConfig === array()) {
            throw new \RuntimeException('No config array set!');
        }
        return $this->aConfig;
    }
}

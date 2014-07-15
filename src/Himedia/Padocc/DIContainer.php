<?php

namespace Himedia\Padocc;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Numbering\NumberingInterface;
use Himedia\Padocc\Properties\PropertiesInterface;
use Psr\Log\LoggerInterface;

/**
 * Simple container for depency injection.
 *
 *
 *
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
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
     * @param LoggerInterface $logger
     * @return self
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
     * @param PropertiesInterface $propertiesAdapter
     * @return self
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
     * @param ShellAdapter $shellAdapter
     * @return self
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
     * @param NumberingInterface $numberingAdapter
     * @return self
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
     * @param array $config
     * @return self
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

<?php

namespace Himedia\Padocc;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Numbering\NumberingInterface;
use Himedia\Padocc\Properties\PropertiesInterface;
use Psr\Log\LoggerInterface;

/**
 * ContainerInterface is the interface implemented by service container classes.
 */
interface DIContainerInterface
{
    /**
     * @param LoggerInterface $logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * @param PropertiesInterface $propertiesAdapter
     *
     * @return self
     */
    public function setPropertiesAdapter(PropertiesInterface $propertiesAdapter);

    /**
     * @return PropertiesInterface
     */
    public function getPropertiesAdapter();

    /**
     * @param ShellAdapter $shellAdapter
     *
     * @return self
     */
    public function setShellAdapter(ShellAdapter $shellAdapter);

    /**
     * @return ShellAdapter
     */
    public function getShellAdapter();

    /**
     * @param NumberingInterface $numberingAdapter
     *
     * @return self
     */
    public function setNumberingAdapter(NumberingInterface $numberingAdapter);

    /**
     * @return NumberingInterface
     */
    public function getNumberingAdapter();

    /**
     * @param array $config
     *
     * @return self
     */
    public function setConfig(array $config);

    /**
     * @return array
     */
    public function getConfig();
}

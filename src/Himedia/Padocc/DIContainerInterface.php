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
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * @return PropertiesInterface
     */
    public function getPropertiesAdapter();

    /**
     * @return ShellAdapter
     */
    public function getShellAdapter();

    /**
     * @return NumberingInterface
     */
    public function getNumberingAdapter();

    /**
     * @return array
     */
    public function getConfig();
}

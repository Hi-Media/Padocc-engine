<?php

namespace Himedia\Padocc\Tests\Minifier;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Minifier\Factory;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \Himedia\Padocc\Minifier\Factory::getInstance
     */
    public function testGetInstance_throwExceptionWhenUnknownType ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $this->setExpectedException('BadMethodCallException', "Unknown type: '65484'!");
        Factory::getInstance(65484, $oShell);
    }
}

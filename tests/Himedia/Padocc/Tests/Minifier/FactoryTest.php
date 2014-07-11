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
    public function testGetInstanceThrowExceptionWhenUnknownType ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $this->setExpectedException('BadMethodCallException', "Unknown type: '-1'!");
        Factory::getInstance(-1, $oShell);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\Factory::getInstance
     */
    public function testGetInstanceJSMin ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $oMinifier = Factory::getInstance(Factory::TYPE_JSMIN, $oShell);
        $this->assertInstanceOf('Himedia\Padocc\Minifier\MinifierInterface', $oMinifier);
    }
}

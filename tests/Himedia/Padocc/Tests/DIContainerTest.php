<?php

namespace Himedia\Padocc\Tests;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class DIContainerTest extends PadoccTestCase
{

    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $this->oDIContainer = new DIContainer();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oDIContainer = null;
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setLogger
     * @covers \Himedia\Padocc\DIContainer::getLogger
     */
    public function testLogger ()
    {
        $oInLogger = new NullLogger();
        $oOutLogger = $this->oDIContainer->setLogger($oInLogger)->getLogger();
        $this->assertEquals($oInLogger, $oOutLogger);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setPropertiesAdapter
     * @covers \Himedia\Padocc\DIContainer::getPropertiesAdapter
     */
    public function testPropertiesAdapter ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $oInProperties = new PropertiesAdapter($oShell, $this->aConfig);
        $oOutProperties = $this->oDIContainer->setPropertiesAdapter($oInProperties)->getPropertiesAdapter();
        $this->assertEquals($oInProperties, $oOutProperties);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setNumberingAdapter
     * @covers \Himedia\Padocc\DIContainer::getNumberingAdapter
     */
    public function testNumberingAdapter ()
    {
        $oInNumbering = new NumberingAdapter();
        $oOutNumbering = $this->oDIContainer->setNumberingAdapter($oInNumbering)->getNumberingAdapter();
        $this->assertEquals($oInNumbering, $oOutNumbering);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setShellAdapter
     * @covers \Himedia\Padocc\DIContainer::getShellAdapter
     */
    public function testShellAdapter ()
    {
        $oInShell = new ShellAdapter(new NullLogger());
        $oOutShell = $this->oDIContainer->setShellAdapter($oInShell)->getShellAdapter();
        $this->assertEquals($oInShell, $oOutShell);
    }
}

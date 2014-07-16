<?php

namespace Himedia\Padocc\Tests;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Psr\Log\NullLogger;

/**
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
 * @license Apache License, Version 2.0
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
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testLogger ()
    {
        $oInLogger = new NullLogger();
        $oOutLogger = $this->oDIContainer->setLogger($oInLogger)->getLogger();
        $this->assertEquals($oInLogger, $oOutLogger);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::getLogger
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testGetLoggerThrowExceptionIfNotSet ()
    {
        $this->setExpectedException('\RuntimeException', 'No LoggerInterface instance set!');
        $this->oDIContainer->getLogger();
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setPropertiesAdapter
     * @covers \Himedia\Padocc\DIContainer::getPropertiesAdapter
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testPropertiesAdapter ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $oInProperties = new PropertiesAdapter($oShell, $this->aConfig);
        $oOutProperties = $this->oDIContainer->setPropertiesAdapter($oInProperties)->getPropertiesAdapter();
        $this->assertEquals($oInProperties, $oOutProperties);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::getPropertiesAdapter
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testGetPropertiesAdapterThrowExceptionIfNotSet ()
    {
        $this->setExpectedException('\RuntimeException', 'No PropertiesInterface instance set!');
        $this->oDIContainer->getPropertiesAdapter();
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setNumberingAdapter
     * @covers \Himedia\Padocc\DIContainer::getNumberingAdapter
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testNumberingAdapter ()
    {
        $oInNumbering = new NumberingAdapter();
        $oOutNumbering = $this->oDIContainer->setNumberingAdapter($oInNumbering)->getNumberingAdapter();
        $this->assertEquals($oInNumbering, $oOutNumbering);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::getNumberingAdapter
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testGetNumberingAdapterThrowExceptionIfNotSet ()
    {
        $this->setExpectedException('\RuntimeException', 'No NumberingInterface instance set!');
        $this->oDIContainer->getNumberingAdapter();
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setShellAdapter
     * @covers \Himedia\Padocc\DIContainer::getShellAdapter
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testShellAdapter ()
    {
        $oInShell = new ShellAdapter(new NullLogger());
        $oOutShell = $this->oDIContainer->setShellAdapter($oInShell)->getShellAdapter();
        $this->assertEquals($oInShell, $oOutShell);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::getShellAdapter
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testGetShellAdapterThrowExceptionIfNotSet ()
    {
        $this->setExpectedException('\RuntimeException', 'No ShellAdapter instance set!');
        $this->oDIContainer->getShellAdapter();
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::setConfig
     * @covers \Himedia\Padocc\DIContainer::getConfig
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testConfig ()
    {
        $aInConfig = array('a' => 'b');
        $aOutConfig = $this->oDIContainer->setConfig($aInConfig)->getConfig();
        $this->assertEquals($aInConfig, $aOutConfig);
    }

    /**
     * @covers \Himedia\Padocc\DIContainer::getConfig
     * @covers \Himedia\Padocc\DIContainer::__construct
     */
    public function testGetConfigThrowExceptionIfNotSet ()
    {
        $this->setExpectedException('\RuntimeException', 'No config array set!');
        $this->oDIContainer->getConfig();
    }
}

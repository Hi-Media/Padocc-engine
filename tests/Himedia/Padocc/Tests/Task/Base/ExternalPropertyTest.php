<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\ExternalProperty;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;
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
class ExternalPropertyTest extends PadoccTestCase
{

    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Tableau indexé contenant les commandes Shell de tous les appels effectués à Shell_Adapter::exec().
     * @var array
     * @see shellExecCallback()
     */
    private $aShellExecCmds;

    /**
     * Callback déclenchée sur appel de Shell_Adapter::exec().
     * Log tous les appels dans le tableau indexé $this->aShellExecCmds.
     *
     * @param string $sCmd commande Shell qui aurait dûe être exécutée.
     * @see $aShellExecCmds
     */
    public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oLogger = new NullLogger();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')
            ->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);
        $oNumbering = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering)
            ->setConfig($this->aConfig);
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
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::__construct
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::centralExecute
     */
    public function testCentralExecuteThrowExceptionIfPropertyNotFound ()
    {
        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $oTask = ExternalProperty::getNewInstance(
            array(
                'name' => 'not_exists',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );

        $this->setExpectedException('UnexpectedValueException', "Property 'not_exists' undefined!");
        $oTask->setUp();
        $oTask->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::__construct
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::centralExecute
     */
    public function testCentralExecuteWith1Property ()
    {
        $oClass = new \ReflectionClass('\Himedia\Padocc\Properties\Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            ExternalProperty::EXTERNAL_PROPERTY_PREFIX . 'my_property' => 'value 1'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $oTask = ExternalProperty::getNewInstance(
            array(
                'name' => 'my_property',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );

        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals('value 1', $oPropertiesAdapter->getProperty('my_property'));
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::__construct
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::centralExecute
     */
    public function testCentralExecuteWithProperties ()
    {
        $oClass = new \ReflectionClass('\Himedia\Padocc\Properties\Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            ExternalProperty::EXTERNAL_PROPERTY_PREFIX . 'my_property' => 'value 1',
            ExternalProperty::EXTERNAL_PROPERTY_PREFIX . 'second' => 'other'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $oTask1 = ExternalProperty::getNewInstance(
            array(
                'name' => 'my_property',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );
        $oTask2 = ExternalProperty::getNewInstance(
            array(
                'name' => 'second',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );
        $oTask1->setUp();
        $oTask2->setUp();
        $oTask1->execute();
        $oTask2->execute();

        $this->assertEquals('value 1', $oPropertiesAdapter->getProperty('my_property'));
        $this->assertEquals('other', $oPropertiesAdapter->getProperty('second'));
    }
}

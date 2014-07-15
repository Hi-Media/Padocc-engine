<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Call;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Base\Target;
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
class CallTest extends PadoccTestCase
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
        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oLogger     = new NullLogger();
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')
            ->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));

        //$oShell = new ShellAdapter($oLogger);
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
     * @covers \Himedia\Padocc\Task\Base\Call::__construct
     */
    public function testNewThrowExceptionIfTargetNotFound ()
    {
        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<target name="my_target"></target>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $this->setExpectedException(
            'UnexpectedValueException',
            "Target 'not_exists' not found or not unique in this project!"
        );
        Call::getNewInstance(
            array('target' => 'not_exists'),
            $oMockProject,
            $this->oDIContainer
        );
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Call::__construct
     */
    public function testNewThrowExceptionIfTargetNotUnique ()
    {
        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project><target name="my_target"></target><target name="my_target"></target></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $this->setExpectedException(
            'UnexpectedValueException',
            "Target 'my_target' not found or not unique in this project!"
        );
        Call::getNewInstance(
            array('target' => 'my_target'),
            $oMockProject,
            $this->oDIContainer
        );
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Call::__construct
     */
    public function testNew ()
    {
        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project><target name="my_target"></target></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        Call::getNewInstance(
            array('target' => 'my_target'),
            $oMockProject,
            $this->oDIContainer
        );
        $oTargetTask = Target::getNewInstance(
            array('name' => 'my_target'),
            $oMockProject,
            $this->oDIContainer
        );

        $this->assertAttributeEquals(array('name' => 'my_target'), 'aAttValues', $oTargetTask);
    }
}

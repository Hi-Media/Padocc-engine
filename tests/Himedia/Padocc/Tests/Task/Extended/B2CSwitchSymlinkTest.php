<?php

namespace Himedia\Padocc\Tests\Task\Extended;

use GAubry\Logger\MinimalLogger;
use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Extended\B2CSwitchSymlink;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\LogLevel;

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
class B2CSwitchSymlinkTest extends PadoccTestCase
{

    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Project.
     * @var Project
     */
    private $oMockProject;

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
    public function shellExecCallback($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
    }

    /**
     * Tableau de tous les messages de log interceptés, regroupés par priorité.
     * @var array
     * @see logCallback()
     */
    private $aMessages;

    /**
     * Callback déclenchée sur appel de Logger_IndentedDecorator::log().
     * Log tous les appels dans le tableau indexé $this->aWarnMessages.
     *
     * @param string $sMsg message à logger.
     * @param int $iLevel priorité du message.
     * @see $aWarnMessages
     */
    public function logCallback($iLevel, $sMsg)
    {
        $this->aMessages[$iLevel][] = $sMsg;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        /* @var $oLogger MinimalLogger|\PHPUnit_Framework_MockObject_MockObject */
        $oLogger = $this->getMock('\GAubry\Logger\MinimalLogger', array('log'), array(LogLevel::ERROR));
        $oLogger->expects($this->any())->method('log')->will($this->returnCallback(array($this, 'logCallback')));
        $this->aMessages = array();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())
            ->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
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

        $this->oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oDIContainer = null;
        $this->oMockProject = null;
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\B2CSwitchSymlink::setCluster
     */
    public function testSetClusterThrowException()
    {
        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->any())->method('exec')
            ->with($this->equalTo('/home/prod/twenga/tools/wwwcluster -s server1 -d'))
            ->will($this->throwException(new \RuntimeException('bla bla', 1)));

        /* @var $oMockProperties \Himedia\Padocc\Properties\Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($oMockShell, $this->aConfig)
        );
        $oMockProperties->expects($this->any())->method('getProperty')
            ->will($this->returnValue('-'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = B2CSwitchSymlink::getNewInstance(array(), $this->oMockProject, $this->oDIContainer);

        $oClass = new \ReflectionClass($oTask);
        $oMethod = $oClass->getMethod('setCluster');
        $oMethod->setAccessible(true);

        $this->setExpectedException(
            'RuntimeException',
            'bla bla'
        );
        $oMethod->invokeArgs($oTask, array('server1', false));
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\B2CSwitchSymlink::setCluster
     */
    public function testSetClusterWithWarning()
    {
        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->any())->method('exec')
            ->with($this->equalTo('/home/prod/twenga/tools/wwwcluster -s server1 -d'))
            ->will($this->throwException(new \RuntimeException('bla bla', 2)));

        /* @var $oMockProperties \Himedia\Padocc\Properties\Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($oMockShell, $this->aConfig)
        );
        $oMockProperties->expects($this->any())->method('getProperty')
            ->will($this->returnValue('-'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = B2CSwitchSymlink::getNewInstance(array(), $this->oMockProject, $this->oDIContainer);

        $oClass = new \ReflectionClass($oTask);
        $oMethod = $oClass->getMethod('setCluster');
        $oMethod->setAccessible(true);

        $oMethod->invokeArgs($oTask, array('server1', false));
        $this->assertEquals(
            array("Remove 'server1' server from the cluster.+++", "---"),
            $this->aMessages[LogLevel::INFO]
        );
    }
}

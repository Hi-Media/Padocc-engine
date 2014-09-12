<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\HTTP;
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
class HTTPTest extends PadoccTestCase
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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $oLogger = new NullLogger();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())
            ->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));

        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);
        $oProperties->setProperty('tmpdir', $this->aConfig['dir']['tmp'] . '/deploy_' . 'YYYYMMDDHHMMSS_xxxxx');
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
     * @covers \Himedia\Padocc\Task\Base\HTTP::__construct
     * @covers \Himedia\Padocc\Task\Base\HTTP::execute
     * @covers \Himedia\Padocc\Task\Base\HTTP::preExecute
     * @covers \Himedia\Padocc\Task\Base\HTTP::centralExecute
     * @covers \Himedia\Padocc\Task\Base\HTTP::postExecute
     */
    public function testExecuteThrowExceptionIfCURLReturnErrorMsg()
    {
        $oLogger = $this->oDIContainer->getLogger();
        $oMockShell = new ShellAdapter($oLogger, $this->aConfig);
        $this->oDIContainer->setShellAdapter($oMockShell);

        $aConfig = $this->oDIContainer->getConfig();
        $aConfig['curl_options'] = '-L --silent --retry 1 --retry-delay 1 --max-time 1';
        $this->oDIContainer->setConfig($aConfig);

        /* @var $oTaskHTTP HTTP|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<http url="http://xxx" />';
        $oXML = new \SimpleXMLElement($sXML);
        $oTaskHTTP = $this->getMock(
            '\Himedia\Padocc\Task\Base\HTTP',
            array('reroutePaths'),
            array($oXML, $this->oMockProject, $this->oDIContainer)
        );
        $oTaskHTTP->expects($this->any())->method('reroutePaths')->will($this->returnArgument(0));

        $this->setExpectedException('RuntimeException');
        $oTaskHTTP->setUp();
        $oTaskHTTP->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\HTTP::execute
     * @covers \Himedia\Padocc\Task\Base\HTTP::preExecute
     * @covers \Himedia\Padocc\Task\Base\HTTP::centralExecute
     * @covers \Himedia\Padocc\Task\Base\HTTP::postExecute
     */
    public function testExecuteWithOneURL()
    {
        /* @var $oTaskHTTP HTTP|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<http url="http://www.xyz.com/index.php?a=26&amp;b=ttt" />';
        $oXML = new \SimpleXMLElement($sXML);
        $oTaskHTTP = $this->getMock(
            '\Himedia\Padocc\Task\Base\HTTP',
            array('reroutePaths'),
            array($oXML, $this->oMockProject, $this->oDIContainer)
        );
        $oTaskHTTP->expects($this->any())->method('reroutePaths')->will($this->returnArgument(0));

        $oTaskHTTP->setUp();
        $oTaskHTTP->execute();
        $aExpected = array(
            $this->aConfig['curl_path'] . ' ' . $this->aConfig['curl_options']
            . ' "http://www.xyz.com/index.php?a=26&b=ttt"'
        );
        $this->assertEquals($aExpected, $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\HTTP::execute
     * @covers \Himedia\Padocc\Task\Base\HTTP::preExecute
     * @covers \Himedia\Padocc\Task\Base\HTTP::centralExecute
     * @covers \Himedia\Padocc\Task\Base\HTTP::postExecute
     */
    public function testExecuteWithMultiURL()
    {
        /* @var $oMockProperties \Himedia\Padocc\Properties\Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('servers'))
            ->will($this->returnValue('www01 www02 www03'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        /* @var $oTaskHTTP HTTP|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<http url="http://aai.twenga.com/push.php?server=${servers}&amp;app=web" />';
        $oXML = new \SimpleXMLElement($sXML);
        $oTaskHTTP = $this->getMock(
            '\Himedia\Padocc\Task\Base\HTTP',
            array('reroutePaths'),
            array($oXML, $this->oMockProject, $this->oDIContainer)
        );
        $oTaskHTTP->expects($this->any())->method('reroutePaths')->will($this->returnArgument(0));

        $oTaskHTTP->setUp();
        $oTaskHTTP->execute();
        $sCURL = $this->aConfig['curl_path'] . ' ' . $this->aConfig['curl_options'];
        $this->assertEquals(
            array(
                $sCURL . ' "http://aai.twenga.com/push.php?server=www01&app=web"',
                $sCURL . ' "http://aai.twenga.com/push.php?server=www02&app=web"',
                $sCURL . ' "http://aai.twenga.com/push.php?server=www03&app=web"',
            ),
            $this->aShellExecCmds
        );
    }
}

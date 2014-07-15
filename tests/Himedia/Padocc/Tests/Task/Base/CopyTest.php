<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\Adapter;
use Himedia\Padocc\Task\Base\Copy;
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
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
 */
class CopyTest extends PadoccTestCase
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
        $oMockShell = $this->getMock(
            '\GAubry\Shell\ShellAdapter',
            array('exec'),
            array($oLogger, $this->aAllConfigs['GAubry\Shell'])
        );
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
     * @covers \Himedia\Padocc\Task\Base\Copy::__construct
     * @covers \Himedia\Padocc\Task\Base\Copy::check
     */
    public function testCheckWithSrcFile ()
    {
        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/srcfile', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcfile'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::__construct
     * @covers \Himedia\Padocc\Task\Base\Copy::check
     */
    public function testCheckWithSrcFileAndJoker ()
    {
        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/src*file?', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/src*file?'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::__construct
     * @covers \Himedia\Padocc\Task\Base\Copy::check
     */
    public function testCheckWithSrcDirWithoutLeadingSlash ()
    {
        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/srcdir', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir/srcdir',
            'src' => '/path/to/srcdir/*'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::__construct
     * @covers \Himedia\Padocc\Task\Base\Copy::check
     */
    public function testCheckWithSrcDirWithLeadingSlash ()
    {
        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/srcdir/', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir/srcdir',
            'src' => '/path/to/srcdir/*'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::execute
     * @covers \Himedia\Padocc\Task\Base\Copy::preExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::postExecute
     */
    public function testExecuteWithSrcFile ()
    {
        /* @var $oMockProperties Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/srcfile', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"',
            'cp -a "/path/to/srcfile" "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::execute
     * @covers \Himedia\Padocc\Task\Base\Copy::preExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::postExecute
     */
    public function testExecuteWithSrcDir ()
    {
        /* @var $oMockProperties Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/srcdir', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir/srcdir"',
            'cp -a "/path/to/srcdir/"* "/path/to/destdir/srcdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::execute
     * @covers \Himedia\Padocc\Task\Base\Copy::preExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::postExecute
     */
    public function testExecuteWithSrcFileAndJoker ()
    {
        /* @var $oMockProperties Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/src*file?', 'destdir' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"',
            'cp -a "/path/to/src"*"file"? "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Copy::execute
     * @covers \Himedia\Padocc\Task\Base\Copy::preExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Copy::postExecute
     */
    public function testExecuteWithSrcDirAndSymLinks ()
    {
        /* @var $oMockProperties Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->at(3))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(4))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(5))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(6))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Copy::getNewInstance(
            array('src' => '/path/to/srcdir', 'destdir' => 'user@server:/path/to/destdir'),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oTaskCopy->setUp();
        $oTaskCopy->execute();

        $sSshOptions = $this->aAllConfigs['GAubry\Shell']['ssh_options'];
        $sBashPath   = $this->aAllConfigs['GAubry\Shell']['bash_path'];
        $this->assertEquals(array(
            "ssh $sSshOptions -T user@server $sBashPath <<EOF\n"
                . 'mkdir -p "/path/to/destdir_releases/12345/srcdir"' . "\n"
                . 'EOF' . "\n",
            "scp $sSshOptions -rpq \"/path/to/srcdir/\"* \"user@server:/path/to/destdir_releases/12345/srcdir\""
        ), $this->aShellExecCmds);
    }
}

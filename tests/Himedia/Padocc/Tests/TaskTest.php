<?php

namespace Himedia\Padocc\Tests;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Copy;
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
class TaskTest extends PadoccTestCase
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
    public function setUp()
    {
        $oLogger     = new NullLogger();
        $oShell      = new ShellAdapter($oLogger);
        $oProperties = new PropertiesAdapter($oShell, $this->aConfig);
        $oNumbering  = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oShell)
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
     * @covers \Himedia\Padocc\Task::expandPath
     * @dataProvider dataProviderTestExpandPath
     */
    public function testExpandPath(array $aProperties, $sPath, array $aExpected)
    {
        foreach ($aProperties as $sKey => $sValue) {
            $this->oDIContainer->getPropertiesAdapter()->setProperty($sKey, $sValue);
        }
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array($sPath));
        $this->assertEquals($aExpected, $aResult);
    }

    /**
     * Data provider pour testExpandPath()
     */
    public function dataProviderTestExpandPath()
    {
        return array(
            array(array(), 'test', array('test')),
            array(array(), 'server:test', array($this->aConfig['default_remote_shell_user'] . '@server:test')),
            array(array(), '[]:test', array($this->aConfig['default_remote_shell_user'] . '@[]:test')),
            array(array(), 'user@server:test', array('user@server:test')),
            array(array(), 'http://test', array('http://test')),

            array(array('p' => 'simple_value'), 'foo${p}bar', array('foosimple_valuebar')),
            array(array('p' => '123 three values'), 'foo${p}bar', array('foo123bar', 'foothreebar', 'foovaluesbar')),
            array(
                array('p' => '123 three values', 'q' => '0 1'),
                '${p}foo${q}',
                array('123foo0', '123foo1', 'threefoo0', 'threefoo1', 'valuesfoo0', 'valuesfoo1')
            ),
            array(
                array('one' => '${two} ${three} four', 'two' => 'a b', 'three' => 'five ${two}'),
                '${one}foo',
                array('afoo', 'bfoo', 'fivefoo', 'fourfoo')
            )
        );
    }

    /**
     * @covers \Himedia\Padocc\Task::reroutePaths
     * @dataProvider dataProviderTestReroutePaths
     */
    public function testReroutePaths(array $aProperties, $aPaths, $aExpected)
    {
        foreach ($aProperties as $sKey => $sValue) {
            $this->oDIContainer->getPropertiesAdapter()->setProperty($sKey, $sValue);
        }
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );

        $oClass = new \ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array($aPaths));
        $this->assertEquals($aExpected, $aResult);
    }

    /**
     * Data provider pour testReroutePaths()
     */
    public function dataProviderTestReroutePaths()
    {
        $sBaseDir = '/path/to/basedir';
        return array(
            array(array('with_symlinks' => 'false'), array(), array()),
            array(
                array('with_symlinks' => 'false'),
                array('/path/to/my_dir', 'user@server:/other/path'),
                array('/path/to/my_dir', 'user@server:/other/path')
            ),
            array(
                array('with_symlinks' => 'true', 'basedir' => $sBaseDir, 'execution_id' => 123),
                array(),
                array()
            ),
            array(
                array('with_symlinks' => 'true', 'basedir' => $sBaseDir, 'execution_id' => 123),
                array(
                    $sBaseDir . 'a',
                    $sBaseDir . '/a',
                    '/path/to/elsewhere',
                    '/bad' . $sBaseDir,
                    'user@server:/other/path',
                    'user@server:/bad' . $sBaseDir,
                    'user@server:' . $sBaseDir,
                    'user@server:' . $sBaseDir . '/subdir'
                ),
                array(
                    $sBaseDir . 'a',
                    $sBaseDir . '/a',
                    '/path/to/elsewhere',
                    '/bad' . $sBaseDir,
                    'user@server:/other/path',
                    'user@server:/bad' . $sBaseDir,
                    'user@server:' . $sBaseDir . $this->aConfig['symlink_releases_dir_suffix'] . '/123',
                    'user@server:' . $sBaseDir . $this->aConfig['symlink_releases_dir_suffix'] . '/123/subdir'
                ),
            ),
        );
    }

    /**
     * @covers \Himedia\Padocc\Task::processPath
     * @dataProvider dataProviderTestProcessPath
     */
    public function testProcessPath(array $aProperties, $sPath, array $aExpected)
    {
        foreach ($aProperties as $sKey => $sValue) {
            $this->oDIContainer->getPropertiesAdapter()->setProperty($sKey, $sValue);
        }
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('processPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array($sPath));
        $this->assertEquals($aExpected, $aResult);
    }

    /**
     * Data provider pour testProcessPath()
     */
    public function dataProviderTestProcessPath()
    {
        $sBaseDir = '/path/to/basedir';
        $sReroutePrefix = 'user@server:' . $sBaseDir . $this->aConfig['symlink_releases_dir_suffix'] . '/123';
        return array(
            array(
                array(
                    'with_symlinks' => 'true', 'basedir' => $sBaseDir, 'execution_id' => 123,
                    'a' => '1 2', 'b' => '3 4'
                ),
                'user@server:' . $sBaseDir . '/foo${a}${b}bar',
                array(
                    "$sReroutePrefix/foo13bar",
                    "$sReroutePrefix/foo14bar",
                    "$sReroutePrefix/foo23bar",
                    "$sReroutePrefix/foo24bar"
                )
            ),
        );
    }

    /**
     * @covers \Himedia\Padocc\Task::processSimplePath
     */
    public function testProcessSimplePathThrowExceptionIfMultipleExpansion()
    {
        $sBaseDir = '/path/to/basedir';
        $aProperties = array('with_symlinks' => 'true', 'basedir' => $sBaseDir, 'execution_id' => 123, 'a' => '1 2');
        $sPath = 'user@server:' . $sBaseDir . '/foo${a}bar';

        foreach ($aProperties as $sKey => $sValue) {
            $this->oDIContainer->getPropertiesAdapter()->setProperty($sKey, $sValue);
        }
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('processSimplePath');
        $method->setAccessible(true);

        $this->setExpectedException('\RuntimeException', 'should return a single path after process:');
        $method->invokeArgs($oMockTask, array($sPath));
    }

    /**
     * @covers \Himedia\Padocc\Task::processSimplePath
     * @dataProvider dataProviderTestProcessSimplePathOk
     */
    public function testProcessSimplePathOk(array $aProperties, $sPath, $sExpected)
    {
        foreach ($aProperties as $sKey => $sValue) {
            $this->oDIContainer->getPropertiesAdapter()->setProperty($sKey, $sValue);
        }
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('processSimplePath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array($sPath));
        $this->assertEquals($sExpected, $aResult);
    }

    /**
     * Data provider pour testProcessSimplePathOk()
     */
    public function dataProviderTestProcessSimplePathOk()
    {
        $sBaseDir = '/path/to/basedir';
        $sReroutePrefix = 'user@server:' . $sBaseDir . $this->aConfig['symlink_releases_dir_suffix'] . '/123';
        return array(
            array(
                array('with_symlinks' => 'true', 'basedir' => $sBaseDir, 'execution_id' => 123),
                'user@server:' . $sBaseDir . '/foo',
                "$sReroutePrefix/foo"
            ),
        );
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     */
    public function testCheckWhenEmpty()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oMockTask->setUp();
        $oMockTask->expects($this->any())->method('check');
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     */
    public function testCheckWhenNotEmpty()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('name' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('name' => 'toto'));

        $oMockTask->setUp();
        $oMockTask->expects($this->any())->method('check');
    }

    /**
     * AttributeProperties exception well transmitted?
     * @covers \Himedia\Padocc\Task::check
     */
    public function testCheckThrowExceptionIfUnknownAttribute()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 'bar'));

        $this->setExpectedException('Exception');
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::getTagName
     */
    public function testGetTagNameThrowException()
    {
        $this->setExpectedException('RuntimeException', 'Not implemented at this level!');
        Task::getTagName();
    }

    /**
     * @covers \Himedia\Padocc\Task::getNewInstance
     */
    public function testGetNewInstanceThrowException()
    {
        /* @var $oMockProject Task\Base\Project|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $this->setExpectedException('RuntimeException', 'Not implemented at this level!');
        Task::getNewInstance(array(), $oMockProject, $this->oDIContainer);
    }

    /**
     * @covers \Himedia\Padocc\Task::getNewInstance
     * @covers \Himedia\Padocc\Task::fetchAttributes
     * @covers \Himedia\Padocc\Task::__construct
     */
    public function testGetNewInstanceOk()
    {
        /* @var $oMockProject Task\Base\Project|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oTaskCopy = Copy::getNewInstance(array('attr1' => 'v1', 'attr2' => 'v2'), $oMockProject, $this->oDIContainer);
        $this->assertAttributeEquals(array('attr1' => 'v1', 'attr2' => 'v2'), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task::registerPaths
     */
    public function testRegisterPaths()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aRegisteredPaths');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath' => AttributeProperties::DIR | AttributeProperties::FILE,
            'srcdir' => AttributeProperties::DIR,
            'srcfile' => AttributeProperties::FILE,
            'other' => 0
        ));

        $oProperty = $oClass->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath' => '/path/to/srcpath',
            'srcdir' => 'user@server:/path/to/srcdir',
            'srcfile' => '/path/to/srcfile',
            'other' => '/path/to/other',
        ));

        $oMethod = $oClass->getMethod('registerPaths');
        $oMethod->setAccessible(true);
        $oMethod->invokeArgs($oMockTask, array());
        $this->assertAttributeEquals(array(
            'user@server:/path/to/srcdir' => true,
            '/path/to/srcfile' => true,
            '/path/to/srcpath' => true
        ), 'aRegisteredPaths', '\Himedia\Padocc\Task');
    }
}

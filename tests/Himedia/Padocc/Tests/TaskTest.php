<?php

namespace Himedia\Padocc\Tests;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\PropertiesInterface;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Copy;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
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
    public function setUp ()
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
     */
    public function testExpandPath_WithSimpleString ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('test'));
        $this->assertEquals(array('test'), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::expandPath
     */
    public function testExpandPath_WithOneSimpleParameter ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('simple_value'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
        $this->assertEquals(array('foosimple_valuebar'), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::expandPath
     */
    public function testExpandPath_WithOneComplexParameter ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('123 three values'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
        $this->assertEquals(array('foo123bar', 'foothreebar', 'foovaluesbar'), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::expandPath
     */
    public function testExpandPath_WithTwoComplexParameters ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('123 three values'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('q'))
            ->will($this->returnValue('0 1'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('${p}foo${q}'));
        $this->assertEquals(array(
            '123foo0', '123foo1',
            'threefoo0', 'threefoo1',
            'valuesfoo0', 'valuesfoo1',
        ), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::expandPath
     */
    public function testExpandPath_WithMultiComplexParameters ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('one'))
            ->will($this->returnValue('${two} ${three} four'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('two'))
            ->will($this->returnValue('a b'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('three'))
            ->will($this->returnValue('five ${two}'));
        $oMockProperties->expects($this->at(3))->method('getProperty')
            ->with($this->equalTo('two'))
            ->will($this->returnValue('a b'));
        $oMockProperties->expects($this->exactly(4))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new \ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('${one}foo'));
        $this->assertEquals(array(
            'afoo',
            'bfoo',
            'fivefoo',
            'fourfoo'
        ), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     */
    public function testCheck_WhenEmpty ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * AttributeProperties exception well transmitted?
     * @covers \Himedia\Padocc\Task::check
     */
    public function testCheck_ThrowExceptionIfUnknownAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
    public function testGetTagName_ThrowException ()
    {
        $this->setExpectedException('RuntimeException', 'Not implemented at this level!');
        Task::getTagName();
    }

    /**
     * @covers \Himedia\Padocc\Task::getNewInstance
     */
    public function testGetNewInstance_ThrowException ()
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
    public function testGetNewInstance_Ok ()
    {
        /* @var $oMockProject Task\Base\Project|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oTaskCopy = Copy::getNewInstance(array('attr1' => 'v1', 'attr2' => 'v2'), $oMockProject, $this->oDIContainer);
        $this->assertAttributeEquals(array('attr1' => 'v1', 'attr2' => 'v2'), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task::reroutePaths
     */
    public function testReroutePaths_WithoutSymlinksWithEmptyPath ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new \ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array()));
        $this->assertEquals(array(), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::reroutePaths
     */
    public function testReroutePaths_WithoutSymlinks ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new \ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array('/path/to/my_dir', 'user@server:/other/path')));
        $this->assertEquals(array('/path/to/my_dir', 'user@server:/other/path'), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::reroutePaths
     */
    public function testReroutePaths_WithSymlinksWithEmptyPath ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/base_dir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new \ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array()));
        $this->assertEquals(array(), $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::reroutePaths
     */
    public function testReroutePaths_WithSymlinksWithNoReroute ()
    {
        $sBaseDir = '/path/to/basedir';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue($sBaseDir));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new \ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aSrc = array(
            $sBaseDir .'trapped',
            '/path/to/elsewhere',
            '/bad' . $sBaseDir,
            'user@server:/other/path',
            'user@server:/bad' . $sBaseDir
        );
        $aDest = $aSrc;
        $aResult = $oMethod->invokeArgs($oMockTask, array($aSrc));
        $this->assertEquals($aDest, $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::reroutePaths
     */
    public function testReroutePaths_WithSymlinksWithReroute ()
    {
        $sBaseDir = '/path/to/basedir';
        $aSrc = array(
            $sBaseDir,
            $sBaseDir . '/subdir',
            'user@server:' . $sBaseDir,
            'user@server:' . $sBaseDir . '/subdir'
        );
        $aDest = array(
            $sBaseDir,
            $sBaseDir . '/subdir',
            'user@server:' . $sBaseDir . $this->aConfig['symlink_releases_dir_suffix'] . '/12345',
            'user@server:' . $sBaseDir . $this->aConfig['symlink_releases_dir_suffix'] . '/12345/subdir'
        );

        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);

        /* @var $oMockProperties PropertiesInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue($sBaseDir));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new \ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);
        $aResult = $oMethod->invokeArgs($oMockTask, array($aSrc));
        $this->assertEquals($aDest, $aResult);
    }

    /**
     * @covers \Himedia\Padocc\Task::registerPaths
     */
    public function testRegisterPaths ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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

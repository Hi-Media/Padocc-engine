<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskTest extends PHPUnit_Framework_TestCase {

    /**
     * Collection de services.
     * @var ServiceContainer
     */
    private $oServiceContainer;

    public function setUp () {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

        $oShell = new Shell_Adapter($oLogger);
        $oProperties = new Properties_Adapter($oShell);
        $oNumbering = new Numbering_Adapter();

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oShell)
            ->setNumberingAdapter($oNumbering);
    }

    public function tearDown() {
        $this->oServiceContainer = NULL;
    }

    /**
     * @covers Task::_expandPath
     */
    public function testExpandPathsWithSimpleString () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('_expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('test'));
        $this->assertEquals(array('test'), $aResult);
    }

    /**
     * @covers Task::_expandPath
     */
    public function testExpandPathsWithOneSimpleParameter () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('simple_value'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oServiceContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('_expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
        $this->assertEquals(array('foosimple_valuebar'), $aResult);
    }

    /**
     * @covers Task::_expandPath
     */
    public function testExpandPathsWithOneComplexParameter () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('123 three values'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oServiceContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('_expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
        $this->assertEquals(array('foo123bar', 'foothreebar', 'foovaluesbar'), $aResult);
    }

    /**
     * @covers Task::_expandPath
     */
    public function testExpandPathsWithTwoComplexParameters () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('123 three values'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('q'))
            ->will($this->returnValue('0 1'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oServiceContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('_expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('${p}foo${q}'));
        $this->assertEquals(array(
            '123foo0', '123foo1',
            'threefoo0', 'threefoo1',
            'valuesfoo0', 'valuesfoo1',
        ), $aResult);
    }

    /**
     * @covers Task::_expandPath
     */
    public function testExpandPathsWithMultiComplexParameters () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
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
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oServiceContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('_expandPath');
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
     * @covers Task::check
     */
    public function testCheckEmptyNotThrowException () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckThrowExceptionIfUnknownAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 'bar'));

        $this->setExpectedException('DomainException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckThrowExceptionIfRequiredAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $this->setExpectedException('UnexpectedValueException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckRequiredAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => 'foo'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckFileAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => AttributeProperties::FILE));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '\path\to/foo'));

        $oMockTask->setUp();
        $this->assertAttributeEquals(array('srcfile' => '/path/to/foo'), '_aAttributes', $oMockTask);
    }

    /**
     * @covers Task::check
     */
    public function testCheckDirAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::DIR));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '\path\to/foo/'));

        $oMockTask->setUp();
        $this->assertAttributeEquals(array('srcdir' => '/path/to/foo'), '_aAttributes', $oMockTask);
    }

    /**
     * @covers Task::check
     */
    public function testCheckThrowExceptionIfDirectoryJokerWithoutDirjokerAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => array()));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

        $this->setExpectedException('DomainException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckDirectoryJokerWithDirjokerAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::DIRJOKER));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckThrowExceptionIfFileJokerWithoutFilejokerAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => array()));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

        $this->setExpectedException('DomainException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckThrowExceptionIfBadBooleanAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::BOOLEAN));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'not a boolean'));

        $this->setExpectedException('DomainException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckBooleanAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'b_true' => AttributeProperties::BOOLEAN,
            'b_false' => AttributeProperties::BOOLEAN
        ));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b_true' => 'true'));
        $oProperty->setValue($oMockTask, array('b_false' => 'true'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckFileJokerWithFilejokerAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => AttributeProperties::FILEJOKER));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckThrowExceptionIfParameterWithoutAllowparametersAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => array()));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

        $this->setExpectedException('DomainException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckParameterWithAllowparametersAttribute () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => AttributeProperties::ALLOW_PARAMETER));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     */
    public function testCheckParameterThrowExceptionWithSrcpathAttribute () {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oServiceContainer->getLogAdapter()));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->returnValue(array('0')));
        $this->oServiceContainer->setShellAdapter($oMockShell);

        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => AttributeProperties::SRC_PATH));

        $oProperty = $o->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => 'foo'));

        $this->setExpectedException('RuntimeException');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::getTagName
     */
    public function testGetTagNameThrowException () {
        $this->setExpectedException('RuntimeException');
        Task::getTagName();
    }

    /**
     * @covers Task::getNewInstance
     */
    public function testGetNewInstanceThrowException () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $this->setExpectedException('RuntimeException');
        Task::getNewInstance(array(), $oMockProject, '', $this->oServiceContainer);
    }

    /**
     * @covers Task::getNewInstance
     * @covers Task::_fetchAttributes
     * @covers Task::__construct
     */
    public function testGetNewInstanceOk () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oTaskCopy = Task_Base_Copy::getNewInstance(array('attr1' => 'v1', 'attr2' => 'v2'), $oMockProject, '', $this->oServiceContainer);
        $this->assertAttributeEquals(array('attr1' => 'v1', 'attr2' => 'v2'), '_aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task::_reroutePaths
     */
    public function testReroutePathsWithoutSymlinksWithEmptyPath () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('_reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array()));
        $this->assertEquals(array(), $aResult);
    }

    /**
     * @covers Task::_reroutePaths
     */
    public function testReroutePathsWithoutSymlinks () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('_reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array('/path/to/my_dir', 'user@server:/other/path')));
        $this->assertEquals(array('/path/to/my_dir', 'user@server:/other/path'), $aResult);
    }

    /**
     * @covers Task::_reroutePaths
     */
    public function testReroutePathsWithSymlinksWithEmptyPath () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('base_dir'))
            ->will($this->returnValue('/path/to/base_dir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('_reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array()));
        $this->assertEquals(array(), $aResult);
    }

    /**
     * @covers Task::_reroutePaths
     */
    public function testReroutePathsWithSymlinksWithNoReroute () {
        $sBaseDir = '/path/to/basedir';
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('base_dir'))
            ->will($this->returnValue($sBaseDir));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('_reroutePaths');
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
     * @covers Task::_reroutePaths
     */
    public function testReroutePathsWithSymlinksWithReroute () {
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
            'user@server:' . $sBaseDir . Task::RELEASES_DIRECTORY_SUFFIX . '/12345',
            'user@server:' . $sBaseDir . Task::RELEASES_DIRECTORY_SUFFIX . '/12345/subdir'
        );

        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('base_dir'))
            ->will($this->returnValue($sBaseDir));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('_reroutePaths');
        $oMethod->setAccessible(true);
        $aResult = $oMethod->invokeArgs($oMockTask, array($aSrc));
        $this->assertEquals($aDest, $aResult);
    }

    /**
     * @covers Task::_registerPaths
     */
    public function testRegisterPaths () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('_aRegisteredPaths');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $oClass->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath' => AttributeProperties::DIR | AttributeProperties::FILE,
            'srcdir' => AttributeProperties::DIR,
            'srcfile' => AttributeProperties::FILE,
            'other' => 0
        ));

        $oProperty = $oClass->getProperty('_aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath' => '/path/to/srcpath',
            'srcdir' => 'user@server:/path/to/srcdir',
            'srcfile' => '/path/to/srcfile',
            'other' => '/path/to/other',
        ));

        $oMethod = $oClass->getMethod('_registerPaths');
        $oMethod->setAccessible(true);
        $oMethod->invokeArgs($oMockTask, array());
        $this->assertAttributeEquals(array(
            'user@server:/path/to/srcdir' => true,
            '/path/to/srcfile' => true,
            '/path/to/srcpath' => true
        ), '_aRegisteredPaths', 'Task');
    }

    /**
     * @covers Task::_normalizeAttributeProperties
     */
    public function testNormalizeAttributePropertiesWithAttrSrcPath () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath1' => AttributeProperties::SRC_PATH,
            'srcpath2' => AttributeProperties::SRC_PATH | AttributeProperties::DIR,
            'srcpath3' => AttributeProperties::SRC_PATH | AttributeProperties::FILE,
            'srcpath4' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'other' => 0
        ));

        $oMethod = $oClass->getMethod('_normalizeAttributeProperties');
        $oMethod->setAccessible(true);
        $oMethod->invokeArgs($oMockTask, array());
        $this->assertAttributeEquals(array(
            'srcpath1' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'srcpath2' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'srcpath3' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'srcpath4' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'other' => 0
        ), '_aAttrProperties', $oMockTask);
    }

    /**
     * @covers Task::_normalizeAttributeProperties
     */
    public function testNormalizeAttributePropertiesWithAttrFileJoker () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath1' => AttributeProperties::FILEJOKER,
            'srcpath2' => AttributeProperties::FILEJOKER | AttributeProperties::FILE,
            'other' => 0
        ));

        $oMethod = $oClass->getMethod('_normalizeAttributeProperties');
        $oMethod->setAccessible(true);
        $oMethod->invokeArgs($oMockTask, array());
        $this->assertAttributeEquals(array(
            'srcpath1' => AttributeProperties::FILEJOKER | AttributeProperties::FILE,
            'srcpath2' => AttributeProperties::FILEJOKER | AttributeProperties::FILE,
            'other' => 0
        ), '_aAttrProperties', $oMockTask);
    }

    /**
     * @covers Task::_normalizeAttributeProperties
     */
    public function testNormalizeAttributePropertiesWithAttrDirJoker () {
        $oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oServiceContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('_aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath1' => AttributeProperties::DIRJOKER,
            'srcpath2' => AttributeProperties::DIRJOKER | AttributeProperties::DIR,
            'other' => 0
        ));

        $oMethod = $oClass->getMethod('_normalizeAttributeProperties');
        $oMethod->setAccessible(true);
        $oMethod->invokeArgs($oMockTask, array());
        $this->assertAttributeEquals(array(
            'srcpath1' => AttributeProperties::DIRJOKER | AttributeProperties::DIR,
            'srcpath2' => AttributeProperties::DIRJOKER | AttributeProperties::DIR,
            'other' => 0
        ), '_aAttrProperties', $oMockTask);
    }
}

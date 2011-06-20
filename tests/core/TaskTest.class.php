<?php

/**
 * @covers Task
 */
class TaskTest extends PHPUnit_Framework_TestCase {

	private $oLogger;
	private $oShell;

	public function setUp () {
		$this->oLogger = new Logger_Adapter(Logger_Interface::WARNING);
		$this->oShell = new Shell_Adapter($this->oLogger);
	}

	public function tearDown() {
		$this->oLogger = NULL;
		$this->oShell = NULL;
	}

	/**
	 * @covers Task::expandPaths
	 */
	public function testExpandPathsWithSimpleString () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));

		$class = new ReflectionClass($oMockTask);
		$method = $class->getMethod('expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMockTask, array('test'));
		$this->assertEquals(array('test'), $aResult);
	}

	/**
	 * @covers Task::expandPaths
	 */
	public function testExpandPathsWithOneSimpleParameter () {
		$oMockProject = $this->getMock('Task_Base_Project', array('getProperty'), array(), '', false);
		$oMockProject->expects($this->at(0))->method('getProperty')
			->with($this->equalTo('p'))
			->will($this->returnValue('simple_value'));
		$oMockProject->expects($this->exactly(1))->method('getProperty');

		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oShell, $this->oLogger));

		$class = new ReflectionClass($oMockTask);
		$method = $class->getMethod('expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
		$this->assertEquals(array('foosimple_valuebar'), $aResult);
	}

	/**
	 * @covers Task::expandPaths
	 */
	public function testExpandPathsWithOneComplexParameter () {
		$oMockProject = $this->getMock('Task_Base_Project', array('getProperty'), array(), '', false);
		$oMockProject->expects($this->at(0))->method('getProperty')
			->with($this->equalTo('p'))
			->will($this->returnValue('123 three values'));
		$oMockProject->expects($this->exactly(1))->method('getProperty');

		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oShell, $this->oLogger));

		$class = new ReflectionClass($oMockTask);
		$method = $class->getMethod('expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
		$this->assertEquals(array('foo123bar', 'foothreebar', 'foovaluesbar'), $aResult);
	}

	/**
	 * @covers Task::expandPaths
	 */
	public function testExpandPathsWithTwoComplexParameter () {
		$oMockProject = $this->getMock('Task_Base_Project', array('getProperty'), array(), '', false);
		$oMockProject->expects($this->at(0))->method('getProperty')
			->with($this->equalTo('p'))
			->will($this->returnValue('123 three values'));
		$oMockProject->expects($this->at(1))->method('getProperty')
			->with($this->equalTo('q'))
			->will($this->returnValue('0 1'));
		$oMockProject->expects($this->exactly(2))->method('getProperty');

		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path', $this->oShell, $this->oLogger));

		$class = new ReflectionClass($oMockTask);
		$method = $class->getMethod('expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMockTask, array('${p}foo${q}'));
		$this->assertEquals(array(
			'123foo0', '123foo1',
			'threefoo0', 'threefoo1',
			'valuesfoo0', 'valuesfoo1',
		), $aResult);
	}



	/**
	 * @covers Task::check
	 */
	public function testCheckEmptyNotThrowException () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array());

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array());

		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckThrowExceptionIfUnknownAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array());

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('foo' => 'bar'));

		$this->setExpectedException('UnexpectedValueException');
		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckThrowExceptionIfRequiredAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array('required')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array());

		$this->setExpectedException('UnexpectedValueException');
		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckRequiredAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array('required')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => 'foo'));

		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckFileAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcfile' => array('file')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcfile' => '\path\to/foo'));

		$oMockTask->check();
		$this->assertAttributeEquals(array('srcfile' => '/path/to/foo'), 'aAttributes', $oMockTask);
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckDirAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array('dir')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => '\path\to/foo/'));

		$oMockTask->check();
		$this->assertAttributeEquals(array('srcdir' => '/path/to/foo'), 'aAttributes', $oMockTask);
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckThrowExceptionIfDirectoryJokerWithoutDirjokerAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array()));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

		$this->setExpectedException('DomainException');
		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckDirectoryJokerWithDirjokerAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array('dirjoker')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckThrowExceptionIfFileJokerWithoutFilejokerAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcfile' => array()));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

		$this->setExpectedException('DomainException');
		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckFileJokerWithFilejokerAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcfile' => array('filejoker')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckThrowExceptionIfParameterWithoutAllowparametersAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => array()));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

		$this->setExpectedException('DomainException');
		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckParameterWithAllowparametersAttribute () {
		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $this->oShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => array('allow_parameters')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckParameterThrowExceptionWithSrcpathAttribute () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->returnValue(array('0')));

		$oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
		$oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, '', $oMockShell, $this->oLogger));
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => array('srcpath')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => 'foo'));

		$this->setExpectedException('RuntimeException');
		$oMockTask->check();
	}
}

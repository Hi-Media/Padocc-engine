<?php

class TaskTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers Task::_expandPaths
	 */
	public function testExpandPathsWithSimpleString () {
		$oMock = $this->getMockForAbstractClass('Task', array(), '', false);

		$class = new ReflectionClass($oMock);
		$method = $class->getMethod('_expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMock, array('test'));
		$this->assertEquals(array('test'), $aResult);
	}

	/**
	 * @covers Task::_expandPaths
	 */
	public function testExpandPathsWithOneSimpleParameter () {
		$oMockProject = $this->getMock('Task_Base_Project', array('getProperty'), array(), '', false);
		$oMockProject->expects($this->at(0))->method('getProperty')
			->with($this->equalTo('p'))
			->will($this->returnValue('simple_value'));
		$oMockProject->expects($this->exactly(1))->method('getProperty');

		$oMock = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path'));

		$class = new ReflectionClass($oMock);
		$method = $class->getMethod('_expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMock, array('foo${p}bar'));
		$this->assertEquals(array('foosimple_valuebar'), $aResult);
	}

	/**
	 * @covers Task::_expandPaths
	 */
	public function testExpandPathsWithOneComplexParameter () {
		$oMockProject = $this->getMock('Task_Base_Project', array('getProperty'), array(), '', false);
		$oMockProject->expects($this->at(0))->method('getProperty')
			->with($this->equalTo('p'))
			->will($this->returnValue('123 three values'));
		$oMockProject->expects($this->exactly(1))->method('getProperty');

		$oMock = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path'));

		$class = new ReflectionClass($oMock);
		$method = $class->getMethod('_expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMock, array('foo${p}bar'));
		$this->assertEquals(array('foo123bar', 'foothreebar', 'foovaluesbar'), $aResult);
	}

	/**
	 * @covers Task::_expandPaths
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

		$oMock = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, 'backup_path'));

		$class = new ReflectionClass($oMock);
		$method = $class->getMethod('_expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMock, array('${p}foo${q}'));
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array()));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => '/foo*/'));

		$this->setExpectedException('DomainException');
		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckDirectoryJokerWithDirjokerAttribute () {
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => array('dirjoker')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('srcdir' => '/foo*/'));

		$oMockTask->check();
	}

	/**
	 * @covers Task::check
	 */
	public function testCheckThrowExceptionIfFileJokerWithoutFilejokerAttribute () {
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
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
	/*public function testCheckParameterWithSrcpathAttribute () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->returnValue(array('0')));

		$oMockTask = $this->getMockForAbstractClass('Task', array(), '', false);
		$o = new ReflectionClass($oMockTask);

		$oProperty = $o->getProperty('aAttributeProperties');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => array('srcpath')));

		$oProperty = $o->getProperty('aAttributes');
		$oProperty->setAccessible(true);
		$oProperty->setValue($oMockTask, array('src' => '/foo/bar'));

		$this->setExpectedException('RuntimeException');
		$oMockTask->check();
	}*/
}

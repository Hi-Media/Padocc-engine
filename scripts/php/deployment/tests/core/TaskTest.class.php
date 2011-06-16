<?php

class TaskTest extends PHPUnit_Framework_TestCase {

	public function testExpandPathsWithSimpleString () {
		$oMock = $this->getMockForAbstractClass('Task', array(), '', false);

		$class = new ReflectionClass($oMock);
		$method = $class->getMethod('_expandPaths');
		$method->setAccessible(true);

		$aResult = $method->invokeArgs($oMock, array('test'));
		$this->assertEquals(array('test'), $aResult);
	}

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
}

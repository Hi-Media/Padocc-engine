<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskMkDirTest extends PHPUnit_Framework_TestCase
{

    /**
     * Collection de services.
     * @var ServiceContainer
     */
    private $oServiceContainer;

    /**
     * Project.
     * @var Task_Base_Project
     */
    private $oMockProject;

    private $aShellExecCmds;

    public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
    }

    public function setUp ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2
        ));

        $oProperties = new Properties_Adapter($oMockShell);

        $oNumbering = new Numbering_Adapter();

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);

        $this->oMockProject = $this->getMock('Task_Base_Project', array(), array(), '', false);
    }

    public function tearDown()
    {
        $this->oServiceContainer = NULL;
        $this->oMockProject = NULL;
    }

    /**
     * @covers Task_Base_MkDir::__construct
     * @covers Task_Base_MkDir::check
     */
    public function testCheck_WithoutMode ()
    {
        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir'), $this->oMockProject, $this->oServiceContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir'
        ), '_aAttributes', $oTask);
    }

    /**
     * @covers Task_Base_MkDir::__construct
     * @covers Task_Base_MkDir::check
     */
    public function testCheck_WithMode ()
    {
        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir', 'mode' => '755'), $this->oMockProject, $this->oServiceContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'mode' => '755'
        ), '_aAttributes', $oTask);
    }

    /**
     * @covers Task_Base_MkDir::execute
     * @covers Task_Base_MkDir::_preExecute
     * @covers Task_Base_MkDir::_centralExecute
     * @covers Task_Base_MkDir::_postExecute
     */
    public function testExecute_WithoutMode ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir'), $this->oMockProject, $this->oServiceContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_MkDir::execute
     * @covers Task_Base_MkDir::_preExecute
     * @covers Task_Base_MkDir::_centralExecute
     * @covers Task_Base_MkDir::_postExecute
     */
    public function testExecute_WithMode ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir', 'mode' => '755'), $this->oMockProject, $this->oServiceContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array('mkdir -p "/path/to/destdir" && chmod 755 "/path/to/destdir"'), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_MkDir::execute
     * @covers Task_Base_MkDir::_preExecute
     * @covers Task_Base_MkDir::_centralExecute
     * @covers Task_Base_MkDir::_postExecute
     */
    public function testExecute_WithModeAndSymLinks ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => 'user@server:/path/to/destdir/subdir', 'mode' => '755'), $this->oMockProject, $this->oServiceContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'ssh -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "/path/to/destdir_releases/12345/subdir" && chmod 755 "/path/to/destdir_releases/12345/subdir"' . "\n"
                . 'EOF' . "\n"
        ), $this->aShellExecCmds);
    }
}

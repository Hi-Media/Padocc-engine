<?php

class TaskCopyTest extends PHPUnit_Framework_TestCase {

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

    public function shellExecCallback ($sCmd) {
        $this->aShellExecCmds[] = $sCmd;
    }

    public function setUp () {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        //$oShell = new Shell_Adapter($oLogger);
        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));

        //$oShell = new Shell_Adapter($oLogger);
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

    public function tearDown() {
        $this->oServiceContainer = NULL;
        $this->oMockProject = NULL;
    }

    /**
     * @covers Task_Base_Copy::__construct
     * @covers Task_Base_Copy::check
     */
    public function testCheckWithSrcFile () {
        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/srcfile', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcfile'
        ), 'aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Copy::__construct
     * @covers Task_Base_Copy::check
     */
    public function testCheckWithSrcFileAndJoker () {
        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/src*file?', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/src*file?'
        ), 'aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Copy::__construct
     * @covers Task_Base_Copy::check
     */
    public function testCheckWithSrcDir () {
        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/srcdir', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir/srcdir',
            'src' => '/path/to/srcdir/*'
        ), 'aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Copy::execute
     */
    public function testExecuteWithSrcFile () {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/srcfile', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"',
            'cp -a "/path/to/srcfile" "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Copy::execute
     */
    public function testExecuteWithSrcDir () {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/srcdir', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir/srcdir"',
            'cp -a "/path/to/srcdir/"* "/path/to/destdir/srcdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Copy::execute
     */
    public function testExecuteWithSrcFileAndJoker () {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/src*file?', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"',
            'cp -a "/path/to/src"*"file"? "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }


    /**
     * @covers Task_Base_Copy::execute
     */
    public function testExecuteWithSrcDirAndSymLinks () {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('base_dir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/srcdir', 'destdir' => 'user@server:/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            //'mkdir -p "/path/to/destdir/srcdir"',
            //'cp -a "/path/to/srcdir/"* "/path/to/destdir/srcdir"'
            'ssh -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "/path/to/destdir_releases/12345/srcdir"' . "\n"
                . 'EOF' . "\n",
            'scp -rpq "/path/to/srcdir/"* "user@server:/path/to/destdir_releases/12345/srcdir"'
        ), $this->aShellExecCmds);
    }
}
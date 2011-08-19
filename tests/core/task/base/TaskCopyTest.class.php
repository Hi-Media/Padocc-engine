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

        /*$oMockShell = $this->getMock('Shell_Adapter', array('getFileStatus'), array($oLogger));
        $oMockShell->expects($this->at(0))->method('getFileStatus')
            ->with($this->equalTo('/path/to/srcdir'))
            ->will($this->returnValue(2));
        $oMockShell->expects($this->exactly(1))->method('getFileStatus');*/

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        //$oMockShell->expects($this->any())->method('exec')->will($this->returnArgument(0));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();
        /*$oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(
            function ($sCmd) {var_dump($this); echo $sCmd;}
        ));*/

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
        $oTaskCopy = Task_Base_Copy::getNewInstance(array('src' => '/path/to/src*file?', 'destdir' => '/path/to/destdir'), $this->oMockProject, '', $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"',
            'cp -a "/path/to/src"*"file"? "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }
}

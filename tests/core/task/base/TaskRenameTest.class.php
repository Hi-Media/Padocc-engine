<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskRenameTest extends PHPUnit_Framework_TestCase
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
            'user@server1:/path/to/src' => 2,
            'user@server1:/path/to/dest' => 2,
            '/path/to/src' => 2,
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
     * @covers Task_Base_Rename::__construct
     * @covers Task_Base_Rename::check
     */
    public function testCheck_ThrowExceptionIfServersNotEquals1 ()
    {
        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => 'user@server1:/path/to/src',
            'dest' => 'user@server2:/path/to/dest'
        ), $this->oMockProject, $this->oServiceContainer);
        $this->setExpectedException(
            'DomainException',
            'Paths must be local or on the same server!'
        );
        $oTask->setUp();
    }

    /**
     * @covers Task_Base_Rename::__construct
     * @covers Task_Base_Rename::check
     */
    public function testCheck_ThrowExceptionIfServersNotEquals2 ()
    {
        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => 'user@server1:/path/to/src',
            'dest' => '/path/to/dest'
        ), $this->oMockProject, $this->oServiceContainer);
        $this->setExpectedException(
            'DomainException',
            'Paths must be local or on the same server!'
        );
        $oTask->setUp();
    }

    /**
     * @covers Task_Base_Rename::check
     * @covers Task_Base_Rename::_centralExecute
     */
    public function testExecute_ThrowExceptionIfMultipleSrc ()
    {
        $oClass = new ReflectionClass('Properties_Adapter');
        $oProperty = $oClass->getProperty('_aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oServiceContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my\\project',
            'environment_name' => 'my "env"',
            'execution_id' => '01234\'5\'6789',
            'with_symlinks' => 'false',
            'basedir' => 'x',
            'servers' => 'y',
            'to' => 'a b',
        ));
        $this->oServiceContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => '/path/${TO}/src',
            'dest' => '/path/to/dest'
        ), $this->oMockProject, $this->oServiceContainer);
        $this->setExpectedException(
            'RuntimeException',
            "String '/path/\${TO}/src' should return a single path after process"
        );
        $oTask->setUp();
        $oTask->execute();
    }

    /**
     * @covers Task_Base_Rename::check
     * @covers Task_Base_Rename::_centralExecute
     */
    public function testExecute_ThrowExceptionIfMultipleDest ()
    {
        $oClass = new ReflectionClass('Properties_Adapter');
        $oProperty = $oClass->getProperty('_aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oServiceContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my\\project',
            'environment_name' => 'my "env"',
            'execution_id' => '01234\'5\'6789',
            'with_symlinks' => 'false',
            'basedir' => 'x',
            'servers' => 'y',
            'to' => 'a b',
        ));
        $this->oServiceContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => '/path/to/src',
            'dest' => '/path/${TO}/dest'
        ), $this->oMockProject, $this->oServiceContainer);
        $this->setExpectedException(
            'RuntimeException',
            "String '/path/\${TO}/dest' should return a single path after process"
        );
        $oTask->setUp();
        $oTask->execute();
    }

    /**
     * @covers Task_Base_Rename::check
     * @covers Task_Base_Rename::_centralExecute
     */
    public function testExecute_Simple ()
    {
        $oClass = new ReflectionClass('Properties_Adapter');
        $oProperty = $oClass->getProperty('_aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oServiceContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my\\project',
            'environment_name' => 'my "env"',
            'execution_id' => '01234\'5\'6789',
            'with_symlinks' => 'false',
            'basedir' => 'x',
            'servers' => 'y',
        ));
        $this->oServiceContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => '/path/to/src',
            'dest' => '/path/to/dest'
        ), $this->oMockProject, $this->oServiceContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(
            array("mv \"/path/to/src\" '/path/to/dest'"),
            $this->aShellExecCmds
        );
    }
}

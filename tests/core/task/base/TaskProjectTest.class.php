<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskProjectTest extends PHPUnit_Framework_TestCase
{
    /**
     * Collection de services.
     * @var ServiceContainer
     */
    private $oServiceContainer;

    private $aShellExecCmds;

    public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
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
            '/path/to/file' => 1
        ));

        $oProperties = new Properties_Adapter($oMockShell);

        $oNumbering = new Numbering_Adapter();

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oServiceContainer = NULL;
    }

    /**
     * @covers Task_Base_Project::getAllProjectsName
     */
    public function testGetAllProjectsName_ThrowExceptionIfNotFound ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Resource path not found: '"
        );
        Task_Base_Project::getAllProjectsName(__DIR__ . '/not_found');
    }

    /**
     * @covers Task_Base_Project::getAllProjectsName
     */
    public function testGetAllProjectsName_ThrowExceptionIfBadXml ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        Task_Base_Project::getAllProjectsName(__DIR__ . '/resources/2');
    }

    /**
     * @covers Task_Base_Project::getAllProjectsName
     */
    public function testGetAllProjectsName ()
    {
        $aProjectNames = Task_Base_Project::getAllProjectsName(__DIR__ . '/resources/1');
        $this->assertEquals($aProjectNames, array('ebay', 'ptpn', 'rts'));
    }

    /**
     * @covers Task_Base_Project::getSXEProject
     */
    public function testGetSXEProject_ThrowExceptionIfNotFound () {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Project definition not found: '"
        );
        Task_Base_Project::getSXEProject(__DIR__ . '/not_found');
    }

    /**
     * @covers Task_Base_Project::getSXEProject
     */
    public function testGetSXEProject_ThrowExceptionIfBadXML ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        Task_Base_Project::getSXEProject(__DIR__ . '/resources/2/bad_xml.xml');
    }

    /**
     * @covers Task_Base_Project::getSXEProject
     */
    public function testGetSXEProject ()
    {
        $oSXE = Task_Base_Project::getSXEProject(__DIR__ . '/resources/1/ebay.xml');
        $this->assertEquals($oSXE, new SimpleXMLElement(__DIR__ . '/resources/1/ebay.xml', NULL, true));
    }

    /**
     * @covers Task_Base_Project::__construct
     */
    public function testNew_ThrowExceptionIfProjectNotFound ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Project definition not found: '/path/not found'!"
        );
        $oTask = new Task_Base_Project('/path/not found', 'myEnv', $this->oServiceContainer);
    }

    /**
     * @covers Task_Base_Project::__construct
     */
    public function testNew_ThrowExceptionIfBadXML ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = 'bla bla';
        file_put_contents($sTmpPath, $sContent);
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '" . DEPLOYMENT_TMP_DIR . "/deploy_unittest_"
        );
        try {
            $oTask = new Task_Base_Project($sTmpPath, 'myEnv', $this->oServiceContainer);
        } catch (UnexpectedValueException $oException) {
            unlink($sTmpPath);
            throw $oException;
        }
    }

    /**
     * @covers Task_Base_Project::__construct
     */
    public function testNew_ThrowExceptionIfEnvNotFound ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests">
</project>
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->setExpectedException(
            'UnexpectedValueException',
            "Environment 'myEnv' not found or not unique in this project!"
        );
        try {
            $oTask = new Task_Base_Project($sTmpPath, 'myEnv', $this->oServiceContainer);
        } catch (UnexpectedValueException $oException) {
            unlink($sTmpPath);
            throw $oException;
        }
    }

    /**
     * @covers Task_Base_Project::__construct
     */
    public function testNew_ThrowExceptionIfMultipleEnv ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests">
    <env name="myEnv" />
    <env name="myEnv" />
</project>
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->setExpectedException(
            'UnexpectedValueException',
            "Environment 'myEnv' not found or not unique in this project!"
        );
        try {
            $oTask = new Task_Base_Project($sTmpPath, 'myEnv', $this->oServiceContainer);
        } catch (UnexpectedValueException $oException) {
            unlink($sTmpPath);
            throw $oException;
        }
    }

    /**
     * @covers Task_Base_Project::__construct
     * @covers Task_Base_Project::check
     */
    public function testCheck ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests" propertyinifile="/path/to/file">
    <env name="myEnv" basedir="/base/dir" />
</project>
EOT;
        file_put_contents($sTmpPath, $sContent);
        $oProject = new Task_Base_Project($sTmpPath, 'myEnv', $this->oServiceContainer);
        /*$oProject = $this->getMock(
            'Task_Base_Project',
            array('_loadProperties'),
            array($sTmpPath, 'myEnv', 'anExecutionID', $this->oServiceContainer)
        );*/
        $oProject->setUp();
        unlink($sTmpPath);

        $oClass = new ReflectionClass('Task_Base_Project');
        $oProperty = $oClass->getProperty('_oBoundTask');
        $oProperty->setAccessible(true);
        $oEnv = $oProperty->getValue($oProject);

        $this->assertAttributeEquals(array(
            'basedir' => '/base/dir',
            'name' => 'myEnv'
        ), '_aAttributes', $oEnv);
    }
}

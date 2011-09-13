<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskTargetTest extends PHPUnit_Framework_TestCase
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

    public function tearDown()
    {
        $this->oServiceContainer = NULL;
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfNotFound () {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Project definition not found: '"
        );
        Task_Base_Target::getAvailableEnvsList(__DIR__ . '/not_found');
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfBadXML ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        Task_Base_Target::getAvailableEnvsList(__DIR__ . '/resources/2/bad_xml.xml');
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfNoEnv ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "No environment found in "
        );
        Task_Base_Target::getAvailableEnvsList(__DIR__ . '/resources/2/project_without_env.xml');
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfInvalidProperty ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Invalid external property in "
        );
        Task_Base_Target::getAvailableEnvsList(__DIR__ . '/resources/2/project_env_withinvalidproperty.xml');
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     * @covers Task_Base_Target::_getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfInvalidTarget ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Target 'invalid' not found or not unique in this project!"
        );
        Task_Base_Target::getAvailableEnvsList(__DIR__ . '/resources/2/project_env_withinvalidtarget.xml');
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     * @covers Task_Base_Target::_getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithEmptyEnv ()
    {
        $aExpected = array(
            'my_env' => array()
        );
        $sProjectPath = __DIR__ . '/resources/2/project_env_empty.xml';
        $aEnvsList = Task_Base_Target::getAvailableEnvsList($sProjectPath);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     * @covers Task_Base_Target::_getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithoutExtProperty ()
    {
        $aExpected = array(
            'my_env' => array()
        );
        $sProjectPath = __DIR__ . '/resources/2/project_env_without_extproperty.xml';
        $aEnvsList = Task_Base_Target::getAvailableEnvsList($sProjectPath);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     * @covers Task_Base_Target::_getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithOneProperty ()
    {
        $aExpected = array(
            'my_env' => array('ref' => 'Branch or tag to deploy')
        );
        $sProjectPath = __DIR__ . '/resources/2/project_env_withoneproperty.xml';
        $aEnvsList = Task_Base_Target::getAvailableEnvsList($sProjectPath);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     * @covers Task_Base_Target::_getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithProperties ()
    {
        $aExpected = array(
            'my_env' => array(
                'ref' => 'Branch or tag to deploy',
                'ref2' => 'label',
            )
        );
        $sProjectPath = __DIR__ . '/resources/2/project_env_withproperties.xml';
        $aEnvsList = Task_Base_Target::getAvailableEnvsList($sProjectPath);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers Task_Base_Target::getAvailableEnvsList
     * @covers Task_Base_Target::_getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithCallAndProperties ()
    {
        $aExpected = array(
            'my_env' => array(
                'ref' => 'Branch or tag to deploy',
                'ref2' => 'label',
                'ref3' => 'other...',
            )
        );
        $sProjectPath = __DIR__ . '/resources/2/project_env_withcallandproperties.xml';
        $aEnvsList = Task_Base_Target::getAvailableEnvsList($sProjectPath);
        $this->assertEquals($aExpected, $aEnvsList);
    }
}

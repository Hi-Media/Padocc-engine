<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskFillTemplateTest extends PHPUnit_Framework_TestCase
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

    private $aWarnMessages;

    public function logCallback ($sMsg, $iLevel)
    {
        $this->aWarnMessages[$iLevel][] = $sMsg;
    }

    public function setUp ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::ERROR);
        //$oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oLogger = $this->getMock('Logger_IndentedDecorator', array('log'), array($oBaseLogger, '   '));
        $oLogger->expects($this->any())->method('log')->will($this->returnCallback(array($this, 'logCallback')));
        $this->aWarnMessages = array();

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        //$oShell = new Shell_Adapter($oLogger);
        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
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

    public function tearDown()
    {
        $this->oServiceContainer = NULL;
        $this->oMockProject = NULL;
    }

    /**
     * @covers Task_Base_FillTemplate::__construct
     * @covers Task_Base_FillTemplate::check
     */
    public function testCheck_throwExceptionIfSrcIsRemote ()
    {
        $oTask = Task_Base_FillTemplate::getNewInstance(
            array('srcfile' => 'server:/path/to/srcfile', 'destfile' => '/path/to/destdir'),
            $this->oMockProject,
            $this->oServiceContainer
        );
        $this->setExpectedException('DomainException', 'Remote paths not yet handled.');
        $oTask->setUp();
    }

    /**
     * @covers Task_Base_FillTemplate::__construct
     * @covers Task_Base_FillTemplate::check
     */
    public function testCheck_throwExceptionIfDestIsRemote ()
    {
        $oTask = Task_Base_FillTemplate::getNewInstance(
            array('srcfile' => '/path/to/srcfile', 'destfile' => 'server:/path/to/destdir'),
            $this->oMockProject,
            $this->oServiceContainer
        );
        $this->setExpectedException('DomainException', 'Remote paths not yet handled.');
        $oTask->setUp();
    }

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
            'not_exists' => 'z',
        ));
        $this->oServiceContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_FillTemplate::getNewInstance(
            array(
                'srcfile' => __DIR__ . '/resources/config_template.inc.php',
                'destfile' => DEPLOYMENT_TMP_DIR . '/config.inc.php'
            ),
            $this->oMockProject,
            $this->oServiceContainer
        );
        $oTask->setUp();
        $oTask->execute();
        $sResult = file_get_contents(DEPLOYMENT_TMP_DIR . '/config.inc.php');
        unlink(DEPLOYMENT_TMP_DIR . '/config.inc.php');

        $sExpectedResult = <<<'EOT'
<?php

define('PROJECT', 'my\\project');
define('PROJECT_BIS', 'my\\project');
define('ENV', 'my \"env\"');
define('EXECUTION_ID', '01234\'5\'6789');
define('BASEDIR', 'x');
define('LOCAL_PROPERTY_SERVERS', 'y');
define('TEST', 'z');

EOT;
        $this->assertEquals(str_replace("\r\n", "\n", $sExpectedResult), $sResult);
        $this->assertEquals(0, count($this->aWarnMessages[Logger_Interface::WARNING]));
    }

    public function testExecute_withWarning ()
    {
        $oClass = new ReflectionClass('Properties_Adapter');
        $oProperty = $oClass->getProperty('_aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oServiceContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my project',
            'environment_name' => 'my env',
            'execution_id' => '0123456789',
            'with_symlinks' => 'false',
        ));
        $this->oServiceContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_FillTemplate::getNewInstance(
            array(
                'srcfile' => __DIR__ . '/resources/config_template.inc.php',
                'destfile' => DEPLOYMENT_TMP_DIR . '/config.inc.php'
            ),
            $this->oMockProject,
            $this->oServiceContainer
        );
        $oTask->setUp();
        $oTask->execute();
        $sResult = file_get_contents(DEPLOYMENT_TMP_DIR . '/config.inc.php');
        unlink(DEPLOYMENT_TMP_DIR . '/config.inc.php');

        $sExpectedResult = <<<'EOT'
<?php

define('PROJECT', 'my project');
define('PROJECT_BIS', 'my project');
define('ENV', 'my env');
define('EXECUTION_ID', '0123456789');
define('BASEDIR', '${BASEDIR}');
define('LOCAL_PROPERTY_SERVERS', '${SERVERS}');
define('TEST', '${NOT_EXISTS}');

EOT;
        $this->assertEquals(str_replace("\r\n", "\n", $sExpectedResult), $sResult);
        $this->assertEquals(3, count($this->aWarnMessages[Logger_Interface::WARNING]));
    }
}

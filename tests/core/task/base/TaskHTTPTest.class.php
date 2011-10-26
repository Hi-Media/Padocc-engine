<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskHTTPTest extends PHPUnit_Framework_TestCase
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

    /**
     * Tableau indexé contenant les commandes Shell de tous les appels effectués à Shell_Adapter::exec().
     * @var array
     * @see shellExecCallback()
     */
    private $aShellExecCmds;

    /**
     * Callback déclenchée sur appel de Shell_Adapter::exec().
     * Log tous les appels dans le tableau indexé $this->aShellExecCmds.
     *
     * @param string $sCmd commande Shell qui aurait dûe être exécutée.
     * @see $aShellExecCmds
     */
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

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oServiceContainer = NULL;
        $this->oMockProject = NULL;
    }

    /**
     * @covers Task_Base_HTTP::__construct
     * @covers Task_Base_HTTP::execute
     * @covers Task_Base_HTTP::_preExecute
     * @covers Task_Base_HTTP::_centralExecute
     * @covers Task_Base_HTTP::_postExecute
     */
    public function testExecute_ThrowExceptionIfCURLReturnErrorMsg ()
    {
        $oLogger = $this->oServiceContainer->getLogAdapter();
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnValue(array('[ERROR] blabla')));
        $this->oServiceContainer->setShellAdapter($oMockShell);

        $sXML = '<http url="http://xxx" />';
        $oXML = new SimpleXMLElement($sXML);
        $oTaskHTTP = $this->getMock('Task_Base_HTTP', array('_reroutePaths'), array($oXML, $this->oMockProject, $this->oServiceContainer));
        $oTaskHTTP->expects($this->any())->method('_reroutePaths')->will($this->returnArgument(0));

        $this->setExpectedException('RuntimeException', '[ERROR] blabla');
        $oTaskHTTP->setUp();
        $oTaskHTTP->execute();
    }

    /**
     * @covers Task_Base_HTTP::execute
     * @covers Task_Base_HTTP::_preExecute
     * @covers Task_Base_HTTP::_centralExecute
     * @covers Task_Base_HTTP::_postExecute
     */
    public function testExecute_WithOneURL ()
    {
        $sXML = '<http url="http://aai.twenga.com/push.php?server=www26&amp;app=web" />';
        $oXML = new SimpleXMLElement($sXML);
        $oTaskHTTP = $this->getMock('Task_Base_HTTP', array('_reroutePaths'), array($oXML, $this->oMockProject, $this->oServiceContainer));
        $oTaskHTTP->expects($this->any())->method('_reroutePaths')->will($this->returnArgument(0));

        $oTaskHTTP->setUp();
        $oTaskHTTP->execute();
        $this->assertEquals(array(
            'curl --silent --retry 2 --retry-delay 2 --max-time 5 "http://aai.twenga.com/push.php?server=www26&app=web"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_HTTP::execute
     * @covers Task_Base_HTTP::_preExecute
     * @covers Task_Base_HTTP::_centralExecute
     * @covers Task_Base_HTTP::_postExecute
     */
    public function testExecute_WithMultiURL ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('servers'))
            ->will($this->returnValue('www01 www02 www03'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $sXML = '<http url="http://aai.twenga.com/push.php?server=${servers}&amp;app=web" />';
        $oXML = new SimpleXMLElement($sXML);
        $oTaskHTTP = $this->getMock('Task_Base_HTTP', array('_reroutePaths'), array($oXML, $this->oMockProject, $this->oServiceContainer));
        $oTaskHTTP->expects($this->any())->method('_reroutePaths')->will($this->returnArgument(0));

        $oTaskHTTP->setUp();
        $oTaskHTTP->execute();
        $this->assertEquals(array(
            'curl --silent --retry 2 --retry-delay 2 --max-time 5 "http://aai.twenga.com/push.php?server=www01&app=web"',
            'curl --silent --retry 2 --retry-delay 2 --max-time 5 "http://aai.twenga.com/push.php?server=www02&app=web"',
            'curl --silent --retry 2 --retry-delay 2 --max-time 5 "http://aai.twenga.com/push.php?server=www03&app=web"',
        ), $this->aShellExecCmds);
    }
}

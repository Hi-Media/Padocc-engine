<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskB2CSwitchSymlinkTest extends PHPUnit_Framework_TestCase
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
     * Tableau de tous les messages de log interceptés, regroupés par priorité.
     * @var array
     * @see logCallback()
     */
    private $aMessages;

    /**
     * Callback déclenchée sur appel de Logger_IndentedDecorator::log().
     * Log tous les appels dans le tableau indexé $this->aWarnMessages.
     *
     * @param string $sMsg message à logger.
     * @param int $iLevel priorité du message.
     * @see $aWarnMessages
     */
    public function logCallback ($sMsg, $iLevel)
    {
        $this->aMessages[$iLevel][] = $sMsg;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::ERROR);
        $oLogger = $this->getMock('Logger_IndentedDecorator', array('log'), array($oBaseLogger, '   '));
        $oLogger->expects($this->any())->method('log')->will($this->returnCallback(array($this, 'logCallback')));
        $this->aMessages = array();

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

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
     * @covers Task_Extended_B2CSwitchSymlink::_setCluster
     */
    public function testSetCluster_ThrowException ()
    {
        $oMockShell = $this->oServiceContainer->getShellAdapter();
        $oMockShell->expects($this->any())->method('exec')
            ->with($this->equalTo('/home/prod/twenga/tools/wwwcluster -s server1 -d'))
            ->will($this->throwException(new RuntimeException('bla bla', 1)));

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($oMockShell));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->will($this->returnValue('-'));
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Extended_B2CSwitchSymlink::getNewInstance(
            array(), $this->oMockProject, $this->oServiceContainer
        );

        $oClass = new ReflectionClass($oTask);
        $oMethod = $oClass->getMethod('_setCluster');
        $oMethod->setAccessible(true);

        $this->setExpectedException(
            'RuntimeException',
            'bla bla'
        );
        $sResult = $oMethod->invokeArgs($oTask, array('server1', false));
    }

    /**
     * @covers Task_Extended_B2CSwitchSymlink::_setCluster
     */
    public function testSetCluster_WithWarning ()
    {
        $oMockShell = $this->oServiceContainer->getShellAdapter();
        $oMockShell->expects($this->any())->method('exec')
            ->with($this->equalTo('/home/prod/twenga/tools/wwwcluster -s server1 -d'))
            ->will($this->throwException(new RuntimeException('bla bla', 2)));

        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($oMockShell));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->will($this->returnValue('-'));
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Extended_B2CSwitchSymlink::getNewInstance(
            array(), $this->oMockProject, $this->oServiceContainer
        );

        $oClass = new ReflectionClass($oTask);
        $oMethod = $oClass->getMethod('_setCluster');
        $oMethod->setAccessible(true);

        $sResult = $oMethod->invokeArgs($oTask, array('server1', false));
        $this->assertEquals(
            array("Remove 'server1' server from the cluster.", "[WARNING] bla bla"),
            $this->aMessages[Logger_Interface::INFO]
        );
    }
}

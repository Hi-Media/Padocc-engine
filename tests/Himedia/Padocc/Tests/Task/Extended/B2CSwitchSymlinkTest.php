<?php

namespace Himedia\Padocc\Tests\Task\Extended;

use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class B2CSwitchSymlinkTest extends PadoccTestCase
{

    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Project.
     * @var Project
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
        $oBaseLogger = new Logger_Adapter(LoggerInterface::ERROR);
        $oLogger = $this->getMock('Logger_IndentedDecorator', array('log'), array($oBaseLogger, '   '));
        $oLogger->expects($this->any())->method('log')->will($this->returnCallback(array($this, 'logCallback')));
        $this->aMessages = array();

        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);

        $oNumbering = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);

        $this->oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oDIContainer = null;
        $this->oMockProject = null;
    }

    /**
     * @covers Task_Extended_B2CSwitchSymlink::_setCluster
     */
    public function testSetCluster_ThrowException ()
    {
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->any())->method('exec')
            ->with($this->equalTo('/home/prod/twenga/tools/wwwcluster -s server1 -d'))
            ->will($this->throwException(new \RuntimeException('bla bla', 1)));

        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($oMockShell));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->will($this->returnValue('-'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Extended_B2CSwitchSymlink::getNewInstance(
            array(), $this->oMockProject, $this->oDIContainer
        );

        $oClass = new \ReflectionClass($oTask);
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
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->any())->method('exec')
            ->with($this->equalTo('/home/prod/twenga/tools/wwwcluster -s server1 -d'))
            ->will($this->throwException(new \RuntimeException('bla bla', 2)));

        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($oMockShell));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->will($this->returnValue('-'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Extended_B2CSwitchSymlink::getNewInstance(
            array(), $this->oMockProject, $this->oDIContainer
        );

        $oClass = new \ReflectionClass($oTask);
        $oMethod = $oClass->getMethod('_setCluster');
        $oMethod->setAccessible(true);

        $sResult = $oMethod->invokeArgs($oTask, array('server1', false));
        $this->assertEquals(
            array("Remove 'server1' server from the cluster.", "[WARNING] bla bla"),
            $this->aMessages[LoggerInterface::INFO]
        );
    }
}

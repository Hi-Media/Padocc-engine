<?php

namespace Himedia\Padocc\Tests\Task\Base;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class MkDirTest extends \PHPUnit_Framework_TestCase
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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oBaseLogger = new Logger_Adapter(LoggerInterface::WARNING);
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

        $this->oDIContainer = new ServiceContainer();
        $this->oDIContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);

        $this->oMockProject = $this->getMock('Project', array(), array(), '', false);
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
     * @covers Task_Base_MkDir::__construct
     * @covers Task_Base_MkDir::check
     */
    public function testCheck_WithoutMode ()
    {
        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir'
        ), 'aAttributes', $oTask);
    }

    /**
     * @covers Task_Base_MkDir::__construct
     * @covers Task_Base_MkDir::check
     */
    public function testCheck_WithMode ()
    {
        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir', 'mode' => '755'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'mode' => '755'
        ), 'aAttributes', $oTask);
    }

    /**
     * @covers Task_Base_MkDir::execute
     * @covers Task_Base_MkDir::preExecute
     * @covers Task_Base_MkDir::centralExecute
     * @covers Task_Base_MkDir::postExecute
     */
    public function testExecute_WithoutMode ()
    {
        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_MkDir::execute
     * @covers Task_Base_MkDir::preExecute
     * @covers Task_Base_MkDir::centralExecute
     * @covers Task_Base_MkDir::postExecute
     */
    public function testExecute_WithMode ()
    {
        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => '/path/to/destdir', 'mode' => '755'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array('mkdir -p "/path/to/destdir" && chmod 755 "/path/to/destdir"'), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_MkDir::execute
     * @covers Task_Base_MkDir::preExecute
     * @covers Task_Base_MkDir::centralExecute
     * @covers Task_Base_MkDir::postExecute
     */
    public function testExecute_WithModeAndSymLinks ()
    {
        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
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
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_MkDir::getNewInstance(array('destdir' => 'user@server:/path/to/destdir/subdir', 'mode' => '755'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "/path/to/destdir_releases/12345/subdir" && chmod 755 "/path/to/destdir_releases/12345/subdir"' . "\n"
                . 'EOF' . "\n"
        ), $this->aShellExecCmds);
    }
}

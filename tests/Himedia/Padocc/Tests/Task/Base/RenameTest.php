<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class RenameTest extends PadoccTestCase
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
        $oLogger = new NullLogger();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            'user@server1:/path/to/src' => 2,
            'user@server1:/path/to/dest' => 2,
            '/path/to/src' => 2,
        ));

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
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::__construct
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::check
     */
    public function testCheck_ThrowExceptionIfServersNotEquals1 ()
    {
        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => 'user@server1:/path/to/src',
            'dest' => 'user@server2:/path/to/dest'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Paths must be local or on the same server!'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::__construct
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::check
     */
    public function testCheck_ThrowExceptionIfServersNotEquals2 ()
    {
        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => 'user@server1:/path/to/src',
            'dest' => '/path/to/dest'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Paths must be local or on the same server!'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::check
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::centralExecute
     */
    public function testExecute_ThrowExceptionIfMultipleSrc ()
    {
        $oClass = new \ReflectionClass('Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my\\project',
            'environment_name' => 'my "env"',
            'execution_id' => '01234\'5\'6789',
            'with_symlinks' => 'false',
            'basedir' => 'x',
            'servers' => 'y',
            'to' => 'a b',
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => '/path/${TO}/src',
            'dest' => '/path/to/dest'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'RuntimeException',
            "String '/path/\${TO}/src' should return a single path after process"
        );
        $oTask->setUp();
        $oTask->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::check
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::centralExecute
     */
    public function testExecute_ThrowExceptionIfMultipleDest ()
    {
        $oClass = new \ReflectionClass('Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my\\project',
            'environment_name' => 'my "env"',
            'execution_id' => '01234\'5\'6789',
            'with_symlinks' => 'false',
            'basedir' => 'x',
            'servers' => 'y',
            'to' => 'a b',
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => '/path/to/src',
            'dest' => '/path/${TO}/dest'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'RuntimeException',
            "String '/path/\${TO}/dest' should return a single path after process"
        );
        $oTask->setUp();
        $oTask->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::check
     * @covers \Himedia\Padocc\Task\Base\Task_Base_Rename::centralExecute
     */
    public function testExecute_Simple ()
    {
        $oClass = new \ReflectionClass('Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'project_name' => 'my\\project',
            'environment_name' => 'my "env"',
            'execution_id' => '01234\'5\'6789',
            'with_symlinks' => 'false',
            'basedir' => 'x',
            'servers' => 'y',
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = Task_Base_Rename::getNewInstance(array(
            'src' => '/path/to/src',
            'dest' => '/path/to/dest'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(
            array("mv \"/path/to/src\" '/path/to/dest'"),
            $this->aShellExecCmds
        );
    }
}

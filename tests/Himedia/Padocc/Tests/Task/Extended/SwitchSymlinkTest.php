<?php

namespace Himedia\Padocc\Tests\Task\Extended;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Environment;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Extended\SwitchSymlink;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class SwitchSymlinkTest extends PadoccTestCase
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
            'user@server:/path/to/srcdir' => 2,
            'user@server:/path/to/destdir/subdir' => 2,
            'user@server:/path/to/link' => 12,
            'user@server:/path/to/destdir/link' => 12
        ));

        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);

        $oNumbering = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering)
            ->setConfig($this->aConfig);

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
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithoutAttrServerThrowExceptionIfServersNotEquals1 ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => 'user@server1:/path/to/link',
            'target' => 'user@server2:/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Servers must be equals! Src=user@server1:/path/to/link Target=user@server2:/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithoutAttrServerThrowExceptionIfServersNotEquals2 ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => 'user@server1:/path/to/link',
            'target' => '/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Servers must be equals! Src=user@server1:/path/to/link Target=/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithoutAttrServerThrowExceptionIfServersNotEquals3 ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => '/path/to/link',
            'target' => 'user@server1:/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Servers must be equals! Src=/path/to/link Target=user@server1:/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfServersNotEquals1 ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => 'user@server1:/path/to/link',
            'target' => '/path/to/destdir',
            'server' => 'user@server2'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Servers must be equals! Src=user@server1:/path/to/link Target=/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfServersNotEquals2 ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => '/path/to/link',
            'target' => 'user@server1:/path/to/destdir',
            'server' => 'user@server2'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Servers must be equals! Src=/path/to/link Target=user@server1:/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfServersNotEquals3 ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => 'user@server1:/path/to/link',
            'target' => 'user@server2:/path/to/destdir',
            'server' => 'user@server3'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Servers must be equals! Src=user@server1:/path/to/link Target=user@server2:/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfTwoOtherServers ()
    {
        $oTask = SwitchSymlink::getNewInstance(array(
            'src' => 'user@server1:/path/to/link',
            'target' => 'user@server1:/path/to/destdir',
            'server' => 'user@server2'
        ), $this->oMockProject, $this->oDIContainer);
        $this->setExpectedException(
            'DomainException',
            'Multiple server declaration! Server=user@server2 '
                . 'Src=user@server1:/path/to/link Target=user@server1:/path/to/destdir'
        );
        $oTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithoutAttributes ()
    {
        $oClass = new \ReflectionClass('\Himedia\Padocc\Properties\Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'execution_id' => '0123456789',
            'with_symlinks' => 'false',
            'basedir' => '/home/to/basedir',
            'rollback_id' => ''
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = SwitchSymlink::getNewInstance(array(), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'src' => $oPropertiesAdapter->getProperty('basedir'),
            'target' => $oPropertiesAdapter->getProperty('basedir') . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX
                      . '/' . $oPropertiesAdapter->getProperty('execution_id'),
            'server' => '${' . Environment::SERVERS_CONCERNED_WITH_BASE_DIR . '}'
        ), 'aAttValues', $oTask);
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::__construct
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::check
     */
    public function testCheck_WithoutAttributesWithRollback ()
    {
        $oClass = new \ReflectionClass('\Himedia\Padocc\Properties\Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            'execution_id' => '0123456789',
            'with_symlinks' => 'false',
            'basedir' => '/home/to/basedir',
            'rollback_id' => '1111111111'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $oTask = SwitchSymlink::getNewInstance(array(), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'src' => $oPropertiesAdapter->getProperty('basedir'),
            'target' => $oPropertiesAdapter->getProperty('basedir') . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX
                      . '/' . $oPropertiesAdapter->getProperty('rollback_id'),
            'server' => '${' . Environment::SERVERS_CONCERNED_WITH_BASE_DIR . '}'
        ), 'aAttValues', $oTask);
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\SwitchSymlink::getNbInstances
     */
    public function testGetNbInstances ()
    {
        $i0 = SwitchSymlink::getNbInstances();
        SwitchSymlink::getNewInstance(array(), $this->oMockProject, $this->oDIContainer);
        $this->assertEquals($i0+1, SwitchSymlink::getNbInstances());
    }
}

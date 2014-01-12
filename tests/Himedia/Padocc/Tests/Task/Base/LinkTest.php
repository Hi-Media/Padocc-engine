<?php

namespace Himedia\Padocc\Tests\Task\Base;

use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class LinkTest extends PadoccTestCase
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
            'user@server:/path/to/destdir/link' => 12,
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithoutAttrServerThrowExceptionIfServersNotEquals1 ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithoutAttrServerThrowExceptionIfServersNotEquals2 ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithoutAttrServerThrowExceptionIfServersNotEquals3 ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfServersNotEquals1 ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfServersNotEquals2 ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfServersNotEquals3 ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithAttrServerThrowExceptionIfTwoOtherServers ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
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
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithoutAttrServerAndServers ()
    {
        $oTask = Task_Base_Link::getNewInstance(array('src' => '/path/to/link', 'target' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'src' => '/path/to/link',
            'target' => '/path/to/destdir',
            'server' => ''
        ), 'aAttValues', $oTask);
    }

    /**
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithAttrServer ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => '/path/to/link',
            'target' => '/path/to/destdir',
            'server' => 'user@server'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'src' => '/path/to/link',
            'target' => '/path/to/destdir',
            'server' => 'user@server'
        ), 'aAttValues', $oTask);
    }

    /**
     * @covers Task_Base_Link::__construct
     * @covers Task_Base_Link::check
     */
    public function testCheck_WithoutAttrServerButServers ()
    {
        $oTask = Task_Base_Link::getNewInstance(array('src' => 'user@server:/path/to/link', 'target' => 'user@server:/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->assertAttributeEquals(array(
            'src' => 'user@server:/path/to/link',
            'target' => 'user@server:/path/to/destdir',
            'server' => ''
        ), 'aAttValues', $oTask);
    }

    /**
     * @covers Task_Base_Link::execute
     * @covers Task_Base_Link::preExecute
     * @covers Task_Base_Link::centralExecute
     * @covers Task_Base_Link::postExecute
     */
    public function testExecute_WithoutAttrServer ()
    {
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => 'user@server:/path/to/link',
            'target' => 'user@server:/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "$(dirname "/path/to/link")" && ln -snf "/path/to/destdir" "/path/to/link"' . "\n"
                . 'EOF' . "\n"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Link::execute
     * @covers Task_Base_Link::preExecute
     * @covers Task_Base_Link::centralExecute
     * @covers Task_Base_Link::postExecute
     */
    public function testExecute_WithoutAttrServerAndLocal ()
    {
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => '/path/to/link',
            'target' => '/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(
            array(
                '[ -h "/path/to/link" ] && echo -n 1; [ -d "/path/to/link" ] && echo 2 || ([ -f "/path/to/link" ] && echo 1 || echo 0)',
                'mkdir -p "$(dirname "/path/to/link")" && ln -snf "/path/to/destdir" "/path/to/link"'
            ),
            $this->aShellExecCmds
        );
    }

    /**
     * @covers Task_Base_Link::execute
     * @covers Task_Base_Link::preExecute
     * @covers Task_Base_Link::centralExecute
     * @covers Task_Base_Link::postExecute
     */
    public function testExecute_WithoutAttrServerThrowExceptionIfBadSrc ()
    {
        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => 'user@server:/path/to/srcdir',
            'target' => 'user@server:/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $this->setExpectedException(
            'RuntimeException',
            "Source attribute must be a symlink or not exist: 'user@server:/path/to/srcdir'"
        );
        $oTask->execute();
    }

    /**
     * @covers Task_Base_Link::execute
     * @covers Task_Base_Link::preExecute
     * @covers Task_Base_Link::centralExecute
     * @covers Task_Base_Link::postExecute
     */
    public function testExecute_WithAttrServer ()
    {
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        //$oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => '/path/to/link',
            'target' => '/path/to/destdir',
            'server' => 'user@server'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "$(dirname "/path/to/link")" && ln -snf "/path/to/destdir" "/path/to/link"' . "\n"
                . 'EOF' . "\n"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Link::execute
     * @covers Task_Base_Link::preExecute
     * @covers Task_Base_Link::centralExecute
     * @covers Task_Base_Link::postExecute
     */
    public function testExecute_WithAttrServerAndSymlink ()
    {
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->at(3))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(4))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(5))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(6))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => '/path/to/destdir/link',
            'target' => '/path/to/destdir/subdir',
            'server' => 'user@server'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "$(dirname "/path/to/destdir_releases/12345/link")" && ln -snf "/path/to/destdir_releases/12345/subdir" "/path/to/destdir_releases/12345/link"' . "\n"
                . 'EOF' . "\n"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Link::execute
     * @covers Task_Base_Link::preExecute
     * @covers Task_Base_Link::centralExecute
     * @covers Task_Base_Link::postExecute
     */
    public function testExecute_WithoutAttrServerWithSymlink ()
    {
        $oMockProperties = $this->getMock('\Himedia\Padocc\Properties\Adapter', array('getProperty'), array($this->oDIContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->at(3))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(4))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(5))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(6))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oTask = Task_Base_Link::getNewInstance(array(
            'src' => 'user@server:/path/to/destdir/link',
            'target' => 'user@server:/path/to/destdir/subdir'
        ), $this->oMockProject, $this->oDIContainer);
        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals(array(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "$(dirname "/path/to/destdir_releases/12345/link")" && ln -snf "/path/to/destdir_releases/12345/subdir" "/path/to/destdir_releases/12345/link"' . "\n"
                . 'EOF' . "\n"
        ), $this->aShellExecCmds);
    }
}

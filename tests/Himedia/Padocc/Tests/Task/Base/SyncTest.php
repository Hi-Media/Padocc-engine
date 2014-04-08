<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Base\Sync;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class SyncTest extends PadoccTestCase
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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oLogger = new NullLogger();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock(
            '\GAubry\Shell\ShellAdapter',
            array('exec'),
            array($oLogger, $this->aAllConfigs['GAubry\Shell'])
        );

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
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
     * @covers \Himedia\Padocc\Task\Base\Sync::__construct
     * @covers \Himedia\Padocc\Task\Base\Sync::check
     */
    public function testCheck_WithSrcFile ()
    {
        $oTaskCopy = Sync::getNewInstance(array('src' => '/path/to/srcfile', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcfile'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::__construct
     * @covers \Himedia\Padocc\Task\Base\Sync::check
     */
    public function testCheck_WithSrcDirWithoutLeadingSlash ()
    {
        $oTaskCopy = Sync::getNewInstance(array('src' => '/path/to/srcdir', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir/srcdir',
            'src' => '/path/to/srcdir/'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::__construct
     * @covers \Himedia\Padocc\Task\Base\Sync::check
     */
    public function testCheck_WithSrcDirWithLeadingSlash ()
    {
        $oTaskCopy = Sync::getNewInstance(array('src' => '/path/to/srcdir/', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcdir/'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::__construct
     * @covers \Himedia\Padocc\Task\Base\Sync::check
     */
    public function testCheck_WithSrcDirWithLeadingPattern ()
    {
        $oTaskCopy = Sync::getNewInstance(array('src' => '/path/to/srcdir/*', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcdir/*'
        ), 'aAttValues', $oTaskCopy);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::execute
     * @covers \Himedia\Padocc\Task\Base\Sync::preExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::postExecute
     */
    public function testExecute_WithSrcDir ()
    {
        $aRawRsyncResult = array('---[-]-->0|0s', '[CMD]', '...', '[OUT]',
            'Number of files: 1774',
            'Number of files transferred: 2',
            'Total file size: 64093953 bytes',
            'Total transferred file size: 178 bytes',
            'Literal data: 178 bytes',
            'Matched data: 0 bytes',
            'File list size: 39177',
            'File list generation time: 0.013 seconds',
            'File list transfer time: 0.000 seconds',
            'Total bytes sent: 39542',
            'Total bytes received: 64',
            '',
            'sent 39542 bytes  received 64 bytes  26404.00 bytes/sec',
            'total size is 64093953  speedup is 1618.29',
            '[ERR]', '///',
        );

        /* @var $oMockProperties \Himedia\Padocc\Properties\Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/path/to/destdir/srcdir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo($this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['vendor']
                    . '/geoffroy-aubry/shell/src/inc/parallelize.sh "-" "rsync -axz --delete --exclude=\".bzr/\" --exclude=\".cvsignore\" --exclude=\".git/\" --exclude=\".gitignore\" --exclude=\".svn/\" --exclude=\"cvslog.*\" --exclude=\"CVS\" --exclude=\"CVS.adm\" --exclude=\"to_exclude.*\" --exclude=\"config.php\" --stats -e \"ssh ' . $this->aAllConfigs['GAubry\Shell']['ssh_options'] . '\" \"/path/to/srcdir/\" \"/path/to/destdir/srcdir\""'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $oTaskCopy = Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => '/path/to/destdir',
            'exclude' => 'to_exclude.* config.php'
        ), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::execute
     * @covers \Himedia\Padocc\Task\Base\Sync::preExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::postExecute
     */
    public function testExecute_WithSrcDirAndInclude ()
    {
        $aRawRsyncResult = array('---[-]-->0|0s', '[CMD]', '...', '[OUT]',
            'Number of files: 1774',
            'Number of files transferred: 2',
            'Total file size: 64093953 bytes',
            'Total transferred file size: 178 bytes',
            'Literal data: 178 bytes',
            'Matched data: 0 bytes',
            'File list size: 39177',
            'File list generation time: 0.013 seconds',
            'File list transfer time: 0.000 seconds',
            'Total bytes sent: 39542',
            'Total bytes received: 64',
            '',
            'sent 39542 bytes  received 64 bytes  26404.00 bytes/sec',
            'total size is 64093953  speedup is 1618.29',
            '[ERR]', '///',
        );

        /* @var $oMockProperties \Himedia\Padocc\Properties\Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/path/to/destdir/srcdir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo($this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['vendor'] . '/geoffroy-aubry/shell/src/inc/parallelize.sh "-" "rsync -axz --delete --include=\"*.js\" --include=\"*.css\" --exclude=\".bzr/\" --exclude=\".cvsignore\" --exclude=\".git/\" --exclude=\".gitignore\" --exclude=\".svn/\" --exclude=\"cvslog.*\" --exclude=\"CVS\" --exclude=\"CVS.adm\" --stats -e \"ssh ' . $this->aAllConfigs['GAubry\Shell']['ssh_options'] . '\" \"/path/to/srcdir/\" \"/path/to/destdir/srcdir\""'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $oTaskCopy = Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => '/path/to/destdir',
            'include' => '*.js *.css'
        ), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::execute
     * @covers \Himedia\Padocc\Task\Base\Sync::preExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::postExecute
     */
    public function testExecute_WithSrcDirAndIncludeAndExclude ()
    {
        $aRawRsyncResult = array('---[-]-->0|0s', '[CMD]', '...', '[OUT]',
            'Number of files: 1774',
            'Number of files transferred: 2',
            'Total file size: 64093953 bytes',
            'Total transferred file size: 178 bytes',
            'Literal data: 178 bytes',
            'Matched data: 0 bytes',
            'File list size: 39177',
            'File list generation time: 0.013 seconds',
            'File list transfer time: 0.000 seconds',
            'Total bytes sent: 39542',
            'Total bytes received: 64',
            '',
            'sent 39542 bytes  received 64 bytes  26404.00 bytes/sec',
            'total size is 64093953  speedup is 1618.29',
            '[ERR]', '///',
        );

        /* @var $oMockProperties \Himedia\Padocc\Properties\Adapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProperties = $this->getMock(
            '\Himedia\Padocc\Properties\Adapter',
            array('getProperty'),
            array($this->oDIContainer->getShellAdapter(), $this->aConfig)
        );
        $oMockProperties->expects($this->any())->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/path/to/destdir/srcdir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo($this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['vendor'] . '/geoffroy-aubry/shell/src/inc/parallelize.sh "-" "rsync -axz --delete --include=\"*.js\" --include=\"*.css\" --exclude=\".bzr/\" --exclude=\".cvsignore\" --exclude=\".git/\" --exclude=\".gitignore\" --exclude=\".svn/\" --exclude=\"cvslog.*\" --exclude=\"CVS\" --exclude=\"CVS.adm\" --exclude=\"to_exclude.*\" --exclude=\"config.php\" --stats -e \"ssh ' . $this->aAllConfigs['GAubry\Shell']['ssh_options'] . '\" \"/path/to/srcdir/\" \"/path/to/destdir/srcdir\""'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $oTaskCopy = Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => '/path/to/destdir',
            'include' => '*.js *.css',
            'exclude' => 'to_exclude.* config.php'
        ), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Sync::execute
     * @covers \Himedia\Padocc\Task\Base\Sync::preExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::centralExecute
     * @covers \Himedia\Padocc\Task\Base\Sync::postExecute
     */
    public function testExecute_WithSrcDirAndSymLinks ()
    {
        $aMkdirExecResult = array(
            '---[user@server]-->0|0s', '[CMD]', '...', '[OUT]', '[ERR]', '///',
        );
        $aRawRsyncResult = array('---[user@server]-->0|0s', '[CMD]', '...', '[OUT]',
            'Number of files: 1774',
            'Number of files transferred: 2',
            'Total file size: 64093953 bytes',
            'Total transferred file size: 178 bytes',
            'Literal data: 178 bytes',
            'Matched data: 0 bytes',
            'File list size: 39177',
            'File list generation time: 0.013 seconds',
            'File list transfer time: 0.000 seconds',
            'Total bytes sent: 39542',
            'Total bytes received: 64',
            '',
            'sent 39542 bytes  received 64 bytes  26404.00 bytes/sec',
            'total size is 64093953  speedup is 1618.29',
            '[ERR]', '///',
        );

        $oProperty = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setProperty('with_symlinks', 'true');
        $oProperty->setProperty('basedir', '/path/to/destdir');
        $oProperty->setProperty('execution_id', '12345');

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $sSshOptions = $this->aAllConfigs['GAubry\Shell']['ssh_options'];
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo($this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['vendor'] . '/geoffroy-aubry/shell/src/inc/parallelize.sh "user@server" "ssh ' . $sSshOptions . ' -T [] /bin/bash <<EOF' . "\n"
                . 'mkdir -p \"/path/to/destdir_releases/12345/srcdir\"' . "\n"
                . 'EOF' . "\n" . '"'))
            ->will($this->returnValue($aMkdirExecResult));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo($this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['vendor'] . '/geoffroy-aubry/shell/src/inc/parallelize.sh "user@server" "rsync -axz --delete --exclude=\".bzr/\" --exclude=\".cvsignore\" --exclude=\".git/\" --exclude=\".gitignore\" --exclude=\".svn/\" --exclude=\"cvslog.*\" --exclude=\"CVS\" --exclude=\"CVS.adm\" --stats -e \"ssh ' . $this->aAllConfigs['GAubry\Shell']['ssh_options'] . '\" \"/path/to/srcdir/\" \"[]:/path/to/destdir_releases/12345/srcdir\""'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $oTaskCopy = Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => 'user@server:/path/to/destdir'
        ), $this->oMockProject, $this->oDIContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
    }
}

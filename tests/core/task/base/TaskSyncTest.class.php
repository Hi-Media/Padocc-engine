<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskSyncTest extends PHPUnit_Framework_TestCase
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
        return array();
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
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));

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
     * @covers Task_Base_Sync::__construct
     * @covers Task_Base_Sync::check
     */
    public function testCheck_WithSrcFile ()
    {
        $oTaskCopy = Task_Base_Sync::getNewInstance(array('src' => '/path/to/srcfile', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcfile'
        ), '_aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Sync::__construct
     * @covers Task_Base_Sync::check
     */
    public function testCheck_WithSrcDirWithoutLeadingSlash ()
    {
        $oTaskCopy = Task_Base_Sync::getNewInstance(array('src' => '/path/to/srcdir', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir/srcdir',
            'src' => '/path/to/srcdir/'
        ), '_aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Sync::__construct
     * @covers Task_Base_Sync::check
     */
    public function testCheck_WithSrcDirWithLeadingSlash ()
    {
        $oTaskCopy = Task_Base_Sync::getNewInstance(array('src' => '/path/to/srcdir/', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcdir/'
        ), '_aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Sync::__construct
     * @covers Task_Base_Sync::check
     */
    public function testCheck_WithSrcDirWithLeadingPattern ()
    {
        $oTaskCopy = Task_Base_Sync::getNewInstance(array('src' => '/path/to/srcdir/*', 'destdir' => '/path/to/destdir'), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $this->assertAttributeEquals(array(
            'destdir' => '/path/to/destdir',
            'src' => '/path/to/srcdir/*'
        ), '_aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task_Base_Sync::execute
     * @covers Task_Base_Sync::_preExecute
     * @covers Task_Base_Sync::_centralExecute
     * @covers Task_Base_Sync::_postExecute
     */
    public function testExecute_WithSrcDir ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => '/path/to/destdir',
            'exclude' => 'to_exclude.* config.php'
        ), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir/srcdir"',
            'rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --exclude="to_exclude.*" --exclude="config.php" --stats -e ssh "/path/to/srcdir/" "/path/to/destdir/srcdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Sync::execute
     * @covers Task_Base_Sync::_preExecute
     * @covers Task_Base_Sync::_centralExecute
     * @covers Task_Base_Sync::_postExecute
     */
    public function testExecute_WithSrcDirAndInclude ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => '/path/to/destdir',
            'include' => '*.js *.css'
        ), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir/srcdir"',
            'rsync -axz --delete --include="*.js" --include="*.css" --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/path/to/srcdir/" "/path/to/destdir/srcdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Sync::execute
     * @covers Task_Base_Sync::_preExecute
     * @covers Task_Base_Sync::_centralExecute
     * @covers Task_Base_Sync::_postExecute
     */
    public function testExecute_WithSrcDirAndIncludeAndExclude ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => '/path/to/destdir',
            'include' => '*.js *.css',
            'exclude' => 'to_exclude.* config.php'
        ), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'mkdir -p "/path/to/destdir/srcdir"',
            'rsync -axz --delete --include="*.js" --include="*.css" --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --exclude="to_exclude.*" --exclude="config.php" --stats -e ssh "/path/to/srcdir/" "/path/to/destdir/srcdir"'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Task_Base_Sync::execute
     * @covers Task_Base_Sync::_preExecute
     * @covers Task_Base_Sync::_centralExecute
     * @covers Task_Base_Sync::_postExecute
     */
    public function testExecute_WithSrcDirAndSymLinks ()
    {
        $oMockProperties = $this->getMock('Properties_Adapter', array('getProperty'), array($this->oServiceContainer->getShellAdapter()));
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('base_dir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->at(3))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(4))->method('getProperty')
            ->with($this->equalTo('base_dir'))
            ->will($this->returnValue('/path/to/destdir'));
        $oMockProperties->expects($this->at(5))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(6))->method('getProperty');
        $this->oServiceContainer->setPropertiesAdapter($oMockProperties);

        $oTaskCopy = Task_Base_Sync::getNewInstance(array(
            'src' => '/path/to/srcdir',
            'destdir' => 'user@server:/path/to/destdir'
        ), $this->oMockProject, $this->oServiceContainer);
        $oTaskCopy->setUp();
        $oTaskCopy->execute();
        $this->assertEquals(array(
            'ssh -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "/path/to/destdir_releases/12345/srcdir"' . "\n"
                . 'EOF' . "\n",
            'rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/path/to/srcdir/" "user@server:/path/to/destdir_releases/12345/srcdir"'
        ), $this->aShellExecCmds);
    }
}

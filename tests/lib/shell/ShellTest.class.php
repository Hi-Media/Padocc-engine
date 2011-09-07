<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class ShellTest extends PHPUnit_Framework_TestCase
{

    private $oLogger;
    private $oShell;

    public function setUp ()
    {
        $this->oLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $this->oShell = new Shell_Adapter($this->oLogger);
    }

    public function tearDown()
    {
        $this->oLogger = NULL;
        $this->oShell = NULL;
    }

    /**
     * @covers Shell_Adapter::isRemotePath
     */
    public function testIsRemotePath_WithEmptyPath ()
    {
        $this->assertEquals(array(false, '', ''), $this->oShell->isRemotePath(''));
    }

    /**
     * @covers Shell_Adapter::isRemotePath
     */
    public function testIsRemotePath_WithLocalPath ()
    {
        $this->assertEquals(
            array(false, '', '/path/to/my file'),
            $this->oShell->isRemotePath('/path/to/my file')
        );
    }

    /**
     * @covers Shell_Adapter::isRemotePath
     */
    public function testIsRemotePath_WithRemotePathWithoutLogin ()
    {
        $this->assertEquals(
            array(true, 'dv2', '/path/to/my file'),
            $this->oShell->isRemotePath('dv2:/path/to/my file')
        );
    }

    /**
     * @covers Shell_Adapter::isRemotePath
     */
    public function testIsRemotePath_WithRemotePathWithLogin ()
    {
        $this->assertEquals(
            array(true, 'gaubry@dv2', '/path/to/my file'),
            $this->oShell->isRemotePath('gaubry@dv2:/path/to/my file')
        );
    }

    /**
     * @covers Shell_Adapter::isRemotePath
     */
    public function testIsRemotePath_ThrowExceptionWithParameter ()
    {
        $this->setExpectedException('DomainException', "Invalid syntax: '\${sdg}'.");
        $this->oShell->isRemotePath('${sdg}');
    }

    /**
     * @covers Shell_Adapter::escapePath
     */
    public function testEscapePath_WithEmptyPath ()
    {
        $this->assertEquals('', $this->oShell->escapePath(''));
    }

    /**
     * @covers Shell_Adapter::escapePath
     */
    public function testEscapePath_WithSimplePath ()
    {
        $this->assertEquals('"/path/to/my file"', $this->oShell->escapePath('/path/to/my file'));
    }

    /**
     * @covers Shell_Adapter::escapePath
     */
    public function testEscapePath_WithJokersPath ()
    {
        $this->assertEquals('"/a/b"?"/img"*"jpg"', $this->oShell->escapePath('/a/b?/img*jpg'));
    }

    /**
     * @covers Shell_Adapter::escapePath
     */
    public function testEscapePath_WithConsecutiveJokersPath ()
    {
        $this->assertEquals('"/a/b/img"?*"jpg"', $this->oShell->escapePath('/a/b/img?*jpg'));
    }

    /**
     * @covers Shell_Adapter::escapePath
     */
    public function testEscapePath_WithBoundJokersPath ()
    {
        $this->assertEquals('?"/a/b/img"*', $this->oShell->escapePath('?/a/b/img*'));
    }

    /**
     * @covers Shell_Adapter::exec
     */
    public function testExec_ThrowExceptionOnShellError ()
    {
        $this->setExpectedException('Exception', "abc\ndef", 101);
        $aResult = $this->oShell->exec('echo abc; echo def; exit 101');
    }

    /**
     * @covers Shell_Adapter::exec
     */
    public function testExec_OneLineResult ()
    {
        $aResult = $this->oShell->exec('echo abc');
        $this->assertEquals(array('abc'), $aResult);
    }

    /**
     * @covers Shell_Adapter::exec
     */
    public function testExec_MultiLineResult ()
    {
        $aResult = $this->oShell->exec('echo abc; echo def');
        $this->assertEquals(array('abc', 'def'), $aResult);
    }

    /**
     * @covers Shell_Adapter::exec
     */
    public function testExec_ErrorMultiLineResult ()
    {
        $aResult = $this->oShell->exec('(echo abc; echo def) >&2');
        $this->assertEquals(array('abc', 'def'), $aResult);
    }

    /**
     * @covers Shell_Adapter::execSSH
     */
    public function testExecSsh_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->execSSH('foo', 'bar');
    }

    /**
     * @covers Shell_Adapter::execSSH
     */
    public function testExecSsh_WithLocalPath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ls "/path/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->execSSH('ls %s', '/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::execSSH
     */
    public function testExecSsh_WithMultipleLocalPath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ls "/path/to/my file"; cd "/path/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->execSSH('ls %1$s; cd %1$s', '/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::execSSH
     */
    public function testExecSsh_WithRemotePath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'ls "/path/to/my file"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->execSSH('ls %s', 'gaubry@dv2:/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::mkdir
     */
    public function testMkdir_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->mkdir('foo');
    }

    /**
     * @covers Shell_Adapter::mkdir
     */
    public function testMkdir_WithLocalPath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/path/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->mkdir('/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::mkdir
     */
    public function testMkdir_WithLocalPathAndMode ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/path/to/my file" && chmod 777 "/path/to/my file"'));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $aResult = $oMockShell->mkdir('/path/to/my file', '777');
    }

    /**
     * @covers Shell_Adapter::mkdir
     */
    public function testMkdir_WithRemotePath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'mkdir -p "/path/to/my file"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->mkdir('gaubry@dv2:/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::mkdir
     */
    public function testMkdir_WithRemotePathAndMode ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'mkdir -p "/path/to/my file" && chmod 777 "/path/to/my file"' . "\n" . 'EOF' . "\n"));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $aResult = $oMockShell->mkdir('gaubry@dv2:/path/to/my file', '777');
    }

    /**
     * @covers Shell_Adapter::remove
     */
    public function testRemove_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->remove('foo/bar');
    }

    /**
     * @covers Shell_Adapter::remove
     */
    public function testRemove_ThrowExceptionWhenTooShortPath ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $this->setExpectedException('DomainException', "Illegal path: 'foo'");
        $oMockShell->remove('foo');
    }

    /**
     * @covers Shell_Adapter::remove
     */
    public function testRemove_WithLocalPath ()
    {
        $aExpectedResult = array('blabla');
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));

        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/my file/subdir1' => 1,
            '/path/to/a' => 2,
            '/path/to/my file/sub/subdir2' => 1,
            '/path/to/b' => 1,
            '/path/to/my file' => 1,
        ));

        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('rm -rf "/path/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->remove('/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);

        $this->assertAttributeEquals(array(
            '/path/to/a' => 2,
            '/path/to/b' => 1,
        ), '_aFileStatus', $oMockShell);
    }

    /**
     * @covers Shell_Adapter::remove
     */
    public function testRemove_WithRemotePath ()
    {
        $aExpectedResult = array('blabla');
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));

        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/my file/subdir1' => 1,
            '/path/to/a' => 2,
            'gaubry@dv2:/path/to/my file/sub/subdir2' => 1,
            '/path/to/b' => 1,
            'gaubry@dv2:/path/to/my file' => 1,
        ));

        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'rm -rf "/path/to/my file"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->remove('gaubry@dv2:/path/to/my file');
        $this->assertEquals($aExpectedResult, $aResult);

        $this->assertAttributeEquals(array(
            '/path/to/my file/subdir1' => 1,
            '/path/to/a' => 2,
            '/path/to/b' => 1,
        ), '_aFileStatus', $oMockShell);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->copy('foo', 'bar', false);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_LocalFileToLocalDir ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('cp -a "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->copy('/srcpath/to/my file', '/destpath/to/my dir');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_LocalFilesToLocalDir ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('cp -a "/srcpath/to/"* "/destpath/to/my dir"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->copy('/srcpath/to/*', '/destpath/to/my dir');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_LocalFileToLocalFile ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to"'));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('cp -a "/srcpath/to/my file" "/destpath/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->copy('/srcpath/to/my file', '/destpath/to/my file', true);
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_LocalFileToRemoteDir ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'mkdir -p "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('scp -rpq "/srcpath/to/my file" "gaubry@dv2:/destpath/to/my dir"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->copy('/srcpath/to/my file', 'gaubry@dv2:/destpath/to/my dir');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_RemoteFilesToLocalDir ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "' . DEPLOYMENT_TMP_PATH . '"'));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('scp -rpq "aai-01:/path/to/a"*".css" "' . DEPLOYMENT_TMP_PATH . '"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->copy('aai-01:/path/to/a*.css', DEPLOYMENT_TMP_PATH);
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::copy
     */
    public function testCopy_LocalFileToRemoteFile ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'mkdir -p "/destpath/to"' . "\n" . 'EOF' . "\n"));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('scp -rpq "/srcpath/to/my file" "gaubry@dv2:/destpath/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->copy('/srcpath/to/my file', 'gaubry@dv2:/destpath/to/my file', true);
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::getPathStatus
     */
    public function testGetPathStatus_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->getPathStatus('foo');
    }

    /**
     * @covers Shell_Adapter::getPathStatus
     */
    public function testGetPathStatus_WithFile ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('[ -h "/path/to/my file" ] && echo -n 1; [ -d "/path/to/my file" ] && echo 2 || ([ -f "/path/to/my file" ] && echo 1 || echo 0)'))
            ->will($this->returnValue(array('1')));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->getPathStatus('/path/to/my file');
        $this->assertEquals(1, $aResult);
        $this->assertAttributeEquals(array('/path/to/my file' => 1), '_aFileStatus', $oMockShell);

        return $oMockShell;
    }

    /**
     * @depends testGetPathStatus_WithFile
     * @covers Shell_Adapter::getPathStatus
     */
    public function testGetPathStatus_WithFileOnCache (Shell_Adapter $oMockShell)
    {
        $this->assertAttributeEquals(array('/path/to/my file' => 1), '_aFileStatus', $oMockShell);
        $oMockShell->expects($this->never())->method('exec');
        $aResult = $oMockShell->getPathStatus('/path/to/my file');
        $this->assertEquals(1, $aResult);
    }

    /**
     * @covers Shell_Adapter::getPathStatus
     */
    public function testGetPathStatus_WithDirWithoutLeadingSlash ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('[ -h "/path/to/dir" ] && echo -n 1; [ -d "/path/to/dir" ] && echo 2 || ([ -f "/path/to/dir" ] && echo 1 || echo 0)'))
            ->will($this->returnValue(array('2')));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->getPathStatus('/path/to/dir');
        $this->assertEquals(2, $aResult);

        $this->assertAttributeEquals(array('/path/to/dir' => 2), '_aFileStatus', $oMockShell);
    }

    /**
     * @covers Shell_Adapter::getPathStatus
     */
    public function testGetPathStatus_WithDirWithLeadingSlash ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('[ -h "/path/to/dir" ] && echo -n 1; [ -d "/path/to/dir" ] && echo 2 || ([ -f "/path/to/dir" ] && echo 1 || echo 0)'))
            ->will($this->returnValue(array('2')));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->getPathStatus('/path/to/dir/');
        $this->assertEquals(2, $aResult);

        $this->assertAttributeEquals(array('/path/to/dir' => 2), '_aFileStatus', $oMockShell);
    }

    /**
     * @covers Shell_Adapter::getPathStatus
     */
    public function testGetPathStatus_WithUnknown ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('[ -h "/path/to/unknwon" ] && echo -n 1; [ -d "/path/to/unknwon" ] && echo 2 || ([ -f "/path/to/unknwon" ] && echo 1 || echo 0)'))
            ->will($this->returnValue(array('0')));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->getPathStatus('/path/to/unknwon');
        $this->assertEquals(0, $aResult);

        $this->assertAttributeEquals(array(), '_aFileStatus', $oMockShell);
    }

    /**
     * @covers Shell_Adapter::sync
     */
    public function testSync_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->sync('foo', 'bar');
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileToLocalDir ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileToLocalDirInKioUnit ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 63 Kio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileToLocalDirInOctetUnit ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): 178 / 640 o
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 640 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileTo2LocalDirs ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
', 'Number of transferred files ( / total): 12 / 2774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29

Number of files: 2774
Number of files transferred: 12
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalEmptySourceToLocalDir ()
    {
        $aExpectedResult = array('Empty source directory.');
        $aRawRsyncResult = array();

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFilesToLocalDir ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('if ls -1 "/srcpath/to/my files" | grep -q .; then rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my files/"* "/destpath/to/my dir"; fi'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my files/*', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFilesToLocalDirWithLeadingSlash ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my files/" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my files/', '/destpath/to/my dir');
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileToLocalDirWithAdditionalExclude ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --exclude="toto" --exclude="titi" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir', array(), array('toto', 'titi', 'toto', '.bzr/'));
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileToLocalDirWithSimpleInclude ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('rsync -axz --delete --include="*.js" --include="*.css" --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --exclude="*" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir', array('*.js', '*.css'), array('*'));
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     * @covers Shell_Adapter::_resumeSyncResult
     */
    public function testSync_LocalFileToRemotesDir ()
    {
        $aExpectedResult = array('Number of transferred files ( / total): 2 / 1774
Total transferred file size ( / total): <1 / 61 Mio
');
        $aRawRsyncResult = explode("\n", 'Number of files: 1774
Number of files transferred: 2
Total file size: 64093953 bytes
Total transferred file size: 178 bytes
Literal data: 178 bytes
Matched data: 0 bytes
File list size: 39177
File list generation time: 0.013 seconds
File list transfer time: 0.000 seconds
Total bytes sent: 39542
Total bytes received: 64

sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
total size is 64093953  speedup is 1618.29');
        $sCmd = 'rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "server1:/destpath/to/my dir" & \\'
            . "\n" . 'rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "login@server2:/destpath/to/my dir" & \\'
            . "\n" . 'wait';

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T server1 /bin/bash <<EOF' . "\n" . 'mkdir -p "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('ssh -T login@server2 /bin/bash <<EOF' . "\n" . 'mkdir -p "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(2))->method('exec')
            ->with($this->equalTo($sCmd))
            ->will($this->returnValue($aRawRsyncResult));
        $oMockShell->expects($this->exactly(3))->method('exec');

        $aResult = $oMockShell->sync('/srcpath/to/my file', array('server1:/destpath/to/my dir', 'login@server2:/destpath/to/my dir'));
        $this->assertEquals(
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aExpectedResult),
            array_map(function($s){return preg_replace('/\s/', '', $s);}, $aResult)
        );
    }

    /**
     * @covers Shell_Adapter::sync
     */
    public function testSync_RemoteDirToRemoteDirWithSameHost ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T user@server /bin/bash <<EOF' . "\n"
                . 'mkdir -p "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('ssh -T user@server /bin/bash <<EOF' . "\n"
                . 'rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --exclude="smarty/*/wrt*" --exclude="smarty/**/wrt*" --stats -e ssh "/srcpath/to/my dir" "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $oMockShell->sync('user@server:/srcpath/to/my dir', 'user@server:/destpath/to/my dir', array(), array('smarty/*/wrt*', 'smarty/**/wrt*'));
    }

    /**
     * @covers Shell_Adapter::sync
     */
    public function testSync_RemoteDirToRemoteDirWithDifferentHost ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T server2 /bin/bash <<EOF' . "\n"
                . 'mkdir -p "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->at(1))->method('exec')
            ->with($this->equalTo('ssh -T user@server1 /bin/bash <<EOF' . "\n"
                . 'rsync -axz --delete --exclude=".bzr/" --exclude=".cvsignore" --exclude=".git/" --exclude=".gitignore" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my dir" "server2:/destpath/to/my dir"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue(array()));
        $oMockShell->expects($this->exactly(2))->method('exec');

        $oMockShell->sync('user@server1:/srcpath/to/my dir', 'server2:/destpath/to/my dir');
    }

    /**
     * @covers Shell_Adapter::sync
     */
    public function testSyncRemoteDirToLocalDirsThrowException () {
        $this->setExpectedException('RuntimeException', 'Not yet implemented!');
        $this->oShell->sync('user@server1:/srcpath/to/my dir', array('/destpath/to/my dir1', '/destpath/to/my dir2'));
    }

    /**
     * @covers Shell_Adapter::createLink
     */
    public function testCreateLink_ThrowExceptionWhenExecFailed ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException('aborted!')));
        $this->setExpectedException('RuntimeException', 'aborted!');
        $oMockShell->createLink('foo', 'bar');
    }

    /**
     * @covers Shell_Adapter::createLink
     */
    public function testCreateLink_ThrowExceptionWhenDifferentHosts1 ()
    {
        $this->setExpectedException(
            'DomainException',
            "Hosts must be equals. Link='/foo'. Target='server:/bar'."
        );
        $this->oShell->createLink('/foo', 'server:/bar');
    }

    /**
     * @covers Shell_Adapter::createLink
     */
    public function testCreateLink_ThrowExceptionWhenDifferentHosts2 ()
    {
        $this->setExpectedException(
            'DomainException',
            "Hosts must be equals. Link='user@server:/foo'. Target='/bar'."
        );
        $this->oShell->createLink('user@server:/foo', '/bar');
    }

    /**
     * @covers Shell_Adapter::createLink
     */
    public function testCreateLink_ThrowExceptionWhenDifferentHosts3 ()
    {
        $this->setExpectedException(
            'DomainException',
            "Hosts must be equals. Link='server1:/foo'. Target='server2:/bar'."
        );
        $this->oShell->createLink('server1:/foo', 'server2:/bar');
    }

    /**
     * @covers Shell_Adapter::createLink
     */
    public function testCreateLink_WithLocalPath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('mkdir -p "$(dirname "/path/to/my file")" && ln -snf "/path/to/my target" "/path/to/my file"'))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->createLink('/path/to/my file', '/path/to/my target');
        $this->assertEquals($aExpectedResult, $aResult);
    }

    /**
     * @covers Shell_Adapter::createLink
     */
    public function testCreateLink_WithRemotePath ()
    {
        $aExpectedResult = array('blabla');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
        $oMockShell->expects($this->at(0))->method('exec')
            ->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'mkdir -p "$(dirname "/path/to/my file")" && ln -snf "/path/to/my target" "/path/to/my file"' . "\n" . 'EOF' . "\n"))
            ->will($this->returnValue($aExpectedResult));
        $oMockShell->expects($this->exactly(1))->method('exec');

        $aResult = $oMockShell->createLink('gaubry@dv2:/path/to/my file', 'gaubry@dv2:/path/to/my target');
        $this->assertEquals($aExpectedResult, $aResult);
    }
}

<?php

/**
 * @covers Shell_Adapter
 */
class ShellTest extends PHPUnit_Framework_TestCase {

	private $oLogger;
	private $oShell;

	public function setUp () {
		$this->oLogger = new Logger_Adapter(Logger_Interface::WARNING);
		$this->oShell = new Shell_Adapter($this->oLogger);
	}

	public function tearDown() {
		$this->oLogger = NULL;
		$this->oShell = NULL;
	}

	public function testIsRemotePathWithEmptyPath () {
		$this->assertEquals(array(false, array('', '', '')), $this->oShell->isRemotePath(''));
	}

	public function testIsRemotePathWithLocalPath () {
		$this->assertEquals(array(false, array('/path/to/my file', '', '/path/to/my file')), $this->oShell->isRemotePath('/path/to/my file'));
	}

	public function testIsRemotePathWithRemotePathWithoutLogin () {
		$this->assertEquals(array(true, array('dv2:/path/to/my file', 'dv2', '/path/to/my file')), $this->oShell->isRemotePath('dv2:/path/to/my file'));
	}

	public function testIsRemotePathWithRemotePathWithLogin () {
		$this->assertEquals(array(true, array('gaubry@dv2:/path/to/my file', 'gaubry@dv2', '/path/to/my file')), $this->oShell->isRemotePath('gaubry@dv2:/path/to/my file'));
	}

	public function testIsRemotePathThrowExceptionWithParameter () {
		$this->setExpectedException('RuntimeException');
		$this->oShell->isRemotePath('${sdg}');
	}



	public function testEscapePathWithEmptyPath () {
		$this->assertEquals('', $this->oShell->escapePath(''));
	}

	public function testEscapePathWithSimplePath () {
		$this->assertEquals('"/path/to/my file"', $this->oShell->escapePath('/path/to/my file'));
	}

	public function testEscapePathWithJokersPath () {
		$this->assertEquals('"/a/b"?"/img"*"jpg"', $this->oShell->escapePath('/a/b?/img*jpg'));
	}

	public function testEscapePathWithConsecutiveJokersPath () {
		$this->assertEquals('"/a/b/img"?*"jpg"', $this->oShell->escapePath('/a/b/img?*jpg'));
	}

	public function testEscapePathWithBoundJokersPath () {
		$this->assertEquals('?"/a/b/img"*', $this->oShell->escapePath('?/a/b/img*'));
	}



	public function testExecThrowExceptionOnShellError () {
		$this->setExpectedException('Exception', "abc\ndef", 101);
		$aResult = $this->oShell->exec('echo abc; echo def; exit 101');
	}

	public function testExecOneLineResult () {
		$aResult = $this->oShell->exec('echo abc');
		$this->assertEquals(array('abc'), $aResult);
	}

	public function testExecMultiLineResult () {
		$aResult = $this->oShell->exec('echo abc; echo def');
		$this->assertEquals(array('abc', 'def'), $aResult);
	}

	public function testExecErrorMultiLineResult () {
		$aResult = $this->oShell->exec('(echo abc; echo def) >&2');
		$this->assertEquals(array('abc', 'def'), $aResult);
	}



	public function testExecSshThrowExceptionWhenExecFailed () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$oMockShell->execSSH('foo', 'bar');
	}

	public function testExecSshWithLocalPath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('ls "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->execSSH('ls %s', '/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testExecSshWithMultipleLocalPath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('ls "/path/to/my file"; cd "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->execSSH('ls %1$s; cd %1$s', '/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testExecSshWithRemotePath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'ls "/path/to/my file"' . "\n" . 'EOF' . "\n"))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->execSSH('ls %s', 'gaubry@dv2:/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testMkdirThrowExceptionWhenExecFailed () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$oMockShell->mkdir('foo');
	}

	public function testMkdirWithLocalPath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('mkdir -p "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->mkdir('/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testMkdirWithRemotePath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'mkdir -p "/path/to/my file"' . "\n" . 'EOF' . "\n"))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->mkdir('gaubry@dv2:/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testRemoveThrowExceptionWhenExecFailed () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$oMockShell->remove('foo/bar');
	}

	public function testRemoveThrowExceptionWhenTooShortPath () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$this->setExpectedException('BadMethodCallException');
		$oMockShell->remove('foo');
	}

	public function testRemoveWithLocalPath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('rm -rf "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->remove('/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testRemoveWithRemotePath () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('ssh -T gaubry@dv2 /bin/bash <<EOF' . "\n" . 'rm -rf "/path/to/my file"' . "\n" . 'EOF' . "\n"))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->remove('gaubry@dv2:/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testCopyThrowExceptionWhenExecFailed () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$oMockShell->copy('foo', 'bar', false);
	}

	public function testCopyLocalFileToLocalDir () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
		$oMockShell->expects($this->at(1))->method('exec')
			->with($this->equalTo('cp -ar "/srcpath/to/my file" "/destpath/to/my dir"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(2))->method('exec');

		$aResult = $oMockShell->copy('/srcpath/to/my file', '/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFilesToLocalDir () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
		$oMockShell->expects($this->at(1))->method('exec')
			->with($this->equalTo('cp -ar "/srcpath/to/"* "/destpath/to/my dir"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(2))->method('exec');

		$aResult = $oMockShell->copy('/srcpath/to/*', '/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFileToLocalFile () {
		$aExpectedResult = array('blabla');

		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to"'));
		$oMockShell->expects($this->at(1))->method('exec')
			->with($this->equalTo('cp -ar "/srcpath/to/my file" "/destpath/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$oMockShell->expects($this->exactly(2))->method('exec');

		$aResult = $oMockShell->copy('/srcpath/to/my file', '/destpath/to/my file', true);
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFileToRemoteDir () {
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

	public function testCopyLocalFileToRemoteFile () {
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



	public function testGetFileStatusThrowExceptionWhenExecFailed () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$oMockShell->getFileStatus('foo');
	}

	public function testGetFileStatusWithFile () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('[ -d "/path/to/my file" ] && echo 2 || ( [ -f "/path/to/my file" ] && echo 1 || echo 0 )'))
			->will($this->returnValue(array('1')));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->getFileStatus('/path/to/my file');
		$this->assertEquals(1, $aResult);
		$this->assertAttributeEquals(array('/path/to/my file' => 1), 'aFileStatus', $oMockShell);

		return $oMockShell;
	}

	/**
	 * @depends testGetFileStatusWithFile
	 */
	public function testGetFileStatusWithFileOnCache (Shell_Adapter $oMockShell) {
		$this->assertAttributeEquals(array('/path/to/my file' => 1), 'aFileStatus', $oMockShell);
		$oMockShell->expects($this->never())->method('exec');
		$aResult = $oMockShell->getFileStatus('/path/to/my file');
		$this->assertEquals(1, $aResult);
	}

	public function testGetFileStatusWithDir () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('[ -d "/path/to/dir" ] && echo 2 || ( [ -f "/path/to/dir" ] && echo 1 || echo 0 )'))
			->will($this->returnValue(array('2')));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->getFileStatus('/path/to/dir');
		$this->assertEquals(2, $aResult);

		$this->assertAttributeEquals(array('/path/to/dir' => 2), 'aFileStatus', $oMockShell);
	}

	public function testGetFileStatusWithUnknown () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('[ -d "/path/to/unknwon" ] && echo 2 || ( [ -f "/path/to/unknwon" ] && echo 1 || echo 0 )'))
			->will($this->returnValue(array('0')));
		$oMockShell->expects($this->exactly(1))->method('exec');

		$aResult = $oMockShell->getFileStatus('/path/to/unknwon');
		$this->assertEquals(0, $aResult);

		$this->assertAttributeEquals(array(), 'aFileStatus', $oMockShell);
	}



	public function testSyncThrowExceptionWhenExecFailed () {
		$oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oLogger));
		$oMockShell->expects($this->exactly(1))->method('exec');
		$oMockShell->expects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$oMockShell->sync('foo', 'bar');
	}

	public function testSyncLocalFileToLocalDir () {
		$aExpectedResult = array('  - Number of transferred files: 2/1774
Total transferred file size: 178/64093953
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

		$oMockShell = $this->getMock('Shell_Adapter', array('exec', 'resumeSyncResult'), array($this->oLogger));
		$oMockShell->expects($this->at(0))->method('exec')
			->with($this->equalTo('mkdir -p "/destpath/to/my dir"'))
			->will($this->returnValue(array()));
		$oMockShell->expects($this->at(1))->method('exec')
			->with($this->equalTo('rsync -az --delete --exclude=.cvsignore --exclude=".bzr/" --exclude=".git/" --exclude=".svn/" --exclude="cvslog.*" --exclude="CVS" --exclude="CVS.adm" --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
			->will($this->returnValue($aRawRsyncResult));
		$oMockShell->expects($this->exactly(2))->method('exec');

		$aResult = $oMockShell->sync('/srcpath/to/my file', '/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}
}

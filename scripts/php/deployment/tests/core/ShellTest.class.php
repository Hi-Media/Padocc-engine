<?php

class ShellTest extends PHPUnit_Framework_TestCase {

	public function testIsRemotePathWithEmptyPath () {
		$this->assertEquals(array(false, array('', '', '')), Shell::isRemotePath(''));
	}

	public function testIsRemotePathWithLocalPath () {
		$this->assertEquals(array(false, array('/path/to/my file', '', '/path/to/my file')), Shell::isRemotePath('/path/to/my file'));
	}

	public function testIsRemotePathWithRemotePath () {
		$this->assertEquals(array(true, array('gaubry@dv2:/path/to/my file', 'gaubry@dv2', '/path/to/my file')), Shell::isRemotePath('gaubry@dv2:/path/to/my file'));
	}



	public function testEscapePathWithEmptyPath () {
		$this->assertEquals('', Shell::escapePath(''));
	}

	public function testEscapePathWithSimplePath () {
		$this->assertEquals('"/path/to/my file"', Shell::escapePath('/path/to/my file'));
	}

	public function testEscapePathWithJokersPath () {
		$this->assertEquals('"/a/b"?"/img"*"jpg"', Shell::escapePath('/a/b?/img*jpg'));
	}

	public function testEscapePathWithConsecutiveJokersPath () {
		$this->assertEquals('"/a/b/img"?*"jpg"', Shell::escapePath('/a/b/img?*jpg'));
	}

	public function testEscapePathWithBoundJokersPath () {
		$this->assertEquals('?"/a/b/img"*', Shell::escapePath('?/a/b/img*'));
	}



	public function testExecSshThrowExceptionWhenExecFailed () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$sMockShell::execSSH('foo', 'bar');
	}

	public function testExecSshWithLocalPath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('ls "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::execSSH('ls %s', '/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testExecSshWithMultipleLocalPath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('ls "/path/to/my file"; cd "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::execSSH('ls %1$s; cd %1$s', '/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testExecSshWithRemotePath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('ssh -T gaubry@dv2 <<EOF' . "\n" . 'ls "/path/to/my file"' . "\n" . 'EOF' . "\n"))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::execSSH('ls %s', 'gaubry@dv2:/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testMkdirThrowExceptionWhenExecFailed () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$sMockShell::mkdir('foo');
	}

	public function testMkdirWithLocalPath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('mkdir -p "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::mkdir('/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testMkdirWithRemotePath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('ssh -T gaubry@dv2 <<EOF' . "\n" . 'mkdir -p "/path/to/my file"' . "\n" . 'EOF' . "\n"))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::mkdir('gaubry@dv2:/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testRemoveThrowExceptionWhenExecFailed () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$sMockShell::remove('foo');
	}

	public function testRemoveWithLocalPath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('rm -rf "/path/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::remove('/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testRemoveWithRemotePath () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('ssh -T gaubry@dv2 <<EOF' . "\n" . 'rm -rf "/path/to/my file"' . "\n" . 'EOF' . "\n"))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::remove('gaubry@dv2:/path/to/my file');
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testCopyThrowExceptionWhenExecFailed () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$sMockShell::copy('foo', 'bar', false);
	}

	public function testCopyLocalFileToLocalDir () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
		$sMockShell::staticExpects($this->at(1))->method('exec')
			->with($this->equalTo('cp -ar "/srcpath/to/my file" "/destpath/to/my dir"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(2))->method('exec');

		$aResult = $sMockShell::copy('/srcpath/to/my file', '/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFilesToLocalDir () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
		$sMockShell::staticExpects($this->at(1))->method('exec')
			->with($this->equalTo('cp -ar "/srcpath/to/"* "/destpath/to/my dir"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(2))->method('exec');

		$aResult = $sMockShell::copy('/srcpath/to/*', '/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFileToLocalFile () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to"'));
		$sMockShell::staticExpects($this->at(1))->method('exec')
			->with($this->equalTo('cp -ar "/srcpath/to/my file" "/destpath/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(2))->method('exec');

		$aResult = $sMockShell::copy('/srcpath/to/my file', '/destpath/to/my file', true);
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFileToRemoteDir () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('ssh -T gaubry@dv2 <<EOF' . "\n" . 'mkdir -p "/destpath/to/my dir"' . "\n" . 'EOF' . "\n"));
		$sMockShell::staticExpects($this->at(1))->method('exec')
			->with($this->equalTo('scp -rpq "/srcpath/to/my file" "gaubry@dv2:/destpath/to/my dir"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(2))->method('exec');

		$aResult = $sMockShell::copy('/srcpath/to/my file', 'gaubry@dv2:/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}

	public function testCopyLocalFileToRemoteFile () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('ssh -T gaubry@dv2 <<EOF' . "\n" . 'mkdir -p "/destpath/to"' . "\n" . 'EOF' . "\n"));
		$sMockShell::staticExpects($this->at(1))->method('exec')
			->with($this->equalTo('scp -rpq "/srcpath/to/my file" "gaubry@dv2:/destpath/to/my file"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(2))->method('exec');

		$aResult = $sMockShell::copy('/srcpath/to/my file', 'gaubry@dv2:/destpath/to/my file', true);
		$this->assertEquals($aExpectedResult, $aResult);
	}



	public function testGetFileStatusThrowExceptionWhenExecFailed () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$sMockShell::getFileStatus('foo');
	}

	public function testGetFileStatusWithFile () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('[ -d "/path/to/my file" ] && echo 2 || ( [ -f "/path/to/my file" ] && echo 1 || echo 0 )'))
			->will($this->returnValue(array('1')));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::getFileStatus('/path/to/my file');
		$this->assertEquals(1, $aResult);

		$oReflection = new ReflectionClass('Shell');
		$aProperties = $oReflection->getStaticProperties();
		$this->assertEquals(1, $aProperties['aFileStatus']['/path/to/my file']);
	}

	/**
	 * @depends testGetFileStatusWithFile
	 */
	public function testGetFileStatusWithFileOnCache () {
		$oReflection = new ReflectionClass('Shell');
		$aProperties = $oReflection->getStaticProperties();
		$this->assertEquals(1, $aProperties['aFileStatus']['/path/to/my file']);

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->never())->method('exec');

		$aResult = $sMockShell::getFileStatus('/path/to/my file');
		$this->assertEquals(1, $aResult);
	}

	public function testGetFileStatusWithDir () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('[ -d "/path/to/dir" ] && echo 2 || ( [ -f "/path/to/dir" ] && echo 1 || echo 0 )'))
			->will($this->returnValue(array('2')));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::getFileStatus('/path/to/dir');
		$this->assertEquals(2, $aResult);

		$oReflection = new ReflectionClass('Shell');
		$aProperties = $oReflection->getStaticProperties();
		$this->assertEquals(2, $aProperties['aFileStatus']['/path/to/dir']);
	}

	public function testGetFileStatusWithUnknown () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')
			->with($this->equalTo('[ -d "/path/to/unknwon" ] && echo 2 || ( [ -f "/path/to/unknwon" ] && echo 1 || echo 0 )'))
			->will($this->returnValue(array('0')));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');

		$aResult = $sMockShell::getFileStatus('/path/to/unknwon');
		$this->assertEquals(0, $aResult);

		$oReflection = new ReflectionClass('Shell');
		$aProperties = $oReflection->getStaticProperties();
		$this->assertNotContains('/path/to/unknwon', array_keys($aProperties['aFileStatus']));
	}



	public function testSyncThrowExceptionWhenExecFailed () {
		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->exactly(1))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->will($this->throwException(new RuntimeException()));
		$this->setExpectedException('RuntimeException');
		$sMockShell::sync('foo', 'bar');
	}

	public function testSyncLocalFileToLocalDir () {
		$aExpectedResult = array('blabla');

		$sMockShell = $this->getMockClass('Shell', array('exec'));
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "/destpath/to/my dir"'));
		$sMockShell::staticExpects($this->at(1))->method('exec')
			->with($this->equalTo('rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/srcpath/to/my file" "/destpath/to/my dir"'))
			->will($this->returnValue($aExpectedResult));
		$sMockShell::staticExpects($this->exactly(2))->method('exec');

		$aResult = $sMockShell::sync('/srcpath/to/my file', '/destpath/to/my dir');
		$this->assertEquals($aExpectedResult, $aResult);
	}
}

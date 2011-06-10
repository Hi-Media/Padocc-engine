<?php

//include_once(DEPLOYMENT_CORE_DIR . '/Shell.class.php');

include_once(__DIR__ . '/../../../../..' . '/deployment/conf/config.inc.php');
set_include_path(
	DEPLOYMENT_CORE_DIR . '/' . PATH_SEPARATOR
	. DEPLOYMENT_INC_DIR . '/' . PATH_SEPARATOR
	. get_include_path());
spl_autoload_register(function($sClass) {
	$sPath = str_replace('_', '/', $sClass) . '.class.php';
	$iPos = strrpos($sPath, '/');
	$sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);
	include_once($sPath);
});

class CopyTest extends PHPUnit_Framework_TestCase {
    public function testXXX () {
        $stack = array();
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));

		$sMockShell = $this->getMockClass('Shell', array('exec'));

		$sMockShell::staticExpects($this->exactly(2))->method('exec');
		$sMockShell::staticExpects($this->at(0))->method('exec')->with($this->equalTo('mkdir -p "dest"'));
		$sMockShell::staticExpects($this->at(1))->method('exec')->with($this->equalTo('cp -ar "src" "dest"'));
		$result = $sMockShell::copy('src', 'dest');
		//echo "Result= $result.";
    }

    /*public static function testToto () {
    	$stack = array();
        $this->assertEquals(0, count($stack));
    }*/
}

<?php

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

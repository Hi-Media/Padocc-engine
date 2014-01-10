<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class LoggerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers Logger_Adapter::log
     */
    public function testLog_WithGreaterLevelError ()
    {
        $oLogger = new Logger_Adapter(Logger_Interface::WARNING);
        ob_start();
        $oLogger->log('A message...', Logger_Interface::ERROR);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('A message...' . "\n", $sOut);
    }

    /**
     * @covers Logger_Adapter::log
     */
    public function testLog_WithEqualLevelError ()
    {
        $oLogger = new Logger_Adapter(Logger_Interface::WARNING);
        ob_start();
        $oLogger->log('A message...', Logger_Interface::WARNING);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('A message...' . "\n", $sOut);
    }

    /**
     * @covers Logger_Adapter::log
     */
    public function testLog_WithLowerLevelError ()
    {
        $oLogger = new Logger_Adapter(Logger_Interface::WARNING);
        ob_start();
        $oLogger->log('A message...', Logger_Interface::INFO);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('', $sOut);
    }
}

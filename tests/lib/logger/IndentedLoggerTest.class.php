<?php

class IndentedLoggerTest extends PHPUnit_Framework_TestCase {

    const BASE_INDENTATION = '----';

    private $oLogger;

    public function setUp () {
        $oRawLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $this->oLogger = new Logger_IndentedDecorator($oRawLogger, self::BASE_INDENTATION);
    }

    public function tearDown() {
        $this->oLogger = NULL;
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWithGreaterLevelError () {
        ob_start();
        $this->oLogger->log('A message...', Logger_Interface::ERROR);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('A message...' . "\n", $sOut);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWithEqualLevelError () {
        ob_start();
        $this->oLogger->log('A message...', Logger_Interface::WARNING);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('A message...' . "\n", $sOut);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWithLowerLevelError () {
        ob_start();
        $this->oLogger->log('A message...', Logger_Interface::INFO);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('', $sOut);
    }
}

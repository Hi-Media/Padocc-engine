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

    /**
     * @covers Logger_IndentedDecorator::log
     * @covers Logger_IndentedDecorator::unindent
     */
    public function testLogWithAbusiveUnindent () {
        ob_start();
        $this->oLogger->unindent()->log('A message...', Logger_Interface::ERROR);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('A message...' . "\n", $sOut);
    }

    /**
     * @covers Logger_IndentedDecorator::indent
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWith2Indents () {
        ob_start();
        $this->oLogger->indent()->indent()->log('A message...', Logger_Interface::ERROR);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(self::BASE_INDENTATION . self::BASE_INDENTATION . 'A message...' . "\n", $sOut);
    }

    /**
     * @covers Logger_IndentedDecorator::indent
     * @covers Logger_IndentedDecorator::log
     * @covers Logger_IndentedDecorator::unindent
     */
    public function testLogWithIndentUnindent () {
        ob_start();
        $this->oLogger->indent()->unindent()->log('A message...', Logger_Interface::ERROR);
        $sOut = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('A message...' . "\n", $sOut);
    }
}

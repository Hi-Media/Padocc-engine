<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY
 */
class IndentedLoggerTest extends PHPUnit_Extensions_OutputTestCase {

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
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->log('A message...', Logger_Interface::ERROR);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWithEqualLevelError () {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->log('A message...', Logger_Interface::WARNING);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWithLowerLevelError () {
        $this->expectOutputString('');
        $this->oLogger->log('A message...', Logger_Interface::INFO);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     * @covers Logger_IndentedDecorator::unindent
     */
    public function testLogWithAbusiveUnindent () {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->unindent()->log('A message...', Logger_Interface::ERROR);
    }

    /**
     * @covers Logger_IndentedDecorator::indent
     * @covers Logger_IndentedDecorator::log
     */
    public function testLogWith2Indents () {
        $this->expectOutputString(self::BASE_INDENTATION . self::BASE_INDENTATION . 'A message...' . "\n");
        $this->oLogger->indent()->indent()->log('A message...', Logger_Interface::ERROR);
    }

    /**
     * @covers Logger_IndentedDecorator::indent
     * @covers Logger_IndentedDecorator::log
     * @covers Logger_IndentedDecorator::unindent
     */
    public function testLogWithIndentUnindent () {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->indent()->unindent()->log('A message...', Logger_Interface::ERROR);
    }
}

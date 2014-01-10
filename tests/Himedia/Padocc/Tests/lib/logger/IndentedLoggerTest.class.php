<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class IndentedLoggerTest extends PHPUnit_Extensions_OutputTestCase
{

    /**
     * Chaîne correspondant à une identation.
     * @var string
     */
    const BASE_INDENTATION = '----';

    /**
     * Instance de log.
     * @var Logger_IndentedInterface
     */
    private $oLogger;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oRawLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $this->oLogger = new Logger_IndentedDecorator($oRawLogger, self::BASE_INDENTATION);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oLogger = NULL;
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLog_WithGreaterLevelError ()
    {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->log('A message...', Logger_Interface::ERROR);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLog_WithEqualLevelError ()
    {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->log('A message...', Logger_Interface::WARNING);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     */
    public function testLog_WithLowerLevelError ()
    {
        $this->expectOutputString('');
        $this->oLogger->log('A message...', Logger_Interface::INFO);
    }

    /**
     * @covers Logger_IndentedDecorator::log
     * @covers Logger_IndentedDecorator::unindent
     */
    public function testLog_WithAbusiveUnindent ()
    {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->unindent()->log('A message...', Logger_Interface::ERROR);
    }

    /**
     * @covers Logger_IndentedDecorator::indent
     * @covers Logger_IndentedDecorator::log
     */
    public function testLog_With2Indents ()
    {
        $this->expectOutputString(self::BASE_INDENTATION . self::BASE_INDENTATION . 'A message...' . "\n");
        $this->oLogger->indent()->indent()->log('A message...', Logger_Interface::ERROR);
    }

    /**
     * @covers Logger_IndentedDecorator::indent
     * @covers Logger_IndentedDecorator::log
     * @covers Logger_IndentedDecorator::unindent
     */
    public function testLog_WithIndentUnindent ()
    {
        $this->expectOutputString('A message...' . "\n");
        $this->oLogger->indent()->unindent()->log('A message...', Logger_Interface::ERROR);
    }
}

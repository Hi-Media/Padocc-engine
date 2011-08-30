<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class ServiceContainerTest extends PHPUnit_Framework_TestCase {

    /**
     * Collection de services.
     * @var ServiceContainer
     */
    private $oServiceContainer;

    public function setUp () {
        $this->oServiceContainer = new ServiceContainer();
    }

    public function tearDown() {
        $this->oServiceContainer = NULL;
    }

    /**
     * @covers ServiceContainer::setLogAdapter
     * @covers ServiceContainer::getLogAdapter
     */
    public function testSetLogAdapter () {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $o = $this->oServiceContainer->setLogAdapter($oLogger)->getLogAdapter();
        $this->assertEquals($oLogger, $o);
    }

    /**
     * @covers ServiceContainer::setPropertiesAdapter
     * @covers ServiceContainer::getPropertiesAdapter
     */
    public function testSetPropertyAdapter () {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($oLogger);
        $oProperties = new Properties_Adapter($oShell);

        $o = $this->oServiceContainer->setPropertiesAdapter($oProperties)->getPropertiesAdapter();
        $this->assertEquals($oProperties, $o);
    }

    /**
     * @covers ServiceContainer::setNumberingAdapter
     * @covers ServiceContainer::getNumberingAdapter
     */
    public function testSetNumberingAdapter () {
        $oNumbering = new Numbering_Adapter();
        $o = $this->oServiceContainer->setNumberingAdapter($oNumbering)->getNumberingAdapter();
        $this->assertEquals($oNumbering, $o);
    }

    /**
     * @covers ServiceContainer::setShellAdapter
     * @covers ServiceContainer::getShellAdapter
     */
    public function testSetShellAdapter () {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($oLogger);

        $o = $this->oServiceContainer->setShellAdapter($oShell)->getShellAdapter();
        $this->assertEquals($oShell, $o);
    }
}

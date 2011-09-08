<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class FactoryTest extends PHPUnit_Framework_TestCase
{

    public function testGetInstance_throwExceptionWhenUnknownType ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($oLogger);

        $this->setExpectedException('BadMethodCallException', "Unknown type: '65484'!");
        Minifier_Factory::getInstance(65484, $oShell);
    }
}

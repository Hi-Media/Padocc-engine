<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class PropertiesTest extends PHPUnit_Framework_TestCase {

    private $oLogger;
    private $oShell;

    public function setUp () {
        $oRawLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $this->oLogger = new Logger_IndentedDecorator($oRawLogger, '----');
        $this->oShell = new Shell_Adapter($this->oLogger);
    }

    public function tearDown() {
        $this->oLogger = NULL;
        $this->oShell = NULL;
    }

    /**
     * @covers Properties_Adapter::getProperty
     */
    public function testGetPropertyThrowExceptionIfUnknownProperty () {
        $oProperties = new Properties_Adapter($this->oShell);
        $this->setExpectedException('UnexpectedValueException');
        $oProperties->getProperty('not_exists');
    }

    /**
     * @covers Properties_Adapter::getProperty
     * @covers Properties_Adapter::setProperty
     */
    public function testGetPropertyIfPropertyExists () {
        $oProperties = new Properties_Adapter($this->oShell);
        $sValue = $oProperties->setProperty('A_PRoperty', 'A value')->getProperty('a_PropERTY');
        $this->assertEquals('A value', $sValue);
    }

    /**
     * @covers Properties_Adapter::getProperty
     * @covers Properties_Adapter::setProperty
     */
    public function testGetPropertyWith2SetProperty () {
        $oProperties = new Properties_Adapter($this->oShell);
        $sValue = $oProperties->setProperty('A_PRoperty', 'A value')
            ->setProperty('A_PRoperty', 'A new value')
            ->getProperty('a_PropERTY');
        $this->assertEquals('A new value', $sValue);
    }

    /**
     * @covers Properties_Adapter::loadConfigIniFile
     */
    public function testLoadConfigIniFileThrowExceptionIfFileNotExists () {
        $oProperties = new Properties_Adapter($this->oShell);
        $this->setExpectedException('UnexpectedValueException');
        $oProperties->loadConfigIniFile('/file/not/exists.ini');
    }

    /**
     * @covers Properties_Adapter::loadConfigIniFile
     */
    public function testLoadConfigIniFileThrowExceptionIfParseFailed () {
        $oProperties = new Properties_Adapter($this->oShell);
        $sTmpPath = tempnam('/tmp', 'deploy_unittest_');
        chmod($sTmpPath, 0222);

        $this->setExpectedException('RuntimeException');
        try {
            $oProperties->loadConfigIniFile($sTmpPath);
        } catch (RuntimeException $oException) {
            unlink($sTmpPath);
            throw $oException;
        } catch (Exception $oException) {
            unlink($sTmpPath);
        }
        @unlink($sTmpPath);
    }

    /**
     * @covers Properties_Adapter::loadConfigIniFile
     */
    public function testLoadConfigIniFileWithValues () {
        $oProperties = new Properties_Adapter($this->oShell);

        $sTmpPath = tempnam('/tmp', 'deploy_unittest_');
        $sContent = <<<EOT
key1=value1
KEY_2 = vAlUE 2
key3 = 'value 3'
key4 = "val'ue 4"
key5 = "val\"ue 5"
EOT;
        file_put_contents($sTmpPath, $sContent);
        $oProperties->loadConfigIniFile($sTmpPath);
        unlink($sTmpPath);

        $this->assertEquals('value1', $oProperties->getProperty('key1'));
        $this->assertEquals('vAlUE 2', $oProperties->getProperty('key_2'));
        $this->assertEquals('value 3', $oProperties->getProperty('key3'));
        $this->assertEquals('val\'ue 4', $oProperties->getProperty('key4'));
        $this->assertEquals('val"ue 5', $oProperties->getProperty('key5'));
    }

    /**
     * @covers Properties_Adapter::loadConfigShellFile
     */
    public function testLoadConfigShellFileThrowExceptionIfFileNotExists () {
        $oProperties = new Properties_Adapter($this->oShell);
        $this->setExpectedException('UnexpectedValueException');
        $oProperties->loadConfigShellFile('/file/not/exists.ini');
    }

    /**
     * @covers Properties_Adapter::loadConfigShellFile
     */
    public function testLoadConfigShellFileWithSimpleValues () {
        $oProperties = new Properties_Adapter($this->oShell);

        $sTmpPath = tempnam('/tmp', 'deploy_unittest_');
        $sContent = <<<EOT
key1="value1"
KEY_2="vAlUE 2"
key3="val'ue 3"
EOT;
        file_put_contents($sTmpPath, $sContent);
        $oProperties->loadConfigShellFile($sTmpPath);
        unlink($sTmpPath);

        $this->assertEquals('value1', $oProperties->getProperty('key1'));
        $this->assertEquals('vAlUE 2', $oProperties->getProperty('key_2'));
        $this->assertEquals('val\'ue 3', $oProperties->getProperty('key3'));
    }

    /**
     * @covers Properties_Adapter::loadConfigShellFile
     */
    public function testLoadConfigShellFileWithRecursiveValues () {
        $oProperties = new Properties_Adapter($this->oShell);

        $sTmpPath = tempnam('/tmp', 'deploy_unittest_');
        $sContent = <<<EOT
k1="v10 v11 v12"
k2="v20 v21"
k3="v3"
k4="\$k1 \$k2"
k5="\$k4 \$k3 v5"
EOT;
        file_put_contents($sTmpPath, $sContent);
        $oProperties->loadConfigShellFile($sTmpPath);
        unlink($sTmpPath);

        $this->assertEquals('v10 v11 v12', $oProperties->getProperty('k1'));
        $this->assertEquals('v10 v11 v12 v20 v21 v3 v5', $oProperties->getProperty('k5'));
    }
}

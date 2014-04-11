<?php

namespace Himedia\Padocc\Tests\Properties;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Properties\Adapter;
use Himedia\Padocc\Properties\PropertiesInterface;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class PropertiesTest extends PadoccTestCase
{

    /**
     * Properties instance.
     * @var PropertiesInterface
     */
    private $oProperties;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $this->oProperties = new Adapter($oShell, $this->aConfig);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oProperties = null;
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::getProperty
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testGetPropertyThrowExceptionIfUnknownProperty ()
    {
        $this->setExpectedException('UnexpectedValueException', "Unknown property 'not_exists'!");
        $this->oProperties->getProperty('not_exists');
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::getProperty
     * @covers \Himedia\Padocc\Properties\Adapter::setProperty
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testGetPropertyIfPropertyExists ()
    {
        $sValue = $this->oProperties->setProperty('A_PRoperty', 'A value')->getProperty('a_PropERTY');
        $this->assertEquals('A value', $sValue);
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::getProperty
     * @covers \Himedia\Padocc\Properties\Adapter::setProperty
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testGetPropertyWith2SetProperty ()
    {
        $sValue = $this->oProperties->setProperty('A_PRoperty', 'A value')
            ->setProperty('A_PRoperty', 'A new value')
            ->getProperty('a_PropERTY');
        $this->assertEquals('A new value', $sValue);
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::loadConfigIniFile
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testLoadConfigIniFileThrowExceptionIfFileNotExists ()
    {
        $this->setExpectedException('UnexpectedValueException', "Property file '/file/not/exists.ini' not found!");
        $this->oProperties->loadConfigIniFile('/file/not/exists.ini');
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::loadConfigIniFile
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testLoadConfigIniFileThrowExceptionIfParseFailed ()
    {
        $sTmpPath = tempnam($this->aConfig['dir']['tmp'], 'deploy_unittest_');
        chmod($sTmpPath, 0222);

        $this->setExpectedException(
            'RuntimeException',
            "Load property file '" . $this->aConfig['dir']['tmp'] . "/deploy_unittest_"
        );
        try {
            $this->oProperties->loadConfigIniFile($sTmpPath);
        } catch (\Exception $oException) {
            unlink($sTmpPath);
            throw $oException;
        }

        if (file_exists($sTmpPath)) {
            unlink($sTmpPath);
        }
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::loadConfigIniFile
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testLoadConfigIniFileWithValues ()
    {
        $sTmpPath = tempnam($this->aConfig['dir']['tmp'], 'deploy_unittest_');
        $sContent = <<<EOT
key1=value1
KEY_2 = vAlUE 2
key3 = 'value 3'
key4 = "val'ue 4"
key5 = "val\"ue 5"
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->oProperties->loadConfigIniFile($sTmpPath);
        unlink($sTmpPath);

        $this->assertEquals('value1', $this->oProperties->getProperty('key1'));
        $this->assertEquals('vAlUE 2', $this->oProperties->getProperty('key_2'));
        $this->assertEquals('value 3', $this->oProperties->getProperty('key3'));
        $this->assertEquals('val\'ue 4', $this->oProperties->getProperty('key4'));
        $this->assertEquals('val"ue 5', $this->oProperties->getProperty('key5'));
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::loadConfigShellFile
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testLoadConfigShellFileThrowExceptionIfFileNotExists ()
    {
        $this->setExpectedException('UnexpectedValueException', "Property file '/file/not/exists.ini' not found!");
        $this->oProperties->loadConfigShellFile('/file/not/exists.ini');
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::loadConfigShellFile
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testLoadConfigShellFileWithSimpleValues ()
    {
        $sTmpPath = tempnam($this->aConfig['dir']['tmp'], 'deploy_unittest_');
        $sContent = <<<EOT
key1="value1"
KEY_2="vAlUE 2"
key3="val'ue 3"
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->oProperties->loadConfigShellFile($sTmpPath);
        unlink($sTmpPath);

        $this->assertEquals('value1', $this->oProperties->getProperty('key1'));
        $this->assertEquals('vAlUE 2', $this->oProperties->getProperty('key_2'));
        $this->assertEquals('val\'ue 3', $this->oProperties->getProperty('key3'));
    }

    /**
     * @covers \Himedia\Padocc\Properties\Adapter::loadConfigShellFile
     * @covers \Himedia\Padocc\Properties\Adapter::__construct
     */
    public function testLoadConfigShellFileWithRecursiveValues ()
    {
        $sTmpPath = tempnam($this->aConfig['dir']['tmp'], 'deploy_unittest_');
        $sContent = <<<EOT
k1="v10 v11 v12"
k2="v20 v21"
k3="v3"
k4="\$k1 \$k2"
k5="\$k4 \$k3 v5"
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->oProperties->loadConfigShellFile($sTmpPath);
        unlink($sTmpPath);

        $this->assertEquals('v10 v11 v12', $this->oProperties->getProperty('k1'));
        $this->assertEquals('v10 v11 v12 v20 v21 v3 v5', $this->oProperties->getProperty('k5'));
    }
}

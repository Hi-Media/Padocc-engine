<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class ToolsTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers Tools::getFileSizeUnit
     * @dataProvider dataProvider_testGetFileSizeUnit
     * @param int $iFileSize taille en octets à changer d'unité
     * @param array $aExpected tableau (int, string) comprenant d'une part le nombre d'octets contenus dans la plus grande
     * unité inférieure à la taille spécifiée, et d'autre part le nom de cette unité.
     */
    public function testGetFileSizeUnit ($iFileSize, $aExpected)
    {
        $aResult = Tools::getFileSizeUnit($iFileSize);
        $this->assertEquals($aExpected, $aResult);
    }

    /**
     * Data provider pour testGetFileSizeUnit()
     */
    public static function dataProvider_testGetFileSizeUnit ()
    {
        return array(
            array(0, array(1, 'o')),
            array(100, array(1, 'o')),
            array(2000, array(1024, 'Kio')),
            array(2000000, array(1024*1024, 'Mio')),
        );
    }

    /**
     * @covers Tools::convertFileSize2String
     * @dataProvider dataProvider_testConvertFileSize2String
     * @param int $iSize taille à convertir
     * @param int $iRefSize référentiel de conversion, si différent de 0
     * @param array $aExpected un couple comprenant d'une part la taille spécifiée arrondie,
     * et d'autre part l'unité dans laquelle la taille a été arrondie.
     */
    public function testConvertFileSize2String ($iSize, $iRefSize, $aExpected)
    {
        $aResult = Tools::convertFileSize2String($iSize, $iRefSize);
        $this->assertEquals($aExpected, $aResult);
    }

    /**
     * Data provider pour testConvertFileSize2String()
     */
    public static function dataProvider_testConvertFileSize2String ()
    {
        return array(
            array(0, 0, array('0', 'o')),
            array(100, 0, array('100', 'o')),
            array(100, 2000000, array('<1', 'Mio')),
            array(2000, 0, array('2', 'Kio')),
            array(2000000, 0, array('2', 'Mio')),
        );
    }
}

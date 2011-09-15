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
     */
    public function testGetFileSizeUnit ($iFileSize, $aExpected)
    {
        $aResult = Tools::getFileSizeUnit($iFileSize);
        $this->assertEquals($aExpected, $aResult);
    }

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
     */
    public function testConvertFileSize2String ($iSize, $iRefSize, $aExpected)
    {
        $aResult = Tools::convertFileSize2String($iSize, $iRefSize);
        $this->assertEquals($aExpected, $aResult);
    }

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

<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TaskProjectTest extends PHPUnit_Framework_TestCase {

    /**
     * @covers Task_Base_Project::getAllProjectsName
     */
    public function testGetAllProjectsNameThrowExceptionIfNotFound () {
        $this->setExpectedException('UnexpectedValueException');
        Task_Base_Project::getAllProjectsName(__DIR__ . '/not_found');
    }

    /**
     * @covers Task_Base_Project::getAllProjectsName
     */
    public function testGetAllProjectsNameThrowExceptionIfBadXml () {
        $this->setExpectedException('RuntimeException');
        Task_Base_Project::getAllProjectsName(__DIR__ . '/resources/2');
    }

    /**
     * @covers Task_Base_Project::getAllProjectsName
     */
    public function testGetAllProjectsName () {
        $aProjectNames = Task_Base_Project::getAllProjectsName(__DIR__ . '/resources/1');
        $this->assertEquals($aProjectNames, array('ebay', 'ptpn', 'rts'));
    }

    /**
     * @covers Task_Base_Project::getSXEProject
     */
    public function testGetSXEProjectThrowExceptionIfNotFound () {
        $this->setExpectedException('UnexpectedValueException');
        Task_Base_Project::getSXEProject(__DIR__ . '/not_found');
    }

    /**
     * @covers Task_Base_Project::getSXEProject
     */
    public function testGetSXEProjectThrowExceptionIfBadXML () {
        $this->setExpectedException('RuntimeException');
        Task_Base_Project::getSXEProject(__DIR__ . '/resources/2/bad_xml.xml');
    }

    /**
     * @covers Task_Base_Project::getSXEProject
     */
    public function testGetSXEProject () {
        $oSXE = Task_Base_Project::getSXEProject(__DIR__ . '/resources/1/ebay.xml');
        $this->assertEquals($oSXE, new SimpleXMLElement(__DIR__ . '/resources/1/ebay.xml', NULL, true));
    }
}

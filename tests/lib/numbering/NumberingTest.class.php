<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY
 */
class NumberingTest extends PHPUnit_Framework_TestCase {

    const SEPARATOR = '#';

    private $oNumbering;

    public function setUp () {
        $this->_oNumbering = new Numbering_Adapter(self::SEPARATOR);
    }

    public function tearDown() {
        $this->_oNumbering = NULL;
    }

    /**
     * @covers Numbering_Adapter::getNextCounterValue
     */
    public function testGetNextCounterValueAtFirstCall () {
        $sCounterValue = $this->_oNumbering->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     */
    public function testGetNextCounterValueAfterAddCounterDivision () {
        $sCounterValue = $this->_oNumbering->addCounterDivision()->getNextCounterValue();
        $this->assertEquals('0' . self::SEPARATOR . '1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterAddAndRemoveCounterDivision () {
        $sCounterValue = $this->_oNumbering->addCounterDivision()->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterRemoveCounterDivision () {
        $sCounterValue = $this->_oNumbering->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterMultipleCalls1 () {
        $this->_oNumbering->getNextCounterValue(); // 1
        $this->_oNumbering
            ->addCounterDivision()   // 1.0
            ->getNextCounterValue();              // 1.1
        $sCounterValue = $this->_oNumbering->getNextCounterValue(); // 1.2
        $this->assertEquals('1' . self::SEPARATOR . '2', $sCounterValue);
        $sCounterValue = $this->_oNumbering
            ->removeCounterDivision() // 1
            ->addCounterDivision()   // 1.2
            ->getNextCounterValue(); // 1.3
        $this->assertEquals('1' . self::SEPARATOR . '3', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterMultipleCalls2 () {
        $this->_oNumbering->getNextCounterValue(); // 1
        $this->_oNumbering
            ->addCounterDivision()   // 1.0
            ->getNextCounterValue();              // 1.1
        $sCounterValue = $this->_oNumbering->getNextCounterValue(); // 1.2
        $this->assertEquals('1' . self::SEPARATOR . '2', $sCounterValue);
        $this->_oNumbering
            ->removeCounterDivision() // 1
            ->getNextCounterValue();              // 2
        $sCounterValue = $this->_oNumbering
            ->addCounterDivision()   // 2.0
            ->getNextCounterValue(); // 1.1
        $this->assertEquals('2' . self::SEPARATOR . '1', $sCounterValue);
    }
}

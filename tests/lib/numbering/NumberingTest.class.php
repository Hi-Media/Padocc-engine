<?php

class NumberingTest extends PHPUnit_Framework_TestCase {

    const SEPARATOR = '#';

    public function setUp () {
        $this->oNumbering = new Numbering_Adapter(self::SEPARATOR);
    }

    public function tearDown() {
        $this->oNumbering = NULL;
    }

    /**
     * @covers Numbering_Adapter::getNextCounterValue
     */
    public function testGetNextCounterValueAtFirstCall () {
        $sCounterValue = $this->oNumbering->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     */
    public function testGetNextCounterValueAfterAddCounterDivision () {
        $sCounterValue = $this->oNumbering->addCounterDivision()->getNextCounterValue();
        $this->assertEquals('0' . self::SEPARATOR . '1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterAddAndRemoveCounterDivision () {
        $sCounterValue = $this->oNumbering->addCounterDivision()->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterRemoveCounterDivision () {
        $sCounterValue = $this->oNumbering->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers Numbering_Adapter::addCounterDivision
     * @covers Numbering_Adapter::getNextCounterValue
     * @covers Numbering_Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterMultipleCalls () {
        $this->oNumbering->getNextCounterValue(); // 1
        $this->oNumbering->addCounterDivision()   // 1.0
            ->getNextCounterValue();              // 1.1
        $sCounterValue = $this->oNumbering->removeCounterDivision() // 1
            ->addCounterDivision()   // 1.1
            ->getNextCounterValue(); // 1.2
        $this->assertEquals('1' . self::SEPARATOR . '2', $sCounterValue);
    }
}

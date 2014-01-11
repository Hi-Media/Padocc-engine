<?php

namespace Himedia\Padocc\Tests\Numbering;

use Himedia\Padocc\Numbering\Adapter;
use Himedia\Padocc\Numbering\NumberingInterface;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class NumberingTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Chaîne intercalée entre chaque niveau hiérarchique
     * @var string
     */
    const SEPARATOR = '#';

    /**
     * Instance de numérotation de tâches.
     * @var NumberingInterface
     */
    private $oNumbering;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $this->oNumbering = new Adapter(self::SEPARATOR);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oNumbering = null;
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     */
    public function testGetNextCounterValue_AtFirstCall ()
    {
        $sCounterValue = $this->oNumbering->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     */
    public function testGetNextCounterValue_AfterAddCounterDivision ()
    {
        $sCounterValue = $this->oNumbering->addCounterDivision()->getNextCounterValue();
        $this->assertEquals('0' . self::SEPARATOR . '1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValue_AfterAddAndRemoveCounterDivision ()
    {
        $sCounterValue = $this->oNumbering->addCounterDivision()->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValue_AfterRemoveCounterDivision ()
    {
        $sCounterValue = $this->oNumbering->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValue_AfterMultipleCalls1 ()
    {
        $this->oNumbering->getNextCounterValue(); // 1
        $this->oNumbering
            ->addCounterDivision()   // 1.0
            ->getNextCounterValue();              // 1.1
        $sCounterValue = $this->oNumbering->getNextCounterValue(); // 1.2
        $this->assertEquals('1' . self::SEPARATOR . '2', $sCounterValue);
        $sCounterValue = $this->oNumbering
            ->removeCounterDivision() // 1
            ->addCounterDivision()   // 1.2
            ->getNextCounterValue(); // 1.3
        $this->assertEquals('1' . self::SEPARATOR . '3', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValue_AfterMultipleCalls2 ()
    {
        $this->oNumbering->getNextCounterValue(); // 1
        $this->oNumbering
            ->addCounterDivision()   // 1.0
            ->getNextCounterValue();              // 1.1
        $sCounterValue = $this->oNumbering->getNextCounterValue(); // 1.2
        $this->assertEquals('1' . self::SEPARATOR . '2', $sCounterValue);
        $this->oNumbering
            ->removeCounterDivision() // 1
            ->getNextCounterValue();              // 2
        $sCounterValue = $this->oNumbering
            ->addCounterDivision()   // 2.0
            ->getNextCounterValue(); // 1.1
        $this->assertEquals('2' . self::SEPARATOR . '1', $sCounterValue);
    }
}

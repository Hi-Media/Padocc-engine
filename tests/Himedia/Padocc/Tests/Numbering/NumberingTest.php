<?php

namespace Himedia\Padocc\Tests\Numbering;

use Himedia\Padocc\Numbering\Adapter;
use Himedia\Padocc\Numbering\NumberingInterface;

/**
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @license Apache License, Version 2.0
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
    public function setUp()
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
    public function testGetNextCounterValueAtFirstCall()
    {
        $sCounterValue = $this->oNumbering->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     */
    public function testGetNextCounterValueAfterAddCounterDivision()
    {
        $sCounterValue = $this->oNumbering->addCounterDivision()->getNextCounterValue();
        $this->assertEquals('0' . self::SEPARATOR . '1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterAddAndRemoveCounterDivision()
    {
        $sCounterValue = $this->oNumbering->addCounterDivision()->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterRemoveCounterDivision()
    {
        $sCounterValue = $this->oNumbering->removeCounterDivision()->getNextCounterValue();
        $this->assertEquals('1', $sCounterValue);
    }

    /**
     * @covers \Himedia\Padocc\Numbering\Adapter::addCounterDivision
     * @covers \Himedia\Padocc\Numbering\Adapter::getNextCounterValue
     * @covers \Himedia\Padocc\Numbering\Adapter::removeCounterDivision
     */
    public function testGetNextCounterValueAfterMultipleCalls1()
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
    public function testGetNextCounterValueAfterMultipleCalls2()
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

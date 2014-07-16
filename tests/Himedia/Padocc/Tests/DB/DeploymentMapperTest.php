<?php

namespace Himedia\Padocc\Tests\DB;

use Himedia\Padocc\DB\DBAdapterInterface;
use Himedia\Padocc\DB\DeploymentMapper;

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
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
 */
class DeploymentMapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \Himedia\Padocc\DB\DeploymentMapper::insert
     * @covers \Himedia\Padocc\DB\DeploymentMapper::__construct
     * @dataProvider dataProviderTestInsert
     */
    public function testInsert (array $aParameters, $sQuery)
    {
        $oMockPDOStmt = $this->getMock('\PDOStatement', array('execute'), array());
        $oMockPDOStmt->expects($this->any())->method('execute')->with(array_values($aParameters));

        /* @var $oMockDB DBAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockDB = $this->getMock('\Himedia\Padocc\DB\PDOAdapter', array('prepare'), array(), '', false);
        $oMockDB->expects($this->any())->method('prepare')
            ->with($sQuery)->will($this->returnValue($oMockPDOStmt));
        $oMapper = new DeploymentMapper($oMockDB);

        $oMapper->insert($aParameters);
    }

    /**
     * Data provider pour testInsert()
     */
    public function dataProviderTestInsert ()
    {
        return array(
            array(array(), ''),
            array(array('a' => 1), 'INSERT INTO deployments (a) VALUES (?)'),
            array(array('a' => 1, 'bb' => 'toto'), 'INSERT INTO deployments (a, bb) VALUES (?,?)'),
        );
    }

    /**
     * @covers \Himedia\Padocc\DB\DeploymentMapper::update
     * @covers \Himedia\Padocc\DB\DeploymentMapper::__construct
     * @dataProvider dataProviderTestUpdate
     */
    public function testUpdateThrowExceptionIfPKMissing ()
    {
        /* @var $oMockDB DBAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockDB = $this->getMock('\Himedia\Padocc\DB\PDOAdapter', array(), array(), '', false);
        $oMapper = new DeploymentMapper($oMockDB);

        $this->setExpectedException('\RuntimeException', 'Missing primary key ');
        $oMapper->update(array('foo' => 'bar'));
    }

    /**
     * @covers \Himedia\Padocc\DB\DeploymentMapper::update
     * @covers \Himedia\Padocc\DB\DeploymentMapper::__construct
     * @dataProvider dataProviderTestUpdate
     */
    public function testUpdate (array $aParameters, $sQuery, array $aValues)
    {
        $oMockPDOStmt = $this->getMock('\PDOStatement', array('execute'), array());
        $oMockPDOStmt->expects($this->any())->method('execute')->with($aValues);

        /* @var $oMockDB DBAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockDB = $this->getMock('\Himedia\Padocc\DB\PDOAdapter', array('prepare'), array(), '', false);
        $oMockDB->expects($this->any())->method('prepare')
            ->with($sQuery)->will($this->returnValue($oMockPDOStmt));
        $oMapper = new DeploymentMapper($oMockDB);

        $oMapper->update($aParameters);
    }

    /**
     * Data provider pour testUpdate()
     */
    public function dataProviderTestUpdate ()
    {
        return array(
            array(array('exec_id' => 1), '', array()),
            array(
                array('exec_id' => 1, 'a' => 'toto'),
                'UPDATE deployments SET a=? WHERE exec_id=?',
                array('toto', 1)
            ),
            array(
                array('bb' => 123, 'exec_id' => 1, 'a' => 'toto'),
                'UPDATE deployments SET bb=?, a=? WHERE exec_id=?',
                array(123, 'toto', 1)
            ),
        );
    }

    /**
     * @covers \Himedia\Padocc\DB\DeploymentMapper::select
     * @covers \Himedia\Padocc\DB\DeploymentMapper::__construct
     * @dataProvider dataProviderTestSelect
     */
    public function testSelect (array $aFilter, array $aOrderBy, $iLimit, $iOffset, $sQuery, array $aValues)
    {
        $oMockPDOStmt = $this->getMock('\PDOStatement', array('execute', 'fetchAll'), array());
        $oMockPDOStmt->expects($this->any())->method('execute')->with($aValues);
        $oMockPDOStmt->expects($this->any())->method('fetchAll')->will($this->returnValue(array('XYZ')));

        /* @var $oMockDB DBAdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $oMockDB = $this->getMock('\Himedia\Padocc\DB\PDOAdapter', array('prepare'), array(), '', false);
        $oMockDB->expects($this->any())->method('prepare')
            ->with($sQuery)->will($this->returnValue($oMockPDOStmt));
        $oMapper = new DeploymentMapper($oMockDB);

        $aResult = $oMapper->select($aFilter, $aOrderBy, $iLimit, $iOffset);
        $this->assertEquals(array('XYZ'), $aResult);
    }

    /**
     * Data provider pour testSelect()
     */
    public function dataProviderTestSelect ()
    {
        return array(
            array(
                array(),
                array(),
                100,
                0,
                'SELECT * FROM deployments   LIMIT 100 OFFSET 0',
                array()
            ),
            array(
                array(array(array('col' => 1))),
                array(),
                100,
                0,
                'SELECT * FROM deployments WHERE (col=?)  LIMIT 100 OFFSET 0',
                array(1)
            ),
            array(
                array(array(array('a' => 1), array('b' => 2)), array(array('c' => 3))),
                array('a ASC', 'b DESC'),
                1,
                10,
                'SELECT * FROM deployments WHERE (a=? OR b=?) AND (c=?) ORDER BY a ASC, b DESC LIMIT 1 OFFSET 10',
                array(1, 2, 3)
            ),
        );
    }
}

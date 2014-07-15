<?php

namespace Himedia\Padocc\Tests;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task;
use Psr\Log\NullLogger;

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
class AttributePropertiesTest extends PadoccTestCase
{

    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oLogger     = new NullLogger();
        $oShell      = new ShellAdapter($oLogger);
        $oProperties = new PropertiesAdapter($oShell, $this->aConfig);
        $oNumbering  = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oShell)
            ->setNumberingAdapter($oNumbering)
            ->setConfig($this->aConfig);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oDIContainer = null;
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::__construct
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheckAttributesWhenNoProperties ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oMockTask->setUp();
        $oMockTask->expects($this->any())->method('check');
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::__construct
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheckAttributesWithNeutralProperty ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 0));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 'bar'));

        $oMockTask->setUp();
        $oMockTask->expects($this->any())->method('check');
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::__construct
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheckAttributesWithMultivaluedProperty ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => AttributeProperties::MULTI_VALUED));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 'a,b, c,  d,e ,f  , g ,  h  ,,'));

        $oMockTask->setUp();
        $oMockTask->expects($this->any())->method('check');
        $this->assertEquals(array('foo' => 'a, b, c, d, e, f, g, h'), $oProperty->getValue($oMockTask));
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::__construct
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheckAttributesWithEmptyProperty ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 0));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => ''));

        $oMockTask->setUp();
        $oMockTask->expects($this->any())->method('check');
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheckUnknownAttributesThrowExceptionIfUnknownAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 'bar'));

        $this->setExpectedException('DomainException', 'Available attributes: ');
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfRequiredAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $this->setExpectedException('UnexpectedValueException', "'srcdir' attribute is required!");
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::formatAttribute
     */
    public function testFormatAttributeWithFile ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => AttributeProperties::FILE));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '\path\to/foo'));

        $oMockTask->setUp();
        $this->assertAttributeEquals(array('srcfile' => '/path/to/foo'), 'aAttValues', $oMockTask);
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::formatAttribute
     */
    public function testFormatAttributeWithDir ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::DIR));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '\path\to/foo/'));

        $oMockTask->setUp();
        $this->assertAttributeEquals(array('srcdir' => '/path/to/foo/'), 'aAttValues', $oMockTask);
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfDirectoryJokerWithoutDirjokerAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => 0));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

        $this->setExpectedException(
            'DomainException',
            "'*' and '?' jokers are not authorized for directory in 'srcdir' attribute!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfFileJokerWithoutFilejokerAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => 0));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

        $this->setExpectedException(
            'DomainException',
            "'*' and '?' jokers are not authorized for filename in 'srcfile' attribute!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfBadURLAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::URL));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'htp://badurl'));

        $this->setExpectedException('DomainException', "Bad URL: 'htp://badurl'");
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfBadEmailAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz'));

        $this->setExpectedException('DomainException', "Email invalid: 'toto.titi@xyz'");
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfGoodEmailsWithoutMultiAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr, aa.bb@xyz.fr'));

        $this->setExpectedException('DomainException', "Email invalid: 'toto.titi@xyz.fr, aa.bb@xyz.fr'");
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfBadBooleanAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::BOOLEAN));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'not a boolean'));

        $this->setExpectedException(
            'DomainException',
            "Value of 'b' attribute is restricted to 'true' or 'false'. Value: 'not a boolean'!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeThrowExceptionIfParameterWithoutAllowparametersAttribute ()
    {
        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => 0));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

        $this->setExpectedException(
            'DomainException',
            "Parameters are not allowed in 'src' attribute! Value: '\${foo}:/bar/'"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheckAttributeParameterThrowExceptionWithSrcpathAttribute ()
    {
        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock(
            '\GAubry\Shell\ShellAdapter',
            array('exec'),
            array($this->oDIContainer->getLogger())
        );
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->returnValue(array('0')));
        $this->oDIContainer->setShellAdapter($oMockShell);

        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => AttributeProperties::SRC_PATH));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => 'foo'));

        $this->setExpectedException(
            'RuntimeException',
            "File or directory 'foo' not found!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::normalizeAttributeProperties
     */
    public function testNormalizeAttributePropertiesWithAttrSrcPath ()
    {
        $aAttrProperties = array(
            'srcpath1' => AttributeProperties::SRC_PATH,
            'srcpath2' => AttributeProperties::SRC_PATH | AttributeProperties::DIR,
            'srcpath3' => AttributeProperties::SRC_PATH | AttributeProperties::FILE,
            'srcpath4' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'other' => 0
        );
        $aExpected = array(
            'srcpath1' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'srcpath2' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'srcpath3' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'srcpath4' => AttributeProperties::SRC_PATH | AttributeProperties::DIR | AttributeProperties::FILE,
            'other' => 0
        );

        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::normalizeAttributeProperties
     */
    public function testNormalizeAttributePropertiesWithAttrFileJoker ()
    {
        $aAttrProperties = array(
            'srcpath1' => AttributeProperties::FILEJOKER,
            'srcpath2' => AttributeProperties::FILEJOKER | AttributeProperties::FILE,
            'other' => 0
        );
        $aExpected = array(
            'srcpath1' => AttributeProperties::FILEJOKER | AttributeProperties::FILE,
            'srcpath2' => AttributeProperties::FILEJOKER | AttributeProperties::FILE,
            'other' => 0
        );

        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }

    /**
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::normalizeAttributeProperties
     */
    public function testNormalizeAttributePropertiesWithAttrDirJoker ()
    {
        $aAttrProperties = array(
            'srcpath1' => AttributeProperties::DIRJOKER,
            'srcpath2' => AttributeProperties::DIRJOKER | AttributeProperties::DIR,
            'other' => 0
        );
        $aExpected = array(
            'srcpath1' => AttributeProperties::DIRJOKER | AttributeProperties::DIR,
            'srcpath2' => AttributeProperties::DIRJOKER | AttributeProperties::DIR,
            'other' => 0
        );

        /* @var $oMockTask Task|\PHPUnit_Framework_MockObject_MockObject */
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass(
            '\Himedia\Padocc\Task',
            array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer)
        );
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }
}

<?php

namespace Himedia\Padocc\Tests;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class TaskTest extends PadoccTestCase
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
        $oBaseLogger = new Logger_Adapter(LoggerInterface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

        $oShell = new Shell_Adapter($oLogger);
        $oProperties = new Properties_Adapter($oShell);
        $oNumbering = new Numbering_Adapter();

        $this->oDIContainer = new ServiceContainer();
        $this->oDIContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oShell)
            ->setNumberingAdapter($oNumbering);
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
     * @covers Task::expandPath
     */
    public function testExpandPath_WithSimpleString ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('test'));
        $this->assertEquals(array('test'), $aResult);
    }

    /**
     * @covers Task::expandPath
     */
    public function testExpandPath_WithOneSimpleParameter ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('simple_value'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
        $this->assertEquals(array('foosimple_valuebar'), $aResult);
    }

    /**
     * @covers Task::expandPath
     */
    public function testExpandPath_WithOneComplexParameter ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('123 three values'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('foo${p}bar'));
        $this->assertEquals(array('foo123bar', 'foothreebar', 'foovaluesbar'), $aResult);
    }

    /**
     * @covers Task::expandPath
     */
    public function testExpandPath_WithTwoComplexParameters ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('p'))
            ->will($this->returnValue('123 three values'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('q'))
            ->will($this->returnValue('0 1'));
        $oMockProperties->expects($this->exactly(2))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('${p}foo${q}'));
        $this->assertEquals(array(
            '123foo0', '123foo1',
            'threefoo0', 'threefoo1',
            'valuesfoo0', 'valuesfoo1',
        ), $aResult);
    }

    /**
     * @covers Task::expandPath
     */
    public function testExpandPath_WithMultiComplexParameters ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('one'))
            ->will($this->returnValue('${two} ${three} four'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('two'))
            ->will($this->returnValue('a b'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('three'))
            ->will($this->returnValue('five ${two}'));
        $oMockProperties->expects($this->at(3))->method('getProperty')
            ->with($this->equalTo('two'))
            ->will($this->returnValue('a b'));
        $oMockProperties->expects($this->exactly(4))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $class = new ReflectionClass($oMockTask);
        $method = $class->getMethod('expandPath');
        $method->setAccessible(true);

        $aResult = $method->invokeArgs($oMockTask, array('${one}foo'));
        $this->assertEquals(array(
            'afoo',
            'bfoo',
            'fivefoo',
            'fourfoo'
        ), $aResult);
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     */
    public function testCheck_EmptyNotThrowException ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_ThrowExceptionIfUnknownAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('foo' => 'bar'));

        $this->setExpectedException('DomainException', 'Available attributes: ');
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfRequiredAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $this->setExpectedException('UnexpectedValueException', "'srcdir' attribute is required!");
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     * @covers AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_RequiredAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => 'foo'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     * @covers AttributeProperties::formatAttribute
     * @covers AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_FileAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => AttributeProperties::FILE));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '\path\to/foo'));

        $oMockTask->setUp();
        $this->assertAttributeEquals(array('srcfile' => '/path/to/foo'), 'aAttributes', $oMockTask);
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     * @covers AttributeProperties::formatAttribute
     * @covers AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_DirAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::DIR));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '\path\to/foo/'));

        $oMockTask->setUp();
        $this->assertAttributeEquals(array('srcdir' => '/path/to/foo/'), 'aAttributes', $oMockTask);
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfDirectoryJokerWithoutDirjokerAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => array()));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

        $this->setExpectedException(
            'DomainException',
            "'*' and '?' jokers are not authorized for directory in 'srcdir' attribute!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_DirectoryJokerWithDirjokerAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::DIRJOKER));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfFileJokerWithoutFilejokerAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => array()));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

        $this->setExpectedException(
            'DomainException',
            "'*' and '?' jokers are not authorized for filename in 'srcfile' attribute!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfBadURLAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::URL));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'htp://badurl'));

        $this->setExpectedException('DomainException', "Bad URL: 'htp://badurl'");
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_WithURLAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::URL));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'http://url/?a=b#c'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfBadEmailAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz'));

        $this->setExpectedException('DomainException', "Email invalid: 'toto.titi@xyz'");
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfGoodEmailsWithoutMultiAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr, aa.bb@xyz.fr'));

        $this->setExpectedException('DomainException', "Email invalid: 'toto.titi@xyz.fr, aa.bb@xyz.fr'");
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_WithOnly1GoodEmailWithMultiAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL | AttributeProperties::MULTI_VALUED));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_WithGoodEmailsWithMultiAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL | AttributeProperties::MULTI_VALUED));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr ,  aa.bb@xyz.fr'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_WithEmailAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfBadBooleanAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::BOOLEAN));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'not a boolean'));

        $this->setExpectedException(
            'DomainException',
            "Value of 'b' attribute is restricted to 'true' or 'false'. Value: 'not a boolean'!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     * @covers AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_BooleanAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'b_true' => AttributeProperties::BOOLEAN,
            'b_false' => AttributeProperties::BOOLEAN
        ));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b_true' => 'true'));
        $oProperty->setValue($oMockTask, array('b_false' => 'false'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_FileJokerWithFilejokerAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => AttributeProperties::FILEJOKER));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfParameterWithoutAllowparametersAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => array()));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

        $this->setExpectedException(
            'DomainException',
            "Parameters are not allowed in 'src' attribute! Value: '\${foo}:/bar/'"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::checkAttribute
     */
    public function testCheck_ParameterWithAllowparametersAttribute ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => AttributeProperties::ALLOW_PARAMETER));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

        $oMockTask->setUp();
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     */
    public function testCheck_ParameterThrowExceptionWithSrcpathAttribute ()
    {
        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($this->oDIContainer->getLogAdapter()));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->returnValue(array('0')));
        $this->oDIContainer->setShellAdapter($oMockShell);

        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => AttributeProperties::SRC_PATH));

        $oProperty = $o->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => 'foo'));

        $this->setExpectedException(
            'RuntimeException',
            "File or directory 'foo' not found!"
        );
        $oMockTask->setUp();
    }

    /**
     * @covers Task::getTagName
     */
    public function testGetTagName_ThrowException ()
    {
        $this->setExpectedException('RuntimeException', 'Not implemented at this level!');
        Task::getTagName();
    }

    /**
     * @covers Task::getNewInstance
     */
    public function testGetNewInstance_ThrowException ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $this->setExpectedException('RuntimeException', 'Not implemented at this level!');
        Task::getNewInstance(array(), $oMockProject, $this->oDIContainer);
    }

    /**
     * @covers Task::getNewInstance
     * @covers Task::fetchAttributes
     * @covers Task::__construct
     */
    public function testGetNewInstance_Ok ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oTaskCopy = Task_Base_Copy::getNewInstance(array('attr1' => 'v1', 'attr2' => 'v2'), $oMockProject, $this->oDIContainer);
        $this->assertAttributeEquals(array('attr1' => 'v1', 'attr2' => 'v2'), 'aAttributes', $oTaskCopy);
    }

    /**
     * @covers Task::reroutePaths
     */
    public function testReroutePaths_WithoutSymlinksWithEmptyPath ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array()));
        $this->assertEquals(array(), $aResult);
    }

    /**
     * @covers Task::reroutePaths
     */
    public function testReroutePaths_WithoutSymlinks ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('false'));
        $oMockProperties->expects($this->exactly(1))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array('/path/to/my_dir', 'user@server:/other/path')));
        $this->assertEquals(array('/path/to/my_dir', 'user@server:/other/path'), $aResult);
    }

    /**
     * @covers Task::reroutePaths
     */
    public function testReroutePaths_WithSymlinksWithEmptyPath ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue('/path/to/base_dir'));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aResult = $oMethod->invokeArgs($oMockTask, array(array()));
        $this->assertEquals(array(), $aResult);
    }

    /**
     * @covers Task::reroutePaths
     */
    public function testReroutePaths_WithSymlinksWithNoReroute ()
    {
        $sBaseDir = '/path/to/basedir';
        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue($sBaseDir));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));

        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);

        $aSrc = array(
            $sBaseDir .'trapped',
            '/path/to/elsewhere',
            '/bad' . $sBaseDir,
            'user@server:/other/path',
            'user@server:/bad' . $sBaseDir
        );
        $aDest = $aSrc;
        $aResult = $oMethod->invokeArgs($oMockTask, array($aSrc));
        $this->assertEquals($aDest, $aResult);
    }

    /**
     * @covers Task::reroutePaths
     */
    public function testReroutePaths_WithSymlinksWithReroute ()
    {
        $sBaseDir = '/path/to/basedir';
        $aSrc = array(
            $sBaseDir,
            $sBaseDir . '/subdir',
            'user@server:' . $sBaseDir,
            'user@server:' . $sBaseDir . '/subdir'
        );
        $aDest = array(
            $sBaseDir,
            $sBaseDir . '/subdir',
            'user@server:' . $sBaseDir . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX . '/12345',
            'user@server:' . $sBaseDir . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX . '/12345/subdir'
        );

        $oMockProject = $this->getMock('Project', array(), array(), '', false);

        $oMockProperties = $this->getMock('Adapter', array('getProperty'), array(), '', false);
        $oMockProperties->expects($this->at(0))->method('getProperty')
            ->with($this->equalTo('with_symlinks'))
            ->will($this->returnValue('true'));
        $oMockProperties->expects($this->at(1))->method('getProperty')
            ->with($this->equalTo('basedir'))
            ->will($this->returnValue($sBaseDir));
        $oMockProperties->expects($this->at(2))->method('getProperty')
            ->with($this->equalTo('execution_id'))
            ->will($this->returnValue('12345'));
        $oMockProperties->expects($this->exactly(3))->method('getProperty');
        $this->oDIContainer->setPropertiesAdapter($oMockProperties);

        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new ReflectionClass($oMockTask);
        $oMethod = $oClass->getMethod('reroutePaths');
        $oMethod->setAccessible(true);
        $aResult = $oMethod->invokeArgs($oMockTask, array($aSrc));
        $this->assertEquals($aDest, $aResult);
    }

    /**
     * @covers Task::registerPaths
     */
    public function testRegisterPaths ()
    {
        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aRegisteredPaths');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array());

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath' => AttributeProperties::DIR | AttributeProperties::FILE,
            'srcdir' => AttributeProperties::DIR,
            'srcfile' => AttributeProperties::FILE,
            'other' => 0
        ));

        $oProperty = $oClass->getProperty('aAttributes');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'srcpath' => '/path/to/srcpath',
            'srcdir' => 'user@server:/path/to/srcdir',
            'srcfile' => '/path/to/srcfile',
            'other' => '/path/to/other',
        ));

        $oMethod = $oClass->getMethod('registerPaths');
        $oMethod->setAccessible(true);
        $oMethod->invokeArgs($oMockTask, array());
        $this->assertAttributeEquals(array(
            'user@server:/path/to/srcdir' => true,
            '/path/to/srcfile' => true,
            '/path/to/srcpath' => true
        ), 'aRegisteredPaths', 'Task');
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::normalizeAttributeProperties
     */
    public function testCheck_NormalizeAttributeProperties_WithAttrSrcPath ()
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

        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::normalizeAttributeProperties
     */
    public function testCheck_NormalizeAttributeProperties_WithAttrFileJoker ()
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

        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }

    /**
     * @covers Task::check
     * @covers AttributeProperties::checkAttributes
     * @covers AttributeProperties::normalizeAttributeProperties
     */
    public function testCheck_NormalizeAttributeProperties_WithAttrDirJoker ()
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

        $oMockProject = $this->getMock('Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('Task', array(new SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }
}

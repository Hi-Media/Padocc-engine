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
 * TODO copier/coller des tests de TaskTest couvrant abusivement AttributeProperties.
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     */
    public function testCheck_WhenEmpty ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_ThrowExceptionIfUnknownAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfRequiredAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_RequiredAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::REQUIRED));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => 'foo'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::formatAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_FileAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::formatAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_DirAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfDirectoryJokerWithoutDirjokerAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => array()));

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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_DirectoryJokerWithDirjokerAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => AttributeProperties::DIRJOKER));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcdir' => '/foo*XXX/'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfFileJokerWithoutFilejokerAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => array()));

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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfBadURLAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_WithURLAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::URL));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'http://url/?a=b#c'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfBadEmailAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfGoodEmailsWithoutMultiAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_WithOnly1GoodEmailWithMultiAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL | AttributeProperties::MULTI_VALUED));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_WithGoodEmailsWithMultiAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL | AttributeProperties::MULTI_VALUED));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr ,  aa.bb@xyz.fr'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_WithEmailAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => AttributeProperties::EMAIL));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b' => 'toto.titi@xyz.fr'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfBadBooleanAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     * @covers \Himedia\Padocc\AttributeProperties::checkUnknownAttributes
     */
    public function testCheck_BooleanAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array(
            'b_true' => AttributeProperties::BOOLEAN,
            'b_false' => AttributeProperties::BOOLEAN
        ));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('b_true' => 'true'));
        $oProperty->setValue($oMockTask, array('b_false' => 'false'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_FileJokerWithFilejokerAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => AttributeProperties::FILEJOKER));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('srcfile' => '/foo/*'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ThrowExceptionIfParameterWithoutAllowparametersAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => array()));

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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::checkAttribute
     */
    public function testCheck_ParameterWithAllowparametersAttribute ()
    {
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $o = new \ReflectionClass($oMockTask);

        $oProperty = $o->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => AttributeProperties::ALLOW_PARAMETER));

        $oProperty = $o->getProperty('aAttValues');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, array('src' => '${foo}:/bar/'));

        $oMockTask->setUp();
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     */
    public function testCheck_ParameterThrowExceptionWithSrcpathAttribute ()
    {
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($this->oDIContainer->getLogger()));
        $oMockShell->expects($this->exactly(1))->method('exec');
        $oMockShell->expects($this->at(0))->method('exec')->will($this->returnValue(array('0')));
        $this->oDIContainer->setShellAdapter($oMockShell);

        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
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
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::normalizeAttributeProperties
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

        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::normalizeAttributeProperties
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

        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }

    /**
     * @covers \Himedia\Padocc\Task::check
     * @covers \Himedia\Padocc\AttributeProperties::checkAttributes
     * @covers \Himedia\Padocc\AttributeProperties::normalizeAttributeProperties
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

        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
        $oMockTask = $this->getMockForAbstractClass('\Himedia\Padocc\Task', array(new \SimpleXMLElement('<foo />'), $oMockProject, $this->oDIContainer));
        $oClass = new \ReflectionClass($oMockTask);

        $oProperty = $oClass->getProperty('aAttrProperties');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockTask, $aAttrProperties);

        $oMockTask->setUp();
        $this->assertAttributeEquals($aExpected, 'aAttrProperties', $oMockTask);
    }
}

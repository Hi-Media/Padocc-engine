<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\ExternalProperty;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class ExternalPropertyTest extends PadoccTestCase
{

    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Tableau indexé contenant les commandes Shell de tous les appels effectués à Shell_Adapter::exec().
     * @var array
     * @see shellExecCallback()
     */
    private $aShellExecCmds;

    /**
     * Callback déclenchée sur appel de Shell_Adapter::exec().
     * Log tous les appels dans le tableau indexé $this->aShellExecCmds.
     *
     * @param string $sCmd commande Shell qui aurait dûe être exécutée.
     * @see $aShellExecCmds
     */
   public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oLogger = new NullLogger();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')
            ->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);
        $oNumbering = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
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
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::__construct
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::centralExecute
     */
    public function testCentralExecute_throwExceptionIfPropertyNotFound ()
    {
        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $oTask = ExternalProperty::getNewInstance(
            array(
                'name' => 'not_exists',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );

        $this->setExpectedException('UnexpectedValueException', "Property 'not_exists' undefined!");
        $oTask->setUp();
        $oTask->execute();
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::__construct
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::centralExecute
     */
    public function testCentralExecute_With1Property ()
    {
        $oClass = new \ReflectionClass('\Himedia\Padocc\Properties\Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            ExternalProperty::EXTERNAL_PROPERTY_PREFIX . '1' => 'value 1'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $oClass = new \ReflectionClass('\Himedia\Padocc\Task\Base\ExternalProperty');
        $oProperty = $oClass->getProperty('iCounter');
        $oProperty->setAccessible(true);
        $oProperty->setValue(null, 0);

        $oTask = ExternalProperty::getNewInstance(
            array(
                'name' => 'my_property',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );

        $oTask->setUp();
        $oTask->execute();
        $this->assertEquals('value 1', $oPropertiesAdapter->getProperty('my_property'));
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::__construct
     * @covers \Himedia\Padocc\Task\Base\ExternalProperty::centralExecute
     */
    public function testCentralExecute_WithProperties ()
    {
        $oClass = new \ReflectionClass('\Himedia\Padocc\Properties\Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            ExternalProperty::EXTERNAL_PROPERTY_PREFIX . '1' => 'value 1',
            ExternalProperty::EXTERNAL_PROPERTY_PREFIX . '2' => 'other'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        /* @var $oMockProject Project|\PHPUnit_Framework_MockObject_MockObject */
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new \SimpleXMLElement($sXML)));

        $oClass = new \ReflectionClass('\Himedia\Padocc\Task\Base\ExternalProperty');
        $oProperty = $oClass->getProperty('iCounter');
        $oProperty->setAccessible(true);
        $oProperty->setValue(null, 0);

        $oTask1 = ExternalProperty::getNewInstance(
            array(
                'name' => 'my_property',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );
        $oTask2 = ExternalProperty::getNewInstance(
            array(
                'name' => 'second',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );
        $oTask1->setUp();
        $oTask2->setUp();
        $oTask1->execute();
        $oTask2->execute();

        $this->assertEquals('value 1', $oPropertiesAdapter->getProperty('my_property'));
        $this->assertEquals('other', $oPropertiesAdapter->getProperty('second'));
    }
}

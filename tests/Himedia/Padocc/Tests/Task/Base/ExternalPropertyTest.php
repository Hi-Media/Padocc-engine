<?php

namespace Himedia\Padocc\Tests\Task\Base;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class ExternalPropertyTest extends \PHPUnit_Framework_TestCase
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
        $oBaseLogger = new Logger_Adapter(LoggerInterface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

        $oMockShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')
            ->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oProperties = new Properties_Adapter($oMockShell);
        $oNumbering = new Numbering_Adapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oDIContainer = null;
        $this->oMockProject = null;
    }

    /**
     * @covers Task_Base_ExternalProperty::__construct
     * @covers Task_Base_ExternalProperty::centralExecute
     */
    public function testCentralExecute_throwExceptionIfPropertyNotFound ()
    {
        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new SimpleXMLElement($sXML)));

        $oTask = Task_Base_ExternalProperty::getNewInstance(
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
     * @covers Task_Base_ExternalProperty::__construct
     * @covers Task_Base_ExternalProperty::centralExecute
     */
    public function testCentralExecute_With1Property ()
    {
        $oClass = new ReflectionClass('Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . '1' => 'value 1'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new SimpleXMLElement($sXML)));

        $oClass = new ReflectionClass('ExternalProperty');
        $oProperty = $oClass->getProperty('_iCounter');
        $oProperty->setAccessible(true);
        $oProperty->setValue(null, 0);

        $oTask = Task_Base_ExternalProperty::getNewInstance(
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
     * @covers Task_Base_ExternalProperty::__construct
     * @covers Task_Base_ExternalProperty::centralExecute
     */
    public function testCentralExecute_WithProperties ()
    {
        $oClass = new ReflectionClass('Adapter');
        $oProperty = $oClass->getProperty('aProperties');
        $oProperty->setAccessible(true);
        $oPropertiesAdapter = $this->oDIContainer->getPropertiesAdapter();
        $oProperty->setValue($oPropertiesAdapter, array(
            Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . '1' => 'value 1',
            Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . '2' => 'other'
        ));
        $this->oDIContainer->setPropertiesAdapter($oPropertiesAdapter);

        $sXML = '<project></project>';
        $oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new SimpleXMLElement($sXML)));

        $oClass = new ReflectionClass('ExternalProperty');
        $oProperty = $oClass->getProperty('_iCounter');
        $oProperty->setAccessible(true);
        $oProperty->setValue(null, 0);

        $oTask1 = Task_Base_ExternalProperty::getNewInstance(
            array(
                'name' => 'my_property',
                'description' => '...'
            ),
            $oMockProject,
            $this->oDIContainer
        );
        $oTask2 = Task_Base_ExternalProperty::getNewInstance(
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

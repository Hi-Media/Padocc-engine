<?php

namespace Himedia\Padocc\Tests\Task\Base;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class CallTest extends \PHPUnit_Framework_TestCase
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

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')
            ->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        //$oShell = new Shell_Adapter($oLogger);
        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));

        //$oShell = new Shell_Adapter($oLogger);
        $oProperties = new Properties_Adapter($oMockShell);
        $oNumbering = new Numbering_Adapter();

        $this->oDIContainer = new ServiceContainer();
        $this->oDIContainer
            ->setLogAdapter($oLogger)
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
     * @covers Task_Base_Call::__construct
     */
    public function testNew_ThrowExceptionIfTargetNotFound ()
    {
        $sXML = '<target name="my_target"></target>';
        $oMockProject = $this->getMock('Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new SimpleXMLElement($sXML)));

        $this->setExpectedException(
            'UnexpectedValueException',
            "Target 'not_exists' not found or not unique in this project!"
        );
        $oTask = Task_Base_Call::getNewInstance(
            array('target' => 'not_exists'),
            $oMockProject,
            $this->oDIContainer
        );
    }

    /**
     * @covers Task_Base_Call::__construct
     */
    public function testNew_ThrowExceptionIfTargetNotUnique ()
    {
        $sXML = '<project><target name="my_target"></target><target name="my_target"></target></project>';
        $oMockProject = $this->getMock('Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new SimpleXMLElement($sXML)));

        $this->setExpectedException(
            'UnexpectedValueException',
            "Target 'my_target' not found or not unique in this project!"
        );
        $oTask = Task_Base_Call::getNewInstance(
            array('target' => 'my_target'),
            $oMockProject,
            $this->oDIContainer
        );
    }

    /**
     * @covers Task_Base_Call::__construct
     */
    public function testNew ()
    {
        $sXML = '<project><target name="my_target"></target></project>';
        $oMockProject = $this->getMock('Project', array('getSXE'), array(), '', false);
        $oMockProject->expects($this->any())->method('getSXE')
            ->will($this->returnValue(new SimpleXMLElement($sXML)));

        $oCallTask = Task_Base_Call::getNewInstance(
            array('target' => 'my_target'),
            $oMockProject,
            $this->oDIContainer
        );
        $oTargetTask = Task_Base_Target::getNewInstance(
            array('name' => 'my_target'),
            $oMockProject,
            $this->oDIContainer
        );

        $this->assertAttributeEquals(array('name' => 'my_target'), 'aAttributes', $oTargetTask);
    }
}

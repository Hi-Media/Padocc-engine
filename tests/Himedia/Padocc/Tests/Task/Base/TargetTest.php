<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Target;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class TargetTest extends PadoccTestCase
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
        $oMockShell->expects($this->any())->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $this->aShellExecCmds = array();

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/file' => 1
        ));

        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);

        $oNumbering = new NumberingAdapter();

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
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfBadXML ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        $sXmlProjectConf = file_get_contents($this->aConfig['dir']['tests'] . '/resources/base_target/bad_xml.xml');
        Target::getAvailableEnvsList($sXmlProjectConf);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfNoEnv ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "No environment found in "
        );
        $sXmlProjectConf = file_get_contents($this->aConfig['dir']['tests'] . '/resources/base_target/project_without_env.xml');
        Target::getAvailableEnvsList($sXmlProjectConf);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfInvalidProperty ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Invalid external property in "
        );
        $sXmlProjectConf = file_get_contents($this->aConfig['dir']['tests'] . '/resources/base_target/project_env_withinvalidproperty.xml');
        Target::getAvailableEnvsList($sXmlProjectConf);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     * @covers \Himedia\Padocc\Task\Base\Target::getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_ThrowExceptionIfInvalidTarget ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Target 'invalid' not found or not unique in this project!"
        );
        $sXmlProjectConf = file_get_contents($this->aConfig['dir']['tests'] . '/resources/base_target/project_env_withinvalidtarget.xml');
        Target::getAvailableEnvsList($sXmlProjectConf);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     * @covers \Himedia\Padocc\Task\Base\Target::getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithEmptyEnv ()
    {
        $aExpected = array(
            'my_env' => array()
        );
        $sProjectPath = $this->aConfig['dir']['tests'] . '/resources/base_target/project_env_empty.xml';
        $sXmlProjectConf = file_get_contents($sProjectPath);
        $aEnvsList = Target::getAvailableEnvsList($sXmlProjectConf);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     * @covers \Himedia\Padocc\Task\Base\Target::getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithoutExtProperty ()
    {
        $aExpected = array(
            'my_env' => array()
        );
        $sProjectPath = $this->aConfig['dir']['tests'] . '/resources/base_target/project_env_without_extproperty.xml';
        $sXmlProjectConf = file_get_contents($sProjectPath);
        $aEnvsList = Target::getAvailableEnvsList($sXmlProjectConf);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     * @covers \Himedia\Padocc\Task\Base\Target::getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithOneProperty ()
    {
        $aExpected = array(
            'my_env' => array('ref' => 'Branch or tag to deploy')
        );
        $sProjectPath = $this->aConfig['dir']['tests'] . '/resources/base_target/project_env_withoneproperty.xml';
        $sXmlProjectConf = file_get_contents($sProjectPath);
        $aEnvsList = Target::getAvailableEnvsList($sXmlProjectConf);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     * @covers \Himedia\Padocc\Task\Base\Target::getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithProperties ()
    {
        $aExpected = array(
            'my_env' => array(
                'ref' => 'Branch or tag to deploy',
                'ref2' => 'label',
            )
        );
        $sProjectPath = $this->aConfig['dir']['tests'] . '/resources/base_target/project_env_withproperties.xml';
        $sXmlProjectConf = file_get_contents($sProjectPath);
        $aEnvsList = Target::getAvailableEnvsList($sXmlProjectConf);
        $this->assertEquals($aExpected, $aEnvsList);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Target::getAvailableEnvsList
     * @covers \Himedia\Padocc\Task\Base\Target::getSXEExternalProperties
     */
    public function testGetAvailableEnvsList_WithCallAndProperties ()
    {
        $aExpected = array(
            'my_env' => array(
                'ref' => 'Branch or tag to deploy',
                'ref2' => 'label',
                'ref3' => 'other...',
            )
        );
        $sProjectPath = $this->aConfig['dir']['tests'] . '/resources/base_target/project_env_withcallandproperties.xml';
        $sXmlProjectConf = file_get_contents($sProjectPath);
        $aEnvsList = Target::getAvailableEnvsList($sXmlProjectConf);
        $this->assertEquals($aExpected, $aEnvsList);
    }
}

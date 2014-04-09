<?php

namespace Himedia\Padocc\Tests\Task\Base;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;
use Psr\Log\NullLogger;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class ProjectTest extends PadoccTestCase
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
        $oMockShell->expects($this->any())
            ->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
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
     * @covers \Himedia\Padocc\Task\Base\Project::getAllProjectsName
     */
    public function testGetAllProjectsNameThrowExceptionIfNotFound ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Resource path not found: '"
        );
        Project::getAllProjectsName($this->aConfig['dir']['tmp'] . '/not_exists_lzujlkrehvls');
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::getAllProjectsName
     */
    public function testGetAllProjectsNameThrowExceptionIfBadXml ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        Project::getAllProjectsName($this->aConfig['dir']['tests'] . '/resources/base_project/2');
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::getAllProjectsName
     */
    public function testGetAllProjectsName ()
    {
        $aProjectNames = Project::getAllProjectsName($this->aConfig['dir']['tests'] . '/resources/base_project/1');
        $this->assertEquals($aProjectNames, array('ebay', 'ptpn', 'rts'));
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::getSXEProject
     */
    public function testGetSXEProjectThrowExceptionIfBadXML ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        $sXML = file_get_contents($this->aConfig['dir']['tests'] . '/resources/base_project/2/bad_xml.xml');
        Project::getSXEProject($sXML);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::getSXEProject
     */
    public function testGetSXEProject ()
    {
        $sXML = file_get_contents($this->aConfig['dir']['tests'] . '/resources/base_project/1/ebay.xml');
        $oSXE = Project::getSXEProject($sXML);
        $oExpectedSXE = new \SimpleXMLElement(
            $this->aConfig['dir']['tests'] . '/resources/base_project/1/ebay.xml',
            null,
            true
        );
        $this->assertEquals($oSXE, $oExpectedSXE);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::__construct
     */
    public function testNewThrowExceptionIfBadXML ()
    {
        $sXML = 'bla bla';
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '$sXML"
        );
        new Project($sXML, 'myEnv', $this->oDIContainer);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::__construct
     */
    public function testNewThrowExceptionIfEnvNotFound ()
    {
        $sXML = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests">
</project>
EOT;
        $this->setExpectedException(
            'UnexpectedValueException',
            "Environment 'myEnv' not found or not unique in this project!"
        );
        new Project($sXML, 'myEnv', $this->oDIContainer);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::__construct
     */
    public function testNewThrowExceptionIfMultipleEnv ()
    {
        $sXML = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests">
    <env name="myEnv" />
    <env name="myEnv" />
</project>
EOT;
        $this->setExpectedException(
            'UnexpectedValueException',
            "Environment 'myEnv' not found or not unique in this project!"
        );
        new Project($sXML, 'myEnv', $this->oDIContainer);
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Project::__construct
     * @covers \Himedia\Padocc\Task\Base\Project::check
     */
    public function testCheck ()
    {
        $sXML = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests" propertyinifile="/path/to/file">
    <env name="myEnv" basedir="/base/dir" />
</project>
EOT;
        $oProject = new Project($sXML, 'myEnv', $this->oDIContainer);
        $oProject->setUp();

        $oClass = new \ReflectionClass('\Himedia\Padocc\Task\Base\Project');
        $oProperty = $oClass->getProperty('oBoundTask');
        $oProperty->setAccessible(true);
        $oEnv = $oProperty->getValue($oProject);

        $this->assertAttributeEquals(
            array(
                'basedir' => '/base/dir',
                'name' => 'myEnv'
            ),
            'aAttValues',
            $oEnv
        );
    }
}

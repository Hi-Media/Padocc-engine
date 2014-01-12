<?php

namespace Himedia\Padocc\Tests\Task\Base;

use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Tests\PadoccTestCase;

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
        $oBaseLogger = new Logger_Adapter(LoggerInterface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

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
     * @covers Project::getAllProjectsName
     */
    public function testGetAllProjectsName_ThrowExceptionIfNotFound ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Resource path not found: '"
        );
        Project::getAllProjectsName(__DIR__ . '/not_found');
    }

    /**
     * @covers Project::getAllProjectsName
     */
    public function testGetAllProjectsName_ThrowExceptionIfBadXml ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        Project::getAllProjectsName(__DIR__ . '/resources/2');
    }

    /**
     * @covers Project::getAllProjectsName
     */
    public function testGetAllProjectsName ()
    {
        $aProjectNames = Project::getAllProjectsName(__DIR__ . '/resources/1');
        $this->assertEquals($aProjectNames, array('ebay', 'ptpn', 'rts'));
    }

    /**
     * @covers Project::getSXEProject
     */
    public function testGetSXEProject_ThrowExceptionIfNotFound () {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Project definition not found: '"
        );
        Project::getSXEProject(__DIR__ . '/not_found');
    }

    /**
     * @covers Project::getSXEProject
     */
    public function testGetSXEProject_ThrowExceptionIfBadXML ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '"
        );
        Project::getSXEProject(__DIR__ . '/resources/2/bad_xml.xml');
    }

    /**
     * @covers Project::getSXEProject
     */
    public function testGetSXEProject ()
    {
        $oSXE = Project::getSXEProject(__DIR__ . '/resources/1/ebay.xml');
        $this->assertEquals($oSXE, new \SimpleXMLElement(__DIR__ . '/resources/1/ebay.xml', null, true));
    }

    /**
     * @covers Project::__construct
     */
    public function testNew_ThrowExceptionIfProjectNotFound ()
    {
        $this->setExpectedException(
            'UnexpectedValueException',
            "Project definition not found: '/path/not found'!"
        );
        $oTask = new Project('/path/not found', 'myEnv', $this->oDIContainer);
    }

    /**
     * @covers Project::__construct
     */
    public function testNew_ThrowExceptionIfBadXML ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = 'bla bla';
        file_put_contents($sTmpPath, $sContent);
        $this->setExpectedException(
            'UnexpectedValueException',
            "Bad project definition: '" . DEPLOYMENT_TMP_DIR . "/deploy_unittest_"
        );
        try {
            $oTask = new Project($sTmpPath, 'myEnv', $this->oDIContainer);
        } catch (UnexpectedValueException $oException) {
            unlink($sTmpPath);
            throw $oException;
        }
    }

    /**
     * @covers Project::__construct
     */
    public function testNew_ThrowExceptionIfEnvNotFound ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests">
</project>
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->setExpectedException(
            'UnexpectedValueException',
            "Environment 'myEnv' not found or not unique in this project!"
        );
        try {
            $oTask = new Project($sTmpPath, 'myEnv', $this->oDIContainer);
        } catch (UnexpectedValueException $oException) {
            unlink($sTmpPath);
            throw $oException;
        }
    }

    /**
     * @covers Project::__construct
     */
    public function testNew_ThrowExceptionIfMultipleEnv ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests">
    <env name="myEnv" />
    <env name="myEnv" />
</project>
EOT;
        file_put_contents($sTmpPath, $sContent);
        $this->setExpectedException(
            'UnexpectedValueException',
            "Environment 'myEnv' not found or not unique in this project!"
        );
        try {
            $oTask = new Project($sTmpPath, 'myEnv', $this->oDIContainer);
        } catch (UnexpectedValueException $oException) {
            unlink($sTmpPath);
            throw $oException;
        }
    }

    /**
     * @covers Project::__construct
     * @covers Project::check
     */
    public function testCheck ()
    {
        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $sContent = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<project name="tests" propertyinifile="/path/to/file">
    <env name="myEnv" basedir="/base/dir" />
</project>
EOT;
        file_put_contents($sTmpPath, $sContent);
        $oProject = new Project($sTmpPath, 'myEnv', $this->oDIContainer);
        /*$oProject = $this->getMock(
            'Project',
            array('loadProperties'),
            array($sTmpPath, 'myEnv', 'anExecutionID', $this->oDIContainer)
        );*/
        $oProject->setUp();
        unlink($sTmpPath);

        $oClass = new \ReflectionClass('Project');
        $oProperty = $oClass->getProperty('oBoundTask');
        $oProperty->setAccessible(true);
        $oEnv = $oProperty->getValue($oProject);

        $this->assertAttributeEquals(array(
            'basedir' => '/base/dir',
            'name' => 'myEnv'
        ), 'aAttValues', $oEnv);
    }
}

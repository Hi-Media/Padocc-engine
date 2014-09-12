<?php

namespace Himedia\Padocc\Tests\Task\Extended;

use GAubry\Shell\PathStatus;
use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Task\Base\Composer;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Tests\PadoccTestCase;
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
class ComposerTest extends PadoccTestCase
{
    /**
     * Collection de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Project.
     * @var Project
     */
    private $oMockProject;

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
     * @return array empty array
     * @see $aShellExecCmds
     */
    public function shellExecCallback($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
        return array();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $oLogger = new NullLogger();

        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->getMock(
            '\GAubry\Shell\ShellAdapter',
            array('exec', 'execSSH'),
            array($oLogger, $this->aAllConfigs['GAubry\Shell'])
        );
        $this->aShellExecCmds = array();

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            $this->aConfig['dir']['repositories'] . '/git/test_project_test_env_1' => PathStatus::STATUS_DIR,
            '/tmp/my-local-repository' => PathStatus::STATUS_DIR,
            '/tmp/my-local-repository/subdir/of/repo' => PathStatus::STATUS_DIR
        ));


        $oProperties = new PropertiesAdapter($oMockShell, $this->aConfig);
        $oProperties
            ->setProperty('project_name', 'test_project')
            ->setProperty('environment_name', 'test_env')
            ->setProperty('with_symlinks', 'false');
        $oNumbering = new NumberingAdapter();

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering)
            ->setConfig($this->aConfig);

        $this->oMockProject = $this->getMock('\Himedia\Padocc\Task\Base\Project', array(), array(), '', false);
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
     * @covers \Himedia\Padocc\Task\Base\Composer::__construct
     * @covers \Himedia\Padocc\Task\Base\Composer::check
     * @covers \Himedia\Padocc\Task\Base\Composer::centralExecute
     * @dataProvider dataProviderTestOverall
     */
    public function testOverall($sConfig, $sDir, $sOptions, array $aCalls)
    {
        /* @var $oMockShell ShellAdapter|\PHPUnit_Framework_MockObject_MockObject */
        $oMockShell = $this->oDIContainer->getShellAdapter();
        $oMockShell->expects($this->at(0))
            ->method('execSSH')->will($this->returnValue($sConfig));
        $oMockShell->expects($this->any())
            ->method('execSSH')->will($this->returnCallback(array($this, 'shellExecCallback')));

        $aAttributes = array('dir' => $sDir);
        if (! empty($sOptions)) {
            $aAttributes['options'] = $sOptions;
        }
        $oComposerTask = Composer::getNewInstance($aAttributes, $this->oMockProject, $this->oDIContainer);
        $oComposerTask->setUp();
        $oComposerTask->execute();

        array_shift($this->aShellExecCmds);
        $this->assertEquals($aCalls, $this->aShellExecCmds);
    }

    /**
     * Data provider pour testProcessPath()
     */
    public function dataProviderTestOverall()
    {
        $sComposer = 'composer install --working-dir "/path/to/dir"';
        $sComposerPhar = 'php composer.phar install --working-dir "/path/to/dir"';
        $sWGet = 'wget -q --no-check-certificate http://getcomposer.org/installer -O - | php';
        $sCURL = 'curl -sS https://getcomposer.org/installer | php';
        return array(
            array(array('1', '0', '0'), '/path/to/dir', '',         array("$sComposer --no-dev")),
            array(array('1', '1', '1'), '/path/to/dir', '--no-dev', array("$sComposer --no-dev")),
            array(array('0', '1', '1'), '/path/to/dir', '--a --b',  array($sWGet, "$sComposerPhar --a --b")),
            array(array('0', '0', '1'), '/path/to/dir', '',         array($sCURL, "$sComposerPhar --no-dev"))
        );
    }

    /**
     * @covers \Himedia\Padocc\Task\Base\Composer::__construct
     * @covers \Himedia\Padocc\Task\Base\Composer::check
     * @covers \Himedia\Padocc\Task\Base\Composer::centralExecute
     */
    public function testOverallThrowExceptionIfInstallImpossible()
    {
        $aAttributes = array('dir' => '/path/to/dir');
        $oComposerTask = Composer::getNewInstance($aAttributes, $this->oMockProject, $this->oDIContainer);
        $oComposerTask->setUp();
        $this->setExpectedException('\RuntimeException', 'Composer is not installed');
        $oComposerTask->execute();
    }
}

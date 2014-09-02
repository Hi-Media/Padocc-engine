<?php

namespace Himedia\Padocc\Tests\Task\Extended;

use GAubry\Shell\PathStatus;
use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Task\Base\Bower;
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
class BowerTest extends PadoccTestCase
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
    public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
        return array();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
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
     * Returns a list of options that are supposed to throw an exception when executing the task.
     *
     * @see testUnauthorizedOptionThrowsDomainException
     *
     * @return array
     */
    public function getUnauthorizedOptions()
    {
        return array(
            array('-o'),
            array('--opt value'),
            array('--quiet --offline'),
            array('--config.interactive=true')
        );
    }

    /**
     * @dataProvider getUnauthorizedOptions
     *
     * @expectedException \DomainException
     * @expectedExceptionMessage Option not allowed
     *
     * @covers \Himedia\Padocc\Task\Base\Bower::__construct
     * @covers \Himedia\Padocc\Task\Base\Bower::check
     * @covers \Himedia\Padocc\Task\Base\Bower::centralExecute
     */
    public function testUnauthorizedOptionThrowsDomainException($options)
    {
        $attributes = array(
            'dir'     => '/path/to/dir',
            'options' => $options
        );

        $task = Bower::getNewInstance($attributes, $this->oMockProject, $this->oDIContainer);
        $task->setUp();
        $task->execute();
    }
}

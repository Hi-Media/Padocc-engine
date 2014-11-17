<?php

namespace Himedia\Padocc\Tests\Task\Extended;

use GAubry\Shell\PathStatus;
use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Numbering\Adapter as NumberingAdapter;
use Himedia\Padocc\Properties\Adapter as PropertiesAdapter;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Extended\GitExport;
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
class GitExportTest extends PadoccTestCase
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
     * Callback déclenchée sur appel de Shell_Adapter::sync().
     * Log tous les appels dans le tableau indexé $this->aShellExecCmds.
     *
     * @param string $sSrcPath au format [[user@]hostname_or_ip:]/path
     * @param string $sDestPath chaque destination au format [[user@]hostname_or_ip:]/path
     * @param array $aValues liste de valeurs (string) optionnelles pour générer autant de demandes de
     * synchronisation en parallèle. Dans ce cas ces valeurs viendront remplacer l'une après l'autre
     * les occurences de crochets vide '[]' présents dans $sSrcPath ou $sDestPath.
     * @param array $aIncludedPaths chemins à transmettre aux paramètres --include de la commande shell rsync.
     * Il précéderons les paramètres --exclude.
     * @param array $aExcludedPaths chemins à transmettre aux paramètres --exclude de la commande shell rsync
     * @param string $sRsyncPattern
     * @return array empty array
     */
    public function shellSyncCallback(
        $sSrcPath,
        $sDestPath,
        array $aValues,
        array $aIncludedPaths,
        array $aExcludedPaths,
        $sRsyncPattern
    ) {
        $this->aShellExecCmds[] = "$sSrcPath|$sDestPath|" . implode('+', $aValues)
                                . '|' . implode('+', $aIncludedPaths)
                                . '|' . implode('+', $aExcludedPaths)
                                . "|$sRsyncPattern";
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
            array('exec', 'sync'),
            array($oLogger, $this->aAllConfigs['GAubry\Shell'])
        );
        $oMockShell->expects($this->any())
            ->method('exec')->will($this->returnCallback(array($this, 'shellExecCallback')));
        $oMockShell->expects($this->any())
            ->method('sync')->will($this->returnCallback(array($this, 'shellSyncCallback')));
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
     * @covers \Himedia\Padocc\Task\Extended\GitExport::__construct
     * @covers \Himedia\Padocc\Task\Extended\GitExport::setUp
     * @covers \Himedia\Padocc\Task\Extended\GitExport::centralExecute
     */
    public function testOverall()
    {
        $oGitTask = GitExport::getNewInstance(
            array(
                'repository' => '/my/repo',
                'ref' => 'master',
                'destdir' => '/path/to/destdir'
            ),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oGitTask->setUp();
        $oGitTask->execute();

        $sGitExport = $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/gitexport.sh';
        $sRepoDir = $this->aConfig['dir']['repositories'];
        $this->assertEquals(array(
            $sGitExport . ' "/my/repo" "master" "' . $sRepoDir . '/git/test_project_test_env_1" "' . $this->aConfig['dir']['conf'] . '/padocc-ssh"',
            $sRepoDir . '/git/test_project_test_env_1/|/path/to/destdir||||'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\GitExport::__construct
     * @covers \Himedia\Padocc\Task\Extended\GitExport::setUp
     * @covers \Himedia\Padocc\Task\Extended\GitExport::centralExecute
     */
    public function testOverallWithIncludeExclude()
    {
        $oGitTask = GitExport::getNewInstance(
            array(
                'repository' => '/my/repo',
                'ref' => 'master',
                'destdir' => '/path/to/destdir',
                'include' => 'this.log that.log',
                'exclude' => '*.log *.bak'
            ),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oGitTask->setUp();
        $oGitTask->execute();

        $sGitExport = $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/gitexport.sh';
        $sRepoDir = $this->aConfig['dir']['repositories'];
        $this->assertEquals(array(
            $sGitExport . ' "/my/repo" "master" "' . $sRepoDir . '/git/test_project_test_env_1" "' . $this->aConfig['dir']['conf'] . '/padocc-ssh"',
            $sRepoDir . '/git/test_project_test_env_1/|/path/to/destdir||this.log+that.log|*.log+*.bak|'
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Task\Extended\GitExport::__construct
     * @covers \Himedia\Padocc\Task\Extended\GitExport::setUp
     * @covers \Himedia\Padocc\Task\Extended\GitExport::centralExecute
     */
    public function testOverallWithLocalRepoAndSubdir()
    {
        $oGitTask = GitExport::getNewInstance(
            array(
                'repository' => '/my/repo',
                'ref' => 'master',
                'srcsubdir' => '/subdir/of/repo/',
                'localrepositorydir' => '/tmp/my-local-repository/',
                'destdir' => '/path/to/destdir'
            ),
            $this->oMockProject,
            $this->oDIContainer
        );
        $oGitTask->setUp();
        $oGitTask->execute();

        $sGitExport = $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/gitexport.sh';
        $this->assertEquals(array(
            $sGitExport . ' "/my/repo" "master" "/tmp/my-local-repository" "' . $this->aConfig['dir']['conf'] . '/padocc-ssh"',
            '/tmp/my-local-repository/subdir/of/repo/|/path/to/destdir||||'
        ), $this->aShellExecCmds);
    }
}

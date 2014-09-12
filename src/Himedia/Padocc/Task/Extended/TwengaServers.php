<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\Task;

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
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @author Tony Caron <caron.tony@gmail.com>
 * @license Apache License, Version 2.0
 */
class TwengaServers extends Task
{
    /**
     * Tâche d'export Git sous-jacente.
     *
     * @var GitExport
     */
    private $oGitExportTask;

    /**
     * Répertoire temporaire où extraire master_synchro.cfg.
     * @var string
     */
    private $sTmpDir;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array();
        $this->sTmpDir = $this->aConfig['dir']['tmp'] . '/'
            . $this->oProperties->getProperty('execution_id') . '_' . self::getTagName();

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        $this->oGitExportTask = GitExport::getNewInstance(
            array(
                'repository' => 'git@git.twenga.com:aa/server_config.git',
                'ref' => 'master',
                'destdir' => $this->sTmpDir
            ),
            $this->oProject,
            $this->oDIContainer
        );
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'twengaserverexport';
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp()
    {
        parent::setUp();
        $this->getLogger()->info('+++');
        $this->oGitExportTask->setUp();
        $this->getLogger()->info('---');
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     */
    protected function check()
    {
        parent::centralExecute();
        $this->getLogger()->info('+++');
        $this->oGitExportTask->execute();
        $sPathToLoad = $this->sTmpDir . '/master_synchro.cfg';
        $this->getLogger()->info("Load shell properties: $sPathToLoad+++");
        $this->oProperties->loadConfigShellFile($sPathToLoad);
        $this->oShell->remove($this->sTmpDir);
        $this->getLogger()->info('------');
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute()
    {
        parent::centralExecute();
    }
}

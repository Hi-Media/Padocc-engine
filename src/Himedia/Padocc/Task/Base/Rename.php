<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Renomme un fichier ou un répertoire.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <rename src="${TMPDIR}/v3" dest="${TMPDIR}/web" />
 *
 *
 *
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
 * @license Apache License, Version 2.0
 */
class Rename extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'dest' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'rename';
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas de valeur non permise
     */
    public function check()
    {
        parent::check();

        // Pour l'instant ne gère pas les chemins distants :
        list(, $sSrcServer, ) = $this->oShell->isRemotePath($this->aAttValues['src']);
        list(, $sDestServer, ) = $this->oShell->isRemotePath($this->aAttValues['dest']);
        if ($sSrcServer != $sDestServer) {
            throw new \DomainException('Paths must be local or on the same server!');
        }
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
        $aSrcPath = $this->processSimplePath($this->aAttValues['src']);
        $aDestPath = $this->processSimplePath($this->aAttValues['dest']);
        $this->getLogger()->info("+++Rename '$aSrcPath' to '$aDestPath'.");
        $this->oShell->execSSH("mv %s '" . $aDestPath . "'", $aSrcPath);
        $this->getLogger()->info('---');
    }
}

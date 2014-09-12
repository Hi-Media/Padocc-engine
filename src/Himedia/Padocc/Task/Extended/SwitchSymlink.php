<?php

namespace Himedia\Padocc\Task\Extended;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task\Base\Environment;
use Himedia\Padocc\Task\Base\Link;

/**
 * Permute les liens symboliques de la dernière release vers la nouvelle à la fin du déploiement.
 * Tâche ajoutée par défaut en tant que dernière tâche de l'environnement, si et seulement si aucune
 * tâche SwitchSymlink ou fille (comme B2CSwitchSymlink) n'est spécifiée dans le XML,
 * et si l'attribut withsymlinks de la tâche env vaut true. À inclure en toute fin de tâche env ou target.
 *
 * Attributs :
 * - 'src' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'target' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'server' : laisser à vide à moins d'être bien conscient des conséquences
 *
 * Exemple : <switchsymlink />
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
class SwitchSymlink extends Link
{
    /**
     * Compteur d'instances de la classe.
     * @var int
     * @see getNbInstances()
     */
    private static $iNbInstances = 0;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'src' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::ALLOW_PARAMETER,
            'target' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::ALLOW_PARAMETER,
            'server' => AttributeProperties::ALLOW_PARAMETER
        );
        self::$iNbInstances++;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'switchsymlink';
    }

    /**
     * Accesseur au compteur d'instances de la classe.
     *
     * @return int nombre d'instances de la classe.
     * @see $iNbInstances
     */
    public static function getNbInstances()
    {
        return self::$iNbInstances;
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
        if (! isset($this->aAttValues['src'])
            && ! isset($this->aAttValues['target'])
            && ! isset($this->aAttValues['server'])
        ) {
            $sBaseSymLink = $this->oProperties->getProperty('basedir');
            $sRollbackID = $this->oProperties->getProperty('rollback_id');
            if ($sRollbackID !== '') {
                $this->getLogger()->info("Rollback to '$sRollbackID' requested.");
                $sID = $sRollbackID;
            } else {
                $sID = $this->oProperties->getProperty('execution_id');
            }
            $sReleaseSymLink = $sBaseSymLink . $this->aConfig['symlink_releases_dir_suffix'] . '/' . $sID;

            $this->aAttValues['src'] = $sBaseSymLink;
            $this->aAttValues['target'] = $sReleaseSymLink;
            $this->aAttValues['server'] = '${' . Environment::SERVERS_CONCERNED_WITH_BASE_DIR . '}';
        }

        parent::check();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute()
    {
        $this->getLogger()->info('+++');
        if ($this->oProperties->getProperty('with_symlinks') === 'true') {
            if ($this->oProperties->getProperty(Environment::SERVERS_CONCERNED_WITH_BASE_DIR) == '') {
                $this->getLogger()->info('No release found.');
            } else {
                $this->oProperties->setProperty('with_symlinks', 'false');
                $this->checkTargets();
                $this->getLogger()->info('---');
                parent::centralExecute();
                $this->getLogger()->info('+++');
                $this->oProperties->setProperty('with_symlinks', 'true');
            }
        } else {
            $this->getLogger()->info("Mode 'withsymlinks' is off: nothing to do.");
        }
        $this->getLogger()->info('---');
    }

    /**
     * Vérifie que chaque répertoire cible des liens existe.
     * Notamment nécessaire en cas de rollback.
     *
     * @throws \RuntimeException si l'un des répertoires cible des liens n'existe pas
     */
    protected function checkTargets()
    {
        $this->getLogger()->info('Check that all symlinks targets exists.+++');

        $aValidStatus = array(
            PathStatus::STATUS_DIR,
            PathStatus::STATUS_SYMLINKED_DIR
        );

        $sPath = $this->aAttValues['target'];
        $aServers = $this->expandPath($this->aAttValues['server']);
        $aPathStatusResult = $this->oShell->getParallelSSHPathStatus($sPath, $aServers);
        foreach ($aServers as $sServer) {
            $sExpandedPath = $sServer . ':' . $sPath;
            if (! in_array($aPathStatusResult[$sServer], $aValidStatus)) {
                $sMsg = "Target attribute must be a directory or a symlink to a directory: '" . $sExpandedPath . "'";
                throw new \RuntimeException($sMsg);
            }
        }

        $this->getLogger()->info('---');
    }
}

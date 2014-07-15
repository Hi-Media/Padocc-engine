<?php

namespace Himedia\Padocc\Task\Base;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Crée un lien symbolique.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'src'
 * - 'target' : la cible n'a pas forcément besoin d'exister, ce qui créera un lien cassé.
 *   Ceci peut-être intéressant lorsque l'on prépare un déploiement dans un fichier temporaire
 *   avant d'effectuer la synchronisation finale vers tous les serveurs (cf. l'exemple ci-dessous).
 * - 'server' : à utiliser si l'on doit créer des mêmes liens sur plusieurs serveurs
 *
 * Exemple : <link src="${TMPDIR}/inc/config.php" target="../../../config/www.twenga/config.php" />
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
class Link extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'src' => AttributeProperties::REQUIRED | AttributeProperties::FILE | AttributeProperties::DIR
                | AttributeProperties::ALLOW_PARAMETER,
            'target' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'server' => AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'link';
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
    public function check ()
    {
        parent::check();

        list($bIsSrcRemote, $sSrcServer, ) = $this->oShell->isRemotePath($this->aAttValues['src']);
        list($bIsDestRemote, $sDestServer, ) = $this->oShell->isRemotePath($this->aAttValues['target']);
        if (($bIsSrcRemote xor $bIsDestRemote)
            || ($bIsSrcRemote && $bIsDestRemote && $sSrcServer != $sDestServer)
        ) {
            $sMsg = 'Servers must be equals!' . ' Src=' . $this->aAttValues['src']
                  . ' Target=' . $this->aAttValues['target'];
            throw new \DomainException($sMsg);
        }

        if (! empty($this->aAttValues['server']) && ($bIsSrcRemote || $bIsDestRemote)) {
            $sMsg = 'Multiple server declaration!' . ' Server=' . $this->aAttValues['server']
                  . ' Src=' . $this->aAttValues['src'] . ' Target=' . $this->aAttValues['target'];
            throw new \DomainException($sMsg);
        }

        // Valeur par défaut :
        if (! isset($this->aAttValues['server'])) {
            $this->aAttValues['server'] = '';
        }
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $this->getLogger()->info('+++');

        // La source doit être un lien symbolique ou ne pas exister :
        $sPath = $this->aAttValues['src'];
        if (! empty($this->aAttValues['server'])) {
            $sPath = $this->aAttValues['server'] . ':' . $sPath;
        }
        $aValidSources = array(
            PathStatus::STATUS_NOT_EXISTS,
            PathStatus::STATUS_SYMLINKED_FILE,
            PathStatus::STATUS_SYMLINKED_DIR,
            PathStatus::STATUS_BROKEN_SYMLINK
        );
        foreach ($this->expandPath($sPath) as $sExpandedPath) {
            if (! in_array($this->oShell->getPathStatus($sExpandedPath), $aValidSources)) {
                $sMsg = "Source attribute must be a symlink or not exist: '" . $sExpandedPath . "'";
                throw new \RuntimeException($sMsg);
            }
        }

        $sRawTargetPath = $this->aAttValues['target'];
        if (! empty($this->aAttValues['server'])) {
            $sRawTargetPath = $this->aAttValues['server'] . ':' . $sRawTargetPath;
        }
        $this->getLogger()->info("Create symlink from '$sPath' to '$sRawTargetPath'.+++");

        $aTargetPaths = $this->processPath($sRawTargetPath);
        foreach ($aTargetPaths as $sTargetPath) {
            list(, $sDestServer, ) = $this->oShell->isRemotePath($sTargetPath);
            if (! empty($sDestServer)) {
                $sDestServer .= ':';
            }
            list(, , $sSrcRealPath) = $this->oShell->isRemotePath($this->aAttValues['src']);
            $sSrc = $this->processSimplePath($sDestServer . $sSrcRealPath);
            $this->oShell->createLink($sSrc, $sTargetPath);
        }
        $this->getLogger()->info('------');
    }
}

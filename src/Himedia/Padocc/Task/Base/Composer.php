<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Exécute l'outil de gestion de dépendances PHP composer.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'dir' : répertoire d'où exécuter composer
 * - 'options' : options à transmettre à la commande 'composer install', par défaut '--no-dev'
 *
 * Exemple : <composer dir="${TMPDIR}" options="--no-dev" />
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
class Composer extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'dir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'options' => 0
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'composer';
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

        if (empty($this->aAttValues['options'])) {
            $this->aAttValues['options'] = '--no-dev';
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

        $sInstallCmdPattern = '%1$s install --working-dir "%2$s" %3$s';
        $sWGetCmd = 'wget -q --no-check-certificate http://getcomposer.org/installer -O - | php';
        $sCURLCmd = 'curl -sS https://getcomposer.org/installer | php';
        $sCheckCmd = 'which composer 1>/dev/null 2>&1 && echo 1 || echo 0; '
              . 'which wget 1>/dev/null 2>&1 && echo 1 || echo 0; '
              . 'which curl 1>/dev/null 2>&1 && echo 1 || echo 0';

        $aDirs = $this->processPath($this->aAttValues['dir']);
        foreach ($aDirs as $sDir) {
            list(, , $sLocalPath) = $this->oShell->isRemotePath($sDir);
            $aResult = $this->oShell->execSSH($sCheckCmd, $sDir);
            $isComposerInstalled = (isset($aResult[0]) && $aResult[0] === '1');
            $isWGetInstalled = (isset($aResult[1]) && $aResult[1] === '1');
            $isCURLInstalled = (isset($aResult[2]) && $aResult[2] === '1');

            // Config:
            if ($isComposerInstalled) {
                $sComposerBin = 'composer';
                $sDownloadCmd = '';
            } elseif ($isWGetInstalled) {
                $sComposerBin = 'php composer.phar';
                $sDownloadCmd = $sWGetCmd;
            } elseif ($isCURLInstalled) {
                $sComposerBin = 'php composer.phar';
                $sDownloadCmd = $sCURLCmd;
            } else {
                $sMsg = 'Composer is not installed, but nor are both wget and curl to install it!';
                throw new \RuntimeException($sMsg);
            }

            // Optional installation:
            if (! empty($sDownloadCmd)) {
                $this->getLogger()->info('Install composer:+++');
                $aResult = $this->oShell->execSSH($sDownloadCmd, $sDir);
                $this->getLogger()->info(implode("\n", $aResult) . '---');
            }

            // Execution:
            $this->getLogger()->info("Execute composer on '$sDir':+++");
            $sCmd = sprintf($sInstallCmdPattern, $sComposerBin, $sLocalPath, $this->aAttValues['options']);
            $aResult = $this->oShell->execSSH($sCmd, $sDir);
            $this->getLogger()->info(implode("\n", $aResult) . '---');
        }

        $this->getLogger()->info('---');
    }
}

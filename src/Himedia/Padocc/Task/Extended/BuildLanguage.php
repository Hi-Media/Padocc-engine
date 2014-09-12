<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Génère les fichiers de langue au format [geozoneId].php pour un projet donné.
 * À inclure dans une tâche env ou target.
 *
 * Complètement recodé par rapport à la version précédente : environ 20 fois plus rapide !
 *
 * Exemples : <buildlanguage project="rts" destdir="${SERVERS}:${BASEDIR}/languages" />
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
class BuildLanguage extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'project' => AttributeProperties::REQUIRED,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'buildlanguage';
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
        $this->getLogger()->info('+++');

        $sLanguagesPath = tempnam(
            $this->aConfig['dir']['tmp'],
            $this->oProperties->getProperty('execution_id') . '_languages_'
        );
        $sURL = 'https://xyz/translation_tool/build_language_files.php?project='
              . $this->aAttValues['project'];
        $this->getLogger()->info('Generate language archive from web service: ' . $sURL);
        if (! copy($sURL, $sLanguagesPath)) {
            throw new \RuntimeException("Copy of '$sURL' to '$sLanguagesPath' failed!");
        }

        // Diffusion de l'archive :
        $this->getLogger()->info('Send language archive to all servers+++');
        $aDestDirs = $this->processPath($this->aAttValues['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->oShell->copy($sLanguagesPath, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->getLogger()->info($sResult);
            }
        }
        $this->getLogger()->info('---');

        // Décompression des archives :
        $this->getLogger()->info('Extract language files from archive on each server+++');
        $sPatternCmd = 'cd %1$s && tar -xf %1$s/"' . basename($sLanguagesPath)
                     . '" && rm -f %1$s/"' . basename($sLanguagesPath) . '"';
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->oShell->execSSH($sPatternCmd, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->getLogger()->info($sResult);
            }
        }
        $this->getLogger()->info('---');

        @unlink($sLanguagesPath);
        $this->getLogger()->info('---');
    }
}

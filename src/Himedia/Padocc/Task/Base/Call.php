<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task\WithProperties;

/**
 * Permet d'appeler une tâche target du même fichier XML.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <call target="web_content" />
 *
 * Dérive Task_WithProperties et supporte donc les attributs XML 'loadtwengaservers', 'propertyshellfile'
 * et 'propertyinifile'.
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
class Call extends WithProperties
{
    /**
     * Tâche appelée.
     * @var Target
     */
    private $oBoundTask;

    /**
     * {@inheritdoc}
     *
     * @throws \UnexpectedValueException if target not found or not unique in this project.
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array('target' => AttributeProperties::REQUIRED)
        );

        // Crée une instance de la tâche target appelée :
        if (! empty($this->aAttValues['target'])) {
            $aTargets = $this->oProject->getSXE()->xpath("target[@name='" . $this->aAttValues['target'] . "']");
            if (count($aTargets) !== 1) {
                $sMsg = "Target '" . $this->aAttValues['target'] . "' not found or not unique in this project!";
                throw new \UnexpectedValueException($sMsg);
            }
            $this->oBoundTask = new Target($aTargets[0], $this->oProject, $this->oDIContainer);
        }
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'call';
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     * @codeCoverageIgnore
     */
    public function setUp()
    {
        parent::setUp();
        $this->oBoundTask->setUp();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     * @codeCoverageIgnore
     */
    protected function centralExecute()
    {
        parent::centralExecute();
        $this->oBoundTask->execute();
    }
}

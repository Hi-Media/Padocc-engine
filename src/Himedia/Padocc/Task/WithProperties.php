<?php

namespace Himedia\Padocc\Task;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Extended\TwengaServers;

/**
 * Couche permettant aux tâches l'implémentant d'importer des propriétés issues de fichiers de configuration INI,
 * de fichiers de configuration shell ou encore de la liste des groupes de serveurs Twenga.
 * Ces propriétés seront ensuite accessibles via $this->oProperties, instance de PropertiesInterface.
 *
 * Toute tâche dérivant Task_WithProperties se voit ainsi dotée de trois attributs XML optionnels et
 * cumulables : 'loadtwengaservers', 'propertyshellfile' et 'propertyinifile'.
 * Les voici illustrés dans l'ordre dans lequel ils sont traités si présents au sein de la même tâche :
 * - 'loadtwengaservers', "true" ou "false" (défaut), chargera la liste des groupes de serveurs Twenga
 * - 'propertyshellfile', chemin système, par ex. "/path/to/shell_file.cfg", importera en tant que propriétés
 * 		les variables du fichier de configuration shell
 * - 'propertyinifile', chemin système, par ex. "/path/to/config.ini", importera en tant que propriétés
 * 		les variables du fichier .INI
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
abstract class WithProperties extends Task
{
    /**
     * Tâche de chargement des listes de serveurs Twenga sous-jacente.
     * @var TwengaServers
     */
    private $oTwengaServersTask;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'loadtwengaservers' => AttributeProperties::BOOLEAN,
            'propertyshellfile' => AttributeProperties::SRC_PATH,
            'propertyinifile' => AttributeProperties::SRC_PATH
        );

        // Création de la tâche de chargement des listes de serveurs Twenga sous-jacente :
        if (! empty($this->aAttValues['loadtwengaservers']) && $this->aAttValues['loadtwengaservers'] == 'true') {
            $this->oNumbering->addCounterDivision();
            $this->oTwengaServersTask = TwengaServers::getNewInstance(array(), $this->oProject, $this->oDIContainer);
            $this->oNumbering->removeCounterDivision();
        } else {
            $this->oTwengaServersTask = null;
        }
    }

    /**
     * Lors de l'exécution de la tâche, charge les propriétés des éventuels fichiers de configuration INI,
     * fichiers de configuration shell ou encore la liste des groupes de serveurs Twenga.
     */
    private function loadProperties()
    {
        if (! empty($this->aAttValues['loadtwengaservers']) && $this->aAttValues['loadtwengaservers'] == 'true') {
            $this->oTwengaServersTask->execute();
        }
        if (! empty($this->aAttValues['propertyshellfile'])) {
            $this->getLogger()->info('Load shell properties: ' . $this->aAttValues['propertyshellfile'] . '+++');
            $this->oProperties->loadConfigShellFile($this->aAttValues['propertyshellfile']);
            $this->getLogger()->info('---');
        }
        if (! empty($this->aAttValues['propertyinifile'])) {
            $this->getLogger()->info('Load ini properties: ' . $this->aAttValues['propertyinifile']);
            $this->oProperties->loadConfigIniFile($this->aAttValues['propertyinifile']);
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp()
    {
        parent::setUp();
        if ($this->oTwengaServersTask !== null) {
            $this->getLogger()->info('+++');
            $this->oTwengaServersTask->setUp();
            $this->getLogger()->info('---');
        }
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::preExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function preExecute()
    {
        parent::preExecute();
        $this->getLogger()->info('+++');
        $this->loadProperties();
        $this->getLogger()->info('---');
    }
}

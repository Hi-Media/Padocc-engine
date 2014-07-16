<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Sync;

/**
 * Exporte tout ou partie du contenu d'un dépôt Git vers une ou plusieurs destinations.
 * À inclure dans une tâche env ou target.
 *
 * Exploite le script '/src/inc/cvsexport.sh'.
 * Réalise la synchronisation à l'aide d'une tâche sync avec la liste d'exclusion suivante
 * (en plus des éventuels include et exclude spécifiés dans la tâche) : '.bzr/', '.cvsignore', '.git/',
 * '.gitignore', '.svn/', 'cvslog.*', 'CVS', 'CVS.adm'.
 *
 * Attributs :
 * - 'repository'
 * - 'ref' : branche ou tag à déployer
 * - 'localrepositorydir' : lieu temporaire d'extraction du contenu qui nous intéresse du dépôt avant de l'envoyer
 * vers la destination ⇒ laisser à vide de manière générale,
 * l'outil utilisera alors le répertoire $aConfig['dir']['repositories'].
 * - 'srcsubdir' : sous-répertoire du dépôt qui nous intéresse
 * - 'destdir'
 * - 'include' : si l'on veut filtrer
 * - 'exclude' : si l'on veut filtrer
 *
 * Exemple : <gitexport repository="git@git.twenga.com:rts/rts.git" ref="${REF}"
 *     destdir="${SERVERS}:${BASEDIR}" exclude="config.* /Tests" />
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
class GitExport extends Task
{
    /**
     * Tâche de synchronisation sous-jacente.
     * @var Sync
     */
    private $oSyncTask;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'repository' => AttributeProperties::FILE | AttributeProperties::REQUIRED,
            'ref' => AttributeProperties::REQUIRED | AttributeProperties::ALLOW_PARAMETER,
            'localrepositorydir' => AttributeProperties::DIR,
            'srcsubdir' => AttributeProperties::DIR,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'include' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
            'exclude' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
        );

        // Valeur par défaut de l'attribut localrepositorydir :
        if (empty($this->aAttValues['localrepositorydir'])) {
            $this->aAttValues['localrepositorydir'] =
                $this->aConfig['dir']['repositories'] . '/git/'
                . $this->oProperties->getProperty('project_name') . '_'
                . $this->oProperties->getProperty('environment_name') . '_'
                . $this->sCounter;
        } else {
            $this->aAttValues['localrepositorydir'] =
                preg_replace('#/$#', '', $this->aAttValues['localrepositorydir']);
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        if (empty($this->aAttValues['srcsubdir'])) {
            $this->aAttValues['srcsubdir'] = '';
        } else {
            $this->aAttValues['srcsubdir'] = '/' . preg_replace('#^/|/$#', '', $this->aAttValues['srcsubdir']);
        }
        $aSyncAttributes = array(
            'src' => $this->aAttValues['localrepositorydir'] . $this->aAttValues['srcsubdir'] . '/',
            'destdir' => $this->aAttValues['destdir'],
        );
        if (! empty($this->aAttValues['include'])) {
            $aSyncAttributes['include'] = $this->aAttValues['include'];
        }
        if (! empty($this->aAttValues['exclude'])) {
            $aSyncAttributes['exclude'] = $this->aAttValues['exclude'];
        }
        $this->oSyncTask = Sync::getNewInstance($aSyncAttributes, $this->oProject, $this->oDIContainer);
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'gitexport';
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->getLogger()->info('+++');

        // La tâche Sync vérifie que le 'srcsubdir' existe bien. Mais s'il s'agit du 1er déploiement
        // de l'env concerné, alors le mkdir au niveau GitExport ne sera pas encore réalisé au moment
        // du check Sync… d'où la levée d'une exception, que l'on mange !
        try {
            $this->oSyncTask->setUp();
        } catch (\UnexpectedValueException $oException) {
            if ($oException->getMessage() !== "File or directory '" . $this->aAttValues['localrepositorydir']
                . $this->aAttValues['srcsubdir'] . '/' . "' not found!") {
                throw $oException;
            }
        }

        $this->getLogger()->info('---');
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

        $aRef = $this->processPath($this->aAttValues['ref']);
        $sRef = $aRef[0];

        $sMsg = "Export '$sRef' reference from '" . $this->aAttValues['repository'] . "' git repository+++";
        $this->getLogger()->info($sMsg);
        $aResult = $this->oShell->exec(
            $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/gitexport.sh'
            . ' "' . $this->aAttValues['repository'] . '"'
            . ' "' . $sRef . '"'
            . ' "' . $this->aAttValues['localrepositorydir'] . '"'
        );
        $this->getLogger()->info(implode("\n", $aResult) . '---');

        $this->oSyncTask->execute();
        $this->getLogger()->info('---');
    }
}

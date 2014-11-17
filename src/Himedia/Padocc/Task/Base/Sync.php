<?php

namespace Himedia\Padocc\Task\Base;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Synchronise efficacement (rsync Shell) et avec suppression le contenu d'un répertoire à l'intérieur d'un autre.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'src'
 * - 'destdir'
 * - 'include'
 * - 'exclude' : à noter que systématiquement sont exclus '.bzr/', '.cvsignore', '.git/', '.gitignore',
 *   '.svn/', 'cvslog.*', 'CVS' et 'CVS.adm'
 *
 * Exemples :
 * <sync src="${TMPDIR}/" destdir="${WEB_SERVERS}:${BASEDIR}" exclude="v3/css v3/js v4/css v4/js" />
 * <sync src="prod@fs3:/home/prod/twenga_files/merchant_logos/"
 *     destdir="${TMPDIR}/img/sites" include="*.png" exclude="*" />
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
class Sync extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::FILEJOKER | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'include' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'exclude' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'sync';
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

        if (preg_match('#\*|\?|/$#', $this->aAttValues['src']) === 0
            && $this->oShell->getPathStatus($this->aAttValues['src']) === PathStatus::STATUS_DIR
        ) {
            $this->aAttValues['destdir'] .= '/' . substr(strrchr($this->aAttValues['src'], '/'), 1);
            $this->aAttValues['src'] .= '/';
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
        $sMsg = "+++Synchronize '" . $this->aAttValues['src'] . "' with '" . $this->aAttValues['destdir'] . "'+++";
        $this->getLogger()->info($sMsg);

        // include / exclude :
        $aIncludedPaths = (empty($this->aAttValues['include'])
                          ? array()
                          : explode(' ', $this->aAttValues['include']));
        $aExcludedPaths = (empty($this->aAttValues['exclude'])
                          ? array()
                          : explode(' ', $this->aAttValues['exclude']));

        list($bIsDestRemote, $sDestServer, $sDestRawPath) = $this->oShell->isRemotePath($this->aAttValues['destdir']);
        $sDestPath = ($bIsDestRemote ? '[]:' . $sDestRawPath : $sDestRawPath);

        // Add default remote SSH user if none:
        $aDestServers = $this->processPath($sDestServer);
        foreach ($aDestServers as $idx => $sPath) {
            if (preg_match('/^[^@]+$/', $sPath) === 1) {
                $aDestServers[$idx] = $this->aConfig['default_remote_shell_user'] . '@' . $sPath;
            }
        }

        foreach ($this->processPath($sDestPath) as $sDestRealPath) {
            $aResults = $this->oShell->sync(
                $this->processSimplePath($this->aAttValues['src']),
                $this->processSimplePath($sDestRealPath),
                $aDestServers,
                $aIncludedPaths,
                $aExcludedPaths
            );
            foreach ($aResults as $sResult) {
                $this->getLogger()->info($sResult);
            }
        }
        $this->getLogger()->info('------');
    }
}

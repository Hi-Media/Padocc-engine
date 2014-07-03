<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Crée un répertoire.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'destdir'
 * - 'mode' : pour ajouter un chmod au mkdir
 *
 * Exemple : <mkdir destdir="${SERVERS}:${BASEDIR}/cache/smarty/templates_c" mode="777" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class MkDir extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'mode' => 0
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'mkdir';
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
        $this->getLogger()->info("+++Create directory '" . $this->aAttValues['destdir'] . "'.+++");
        $sMode = (empty($this->aAttValues['mode']) ? '' : $this->aAttValues['mode']);

        $aDestDirs = $this->processPath($this->aAttValues['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $this->oShell->mkdir($sDestDir, $sMode);
        }
        $this->getLogger()->info('------');
    }
}

<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
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
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'mkdir';
    }

    /**
     * Constructeur.
     *
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject, DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array(
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'mode' => 0
        );
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
        $this->oLogger->info("+++Create directory '" . $this->aAttValues['destdir'] . "'.+++");
        $sMode = (empty($this->aAttValues['mode']) ? '' : $this->aAttValues['mode']);

        $aDestDirs = $this->processPath($this->aAttValues['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $this->oShell->mkdir($sDestDir, $sMode);
        }
        $this->oLogger->info('------');
    }
}

<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
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
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Call extends WithProperties
{

    /**
     * Tâche appelée.
     * @var Target
     */
    private $oBoundTask;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'call';
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
        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array('target' => AttributeProperties::REQUIRED)
        );

        // Crée une instance de la tâche target appelée :
        if (! empty($this->aAttributes['target'])) {
            $aTargets = $this->oProject->getSXE()->xpath("target[@name='" . $this->aAttributes['target'] . "']");
            if (count($aTargets) !== 1) {
                $sMsg = "Target '" . $this->aAttributes['target'] . "' not found or not unique in this project!";
                throw new \UnexpectedValueException($sMsg);
            }
            $this->oBoundTask = new Target($aTargets[0], $this->oProject, $this->oDIContainer);
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oBoundTask->setUp();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $this->oBoundTask->execute();
    }
}

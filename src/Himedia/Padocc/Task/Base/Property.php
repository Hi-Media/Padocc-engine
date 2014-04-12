<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;

/**
 * Définit une propriété interne, réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <property name="servers" value="${SERVER_ADMIN} ${SERVER_SCHED_DEPLOY}" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Property extends Task
{
    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'property';
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
            'name' => AttributeProperties::REQUIRED,
            'value' => AttributeProperties::REQUIRED | AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function check ()
    {
        parent::centralExecute();
        $sMsg = "+++Set internal property '" . $this->aAttValues['name'] . "' to '"
              . $this->aAttValues['value'] . "'.---";
        $this->oLogger->info($sMsg);
        $this->oProperties->setProperty($this->aAttValues['name'], $this->aAttValues['value']);
    }
}

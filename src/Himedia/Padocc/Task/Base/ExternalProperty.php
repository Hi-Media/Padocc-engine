<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;

/**
 * Définit une propriété externe qu'il sera obligatoire de fournir lors de tout déploiement.
 * Cette propriété est par la suite réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <externalproperty name="ref" description="Branch or tag to deploy" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class ExternalProperty extends Task
{


    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'externalproperty';
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
            'description' => AttributeProperties::REQUIRED
        );
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
        $this->oLogger->info('+++');
        try {
            $sValue = $this->oProperties->getProperty($this->aAttributes['name']);
        } catch (\UnexpectedValueException $oException) {
            $sMsg = "Property '" . $this->aAttributes['name'] . "' undefined!";
            throw new \UnexpectedValueException($sMsg, 1, $oException);
        }
        $sMsg = "Set external property '" . $this->aAttributes['name'] . "' (description: '"
              . $this->aAttributes['description'] . "') to '$sValue'.";
        $this->oLogger->info($sMsg);
        $this->oProperties->setProperty($this->aAttributes['name'], $sValue);
        $this->oLogger->info('---');
    }
}

<?php
namespace Fuel\Tasks;

/**
 * Définit une propriété externe qu'il sera obligatoire de fournir lors de tout déploiement.
 * Cette propriété est par la suite réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <externalproperty name="ref" description="Branch or tag to deploy" />
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_ExternalProperty extends Task
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
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'name' => AttributeProperties::REQUIRED,
            'description' => AttributeProperties::REQUIRED
        );
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        var_dump($this->_aAttributes);
        try {
            $sValue = $this->_oProperties->getProperty($this->_aAttributes['name']);
        } catch (\UnexpectedValueException $oException) {
            $sMsg = "Property '" . $this->_aAttributes['name'] . "' undefined!";
            throw new \UnexpectedValueException($sMsg, 1, $oException);
        }
        $sMsg = "Set external property '" . $this->_aAttributes['name'] . "' (description: '"
              . $this->_aAttributes['description'] . "') to '$sValue'.";
        $this->_oLogger->log($sMsg);
        $this->_oProperties->setProperty($this->_aAttributes['name'], $sValue);
        $this->_oLogger->unindent();
    }
}

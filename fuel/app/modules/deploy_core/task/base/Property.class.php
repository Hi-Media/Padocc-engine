<?php
namespace Fuel\Tasks;

/**
 * Définit une propriété interne, réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <property name="servers" value="${SERVER_ADMIN} ${SERVER_SCHED_DEPLOY}" />
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_Property extends Task
{
    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'property';
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
            'value' => AttributeProperties::REQUIRED | AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function check ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        $sMsg = "Set internal property '" . $this->_aAttributes['name'] . "' to '"
              . $this->_aAttributes['value'] . "'.";
        $this->_oLogger->log($sMsg);
        $this->_oProperties->setProperty($this->_aAttributes['name'], $this->_aAttributes['value']);
        $this->_oLogger->unindent();
    }
}

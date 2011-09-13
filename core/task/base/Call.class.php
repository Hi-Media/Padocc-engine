<?php

/**
 *
 * Dérive Task_WithProperties et supporte donc les attributs XML 'loadtwengaservers', 'propertyshellfile'
 * et 'propertyinifile'.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_Call extends Task_WithProperties
{

    /**
     * Tâche appelée.
     * @var Task_Base_Target
     */
    private $_oBoundTask;

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
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array_merge(
            $this->_aAttrProperties,
            array('target' => AttributeProperties::REQUIRED)
        );

        // Crée une instance de la tâche target appelée :
        if ( ! empty($this->_aAttributes['target'])) {
            $aTargets = $this->_oProject->getSXE()->xpath("target[@name='" . $this->_aAttributes['target'] . "']");
            if (count($aTargets) !== 1) {
                $sMsg = "Target '" . $this->_aAttributes['target'] . "' not found or not unique in this project!";
                throw new UnexpectedValueException($sMsg);
            }
            $this->_oBoundTask = new Task_Base_Target($aTargets[0], $this->_oProject, $this->_oServiceContainer);
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->_oBoundTask->setUp();
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
        $this->_oBoundTask->execute();
    }
}

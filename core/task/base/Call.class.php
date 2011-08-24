<?php

class Task_Base_Call extends Task_WithProperties
{

    /**
     * Tâche appelée.
     * @var Task_Base_Target
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
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array_merge($this->aAttributeProperties, array(
            'target' => Task::ATTRIBUTE_REQUIRED
        ));

        // Crée une instance de la tâche target appelée :
        $aTargets = $this->oProject->getSXE()->xpath("target[@name='" . $this->aAttributes['target'] . "']");
        if (count($aTargets) !== 1) {
            $sMsg = "Target '" . $this->aAttributes['target'] . "' not found or not unique in this project!";
            throw new UnexpectedValueException($sMsg);
        }
        $this->oBoundTask = new Task_Base_Target($aTargets[0], $this->oProject, $sBackupPath,
                                                 $this->oServiceContainer);
    }

    public function setUp ()
    {
        parent::setUp();
        $this->oBoundTask->setUp();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->oBoundTask->backup();
        $this->oBoundTask->execute();
    }

    public function backup ()
    {
    }
}

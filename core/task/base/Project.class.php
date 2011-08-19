<?php

class Task_Base_Project extends Task_WithProperties
{

    /**
     * Tâche appelée.
     * @var Task_Base_Environment
     */
    private $oBoundTask;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'project';
    }

    /**
     * Constructeur.
     *
     * @param string $sProjectName Nom du projet.
     * @param string $sEnvName Environnement sélectionné.
     * @param string $sExecutionID Identifiant d'exécution.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
     */
    public function __construct ($sProjectName, $sEnvName, $sExecutionID, ServiceContainer $oServiceContainer)
    {
        $sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
        $oProject = Tasks::getProject($sProjectName);
        $this->sEnvName = $sEnvName;

        parent::__construct($oProject, $this, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array_merge($this->aAttributeProperties, array(
            'name' => Task::ATTRIBUTE_REQUIRED
        ));

        // Crée une instance de la tâche environnement appelée :
        $aTargets = $this->oProject->getSXE()->xpath("env[@name='$sEnvName']");
        if (count($aTargets) !== 1) {
            throw new UnexpectedValueException("Environment '$sEnvName' not found or not unique in this project!");
        }
        $this->oBoundTask = new Task_Base_Environment($aTargets[0], $this->oProject, $sBackupPath, $this->oServiceContainer);
    }

    public function setUp ()
    {
        parent::setUp();
        $this->oBoundTask->setUp();
    }

    public function execute ()
    {
        parent::execute();
        $this->oBoundTask->backup();
        $this->oBoundTask->execute();
    }

    public function backup ()
    {
    }

    public function getSXE ()
    {
        return $this->oXMLTask;
    }
}

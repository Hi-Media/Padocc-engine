<?php

class Task_Base_Project extends Task_WithProperties
{

    public static function getAllProjectsName ()
    {
        $aProjectName = array();
        if ($handle = opendir(DEPLOYMENT_RESOURCES_DIR)) {
            while ($file = readdir($handle)) {
                clearstatcache();
                $sProjectFilename = DEPLOYMENT_RESOURCES_DIR.'/'.$file;
                if (substr($file, strlen($file)-3, 3) == "xml" && is_file($sProjectFilename)) {
                    $oProject = new SimpleXMLElement($sProjectFilename, NULL, true);
                    if (isset($oProject['name'])) {
                        $aProjectName[] = (string)$oProject['name'];
                    }
                }
            }
            closedir($handle);
        }

        return $aProjectName;
    }

    /**
     * Retourne une instance SimpleXML du projet spécifié.
     *
     * @param string $sProjectName nom du projet à charger
     * @throws UnexpectedValueException si fichier XML du projet non trouvé
     * @return SimpleXMLElement isntance du projet spécifié
     */
    public static function getProject ($sProjectName)
    {
        $sProjectFilename = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
        if ( ! file_exists($sProjectFilename)) {
            throw new UnexpectedValueException("Project definition not found: '$sProjectFilename'!");
        }
        return new SimpleXMLElement($sProjectFilename, NULL, true);
    }

    /**
     * Tâche appelée.
     * @var Task_Base_Environment
     */
    private $_oBoundTask;

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
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct ($sProjectName, $sEnvName, $sExecutionID, ServiceContainer $oServiceContainer)
    {
        $sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
        $oProject = self::getProject($sProjectName);
        $this->sEnvName = $sEnvName;

        parent::__construct($oProject, $this, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array_merge(
            $this->aAttributeProperties,
            array('name' => Task::ATTRIBUTE_REQUIRED)
        );

        // Crée une instance de la tâche environnement appelée :
        $aTargets = $this->oProject->getSXE()->xpath("env[@name='$sEnvName']");
        if (count($aTargets) !== 1) {
            throw new UnexpectedValueException("Environment '$sEnvName' not found or not unique in this project!");
        }
        $this->_oBoundTask = new Task_Base_Environment(
            $aTargets[0], $this->oProject,
            $sBackupPath, $this->oServiceContainer
        );
    }

    public function setUp ()
    {
        parent::setUp();
        $this->_oBoundTask->setUp();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oBoundTask->backup();
        $this->_oBoundTask->execute();
    }

    public function backup ()
    {
    }

    public function getSXE ()
    {
        return $this->oXMLTask;
    }
}

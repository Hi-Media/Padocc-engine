<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_Project extends Task_WithProperties
{

    /**
     * Retourne la liste des projets dont le fichier de déploiement XML se trouve dans le chemin spécifié.
     * La liste est triée par ordre alphabétique.
     *
     * @param string $sRessourcesPath chemin hébergeant des configurations de déploiement au format XML
     * @return array la liste des projets dont le fichier de déploiement XML se trouve dans le chemin spécifié.
     * @throws UnexpectedValueException si chemin non trouvé
     */
    public static function getAllProjectsName ($sRessourcesPath)
    {
        $aProjectNames = array();
        $rHandle = @opendir($sRessourcesPath);
        if ($rHandle === false) {
            throw new UnexpectedValueException("Resource path not found: '$sRessourcesPath'.");
        } else {
            while ($file = readdir($rHandle)) {
                clearstatcache();
                $sProjectPath = $sRessourcesPath . '/' . $file;
                if (substr($file, strlen($file)-4, 4) == '.xml' && is_file($sProjectPath)) {
                    try {
                        $oProject = new SimpleXMLElement($sProjectPath, NULL, true);
                    } catch (Exception $oException) {
                        throw new RuntimeException("Bad project definition: '$sProjectPath'", 1, $oException);
                    }
                    if (isset($oProject['name'])) {
                        $aProjectNames[] = (string)$oProject['name'];
                    }
                }
            }
            closedir($rHandle);
        }
        sort($aProjectNames);
        return $aProjectNames;
    }

    /**
     * Retourne une instance SimpleXMLElement du projet spécifié.
     *
     * @param string $sProjectPath chemin menant au fichier de configuration XML du projet
     * @throws UnexpectedValueException si fichier XML du projet non trouvé
     * @return SimpleXMLElement instance du projet spécifié
     */
    public static function getSXEProject ($sProjectPath)
    {
        if ( ! file_exists($sProjectPath)) {
            throw new UnexpectedValueException("Project definition not found: '$sProjectPath'!");
        }
        try {
            $oSXE = new SimpleXMLElement($sProjectPath, NULL, true);
        } catch (Exception $oException) {
            throw new RuntimeException("Bad project definition: '$sProjectPath'", 1, $oException);
        }
        return $oSXE;
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
        $oProject = self::getSXEProject(DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml');
        $this->sEnvName = $sEnvName;

        parent::__construct($oProject, $this, $oServiceContainer);
        $this->_aAttrProperties = array_merge(
            $this->_aAttrProperties,
            array('name' => AttributeProperties::REQUIRED)
        );

        // Crée une instance de la tâche environnement appelée :
        $aTargets = $this->_oProject->getSXE()->xpath("env[@name='$sEnvName']");
        if (count($aTargets) !== 1) {
            throw new UnexpectedValueException("Environment '$sEnvName' not found or not unique in this project!");
        }
        $this->_oBoundTask = new Task_Base_Environment($aTargets[0], $this->_oProject, $this->_oServiceContainer);
    }

    public function check()
    {
        parent::check();
        $this->_oLogger->indent();
        foreach ($this->_aAttributes as $sAttribute => $sValue) {
            if ( ! empty($sValue) && $sAttribute !== 'name') {
                $this->_oLogger->log("Attribute: $sAttribute = '$sValue'");
            }
        }
        $this->_oLogger->unindent();
    }

    public function setUp ()
    {
        parent::setUp();
        $this->_oBoundTask->setUp();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oBoundTask->execute();
    }

    public function getSXE ()
    {
        return $this->_oXMLTask;
    }
}

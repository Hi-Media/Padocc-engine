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
class Task_Base_Project extends Task_WithProperties
{

    /**
     * Retourne la liste des projets dont le fichier de déploiement XML se trouve dans le chemin spécifié.
     * La liste est triée par ordre alphabétique.
     *
     * @param string $sRessourcesPath chemin hébergeant des configurations de déploiement au format XML
     * @return array la liste des projets dont le fichier de déploiement XML se trouve dans le chemin spécifié.
     * @throws UnexpectedValueException si chemin non trouvé
     * @throws UnexpectedValueException si fichier XML mal formaté
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
                        throw new UnexpectedValueException("Bad project definition: '$sProjectPath'", 1, $oException);
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
     * @throws UnexpectedValueException si fichier XML du projet mal formaté
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
            throw new UnexpectedValueException("Bad project definition: '$sProjectPath'", 1, $oException);
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
     * @throws UnexpectedValueException si fichier XML du projet non trouvé
     * @throws UnexpectedValueException si environnement non trouvé ou non unique
     */
    public function __construct ($sProjectPath, $sEnvName, ServiceContainer $oServiceContainer)
    {
        $oSXEProject = self::getSXEProject($sProjectPath);
        $this->sEnvName = $sEnvName;

        parent::__construct($oSXEProject, $this, $oServiceContainer);
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

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->_oBoundTask->setUp();
    }

    protected function _preExecute ()
    {
        parent::_preExecute();
        $this->_oLogger->indent();
        $this->_oShell->mkdir($this->_oProperties->getProperty('tmpdir'));
        $this->_oLogger->unindent();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oBoundTask->execute();
    }

    protected function _postExecute()
    {
        $this->_oLogger->indent();
        $this->_oShell->remove($this->_oProperties->getProperty('tmpdir'));
        $this->_oLogger->unindent();
        parent::_postExecute();
    }

    public function getSXE ()
    {
        return $this->_oXMLTask;
    }
}

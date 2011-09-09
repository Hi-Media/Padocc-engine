<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
abstract class Task
{

    /**
     * Suffixe concaténé au base directory pour obtenir le nom du répertoire regroupant les différentes releases.
     * @var string
     */
    const RELEASES_DIRECTORY_SUFFIX = '_releases';

    /**
     * Compteur d'instances pour mieux s'y retrouver dans les logs des tâches.
     * @var Numbering_Interface
     * @see $sName
     */
    protected $_oNumbering;

    /**
     * Collection de services.
     * @var ServiceContainer
     */
    protected $_oServiceContainer;

    /**
     * Instance de AttributeProperties.
     * @var AttributeProperties
     * @see check()
     */
    protected $_oAttrProperties;

    /**
     * Adaptater shell.
     * @var Shell_Interface
     */
    protected $_oShell;

    /**
     * Adaptateur de log.
     * @var Logger_IndentedInterface
     */
    protected $_oLogger;

    /**
     * Adaptateur de propriétés.
     * @var Properties_Interface
     */
    protected $_oProperties;

    /**
     * Contenu XML de la tâche.
     * @var SimpleXMLElement
     */
    protected $_oXMLTask;

    /**
     * @var Task_Base_Project
     */
    protected $_oProject;

    /**
     * Chaîne numérotant la tâche.
     * @var string
     * @see Numbering_Interface::getNextCounterValue()
     */
    protected $_sCounter;

    /**
     * Nom complet de la tâche, utilisé notamment dans le suivi d'exécution.
     * @var string
     */
    protected $_sName;

    /**
     * Attributs XML de la tâche.
     * Tableau ((string) clé, (string) valeur).
     * @var array
     */
    protected $_aAttributes;

    /**
     * Liste des propriétés des attributs déclarés de la tâche.
     *
     * Structure : array('attribute' => iValue, ...)
     * Où iValue vaut 0 ou une combinaison de bits au sens |,
     * à partir des constantes de la classe AttributeProperties.
     *
     * @var array
     * @see check()
     * @see AttributeProperties
     */
    protected $_aAttrProperties;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @throws RuntimeException si appelée directement sur Task.
     */
    public static function getTagName ()
    {
        throw new RuntimeException('Not implemented at this level!');
    }

    /**
     * Surcharge du constructeur, dont le premier paramètre est passé d'une instance de SimpleXMLElement à
     * un tableau associatif attribut => valeur.
     * Ne peut être utilisé pour créer une instance de Task_Base_Project.
     *
     * @param array $aAttributes Tableau associatif listant des attributs et leur valeur.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     * @return Task
     * @throws RuntimeException si appelée directement sur Task.
     */
    public static function getNewInstance (array $aAttributes, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        $sAttributes = '';
        foreach ($aAttributes as $sName => $sValue) {
            $sAttributes .= ' ' . $sName . '="' . $sValue . '"';
        }
        $sXML = '<' . static::getTagName() . $sAttributes . ' />';

        $oXML = new SimpleXMLElement($sXML);
        return new static($oXML, $oProject, $oServiceContainer);
    }

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oXMLTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        $this->_oXMLTask = $oXMLTask;
        $this->_oProject = $oProject;

        $this->_oServiceContainer = $oServiceContainer;
        $this->_oShell = $this->_oServiceContainer->getShellAdapter();
        $this->_oLogger = $this->_oServiceContainer->getLogAdapter();
        $this->_oProperties = $this->_oServiceContainer->getPropertiesAdapter();
        $this->_oNumbering = $this->_oServiceContainer->getNumberingAdapter();

        $this->_oAttrProperties = new AttributeProperties($this->_oServiceContainer);

        $sCounter = $this->_oNumbering->getNextCounterValue();
        $this->_sCounter = $sCounter;
        $this->_sName = (strlen($this->_sCounter) === 0 ? '' : $this->_sCounter . '_') . get_class($this);

        $this->_aAttrProperties = array();
        $this->_fetchAttributes();
    }

    protected function _fetchAttributes ()
    {
        $this->_aAttributes = array();
        foreach ($this->_oXMLTask->attributes() as $key => $val) {
            $this->_aAttributes[$key] = (string)$val;
        }
    }

    protected function _processPath ($sPath)
    {
        $aExpandedPaths = $this->_expandPath($sPath);
        $aReroutedPaths = $this->_reroutePaths($aExpandedPaths);
        return $aReroutedPaths;
    }

    protected function _processSimplePath ($sPath)
    {
        $aProcessedPaths = $this->_processPath($sPath);
        if (count($aProcessedPaths) !== 1) {
            $sMsg = "String '$sPath' should return a single path after process: " . print_r($aProcessedPaths, true);
            throw new RuntimeException($sMsg);
        }
        return reset($aProcessedPaths);
    }

    /**
     * Retourne la liste de tous les chemins générés en remplaçant les paramètres
     * du chemin spécifié par leurs valeurs.
     *
     * @param string $sPath chemin pouvant contenir des paramètres
     * @return array liste de tous les chemins générés en remplaçant les paramètres par leurs valeurs
     */
    protected function _expandPath ($sPath)
    {
        if (preg_match_all('/\$\{([^}]+)\}/i', $sPath, $aMatches) > 0) {
            // On traite dans un premier temps un maximum de remplacements sans récursivité :
            $aPaths = array($sPath);
            foreach ($aMatches[1] as $property) {
                $aToProcessPaths = $aPaths;
                $aPaths = array();

                $sRawValue = $this->_oProperties->getProperty($property);
                $values = explode(' ', $sRawValue);
                foreach ($aToProcessPaths as $sPath) {
                    foreach ($values as $value) {
                        $aPaths[] = str_replace('${' . $property . '}', $value, $sPath);
                    }
                }
            }

            // Perfectible mais suffisant, récursivité sur les propriétés de propriétés :
            $aRecursivePaths = $aPaths;
            $aPaths = array();
            foreach ($aRecursivePaths as $sPath) {
                $aPaths = array_merge($aPaths, $this->_expandPath($sPath));
            }
            $aPaths = array_values(array_unique($aPaths));
        } else {
            $aPaths = array($sPath);
        }

        return $aPaths;
    }

    //private static $aPreparedEnv = array();
    protected function _reroutePaths ($aPaths)
    {
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
            $sReleaseSymLink = $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX . '/'
                             . $this->_oProperties->getProperty('execution_id');
            for ($i=0, $iMax=count($aPaths); $i<$iMax; $i++) {
                if (preg_match('#^(.*?:)' . preg_quote($sBaseSymLink, '#') . '\b#', $aPaths[$i], $aMatches) === 1) {
                    $sNewPath = str_replace(
                        $aMatches[1] . $sBaseSymLink,
                        $aMatches[1] . $sReleaseSymLink,
                        $aPaths[$i]
                    );
                    $aPaths[$i] = $sNewPath;
                }
            }
        }
        return $aPaths;
    }

    protected static $_aRegisteredPaths = array();

    protected function _registerPaths ()
    {
        //$this->_oLogger->log("registerPaths");
        foreach ($this->_aAttrProperties as $sAttribute => $iProperties) {
            if (
                (($iProperties & AttributeProperties::DIR) > 0 || ($iProperties & AttributeProperties::FILE) > 0)
                && isset($this->_aAttributes[$sAttribute])
            ) {
                self::$_aRegisteredPaths[$this->_aAttributes[$sAttribute]] = true;
            }
        }
        ksort(self::$_aRegisteredPaths);
    }

    public function setUp ()
    {
        $this->check();
        $this->_registerPaths();
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas d'attribut non permis
     * @see self::$aAttributeProperties
     */
    protected function check ()
    {
        $sMsg = "Check '" . $this->_sName . "' task";
        if ( ! empty($this->_aAttributes['name'])) {
            $sMsg .= ': \'' . $this->_aAttributes['name'] . '\'';
        }
        $this->_oLogger->log($sMsg);
        $this->_oLogger->indent();
        $this->_oAttrProperties->checkAttributes($this->_aAttrProperties, $this->_aAttributes);
        $this->_oLogger->unindent();
    }

    protected function _preExecute ()
    {
        $this->_oLogger->log("Execute '" . $this->_sName . "' task");
    }

    protected function _centralExecute ()
    {
    }

    protected function _postExecute ()
    {
    }

    public function execute ()
    {
        $this->_preExecute();
        $this->_centralExecute();
        $this->_postExecute();
    }
}

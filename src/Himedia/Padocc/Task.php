<?php

namespace Himedia\Padocc;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Numbering\NumberingInterface;
use Himedia\Padocc\Properties\PropertiesInterface;
use Himedia\Padocc\Task\Base\Project;
use Psr\Log\LoggerInterface;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
abstract class Task
{

    /**
     * Compteur d'instances pour mieux s'y retrouver dans les logs des tâches.
     * @var NumberingInterface
     * @see $sName
     */
    protected $oNumbering;

    /**
     * Collection de services.
     *
     * @var DIContainer
     */
    protected $oDIContainer;

    /**
     * Instance de AttributeProperties.
     * @var AttributeProperties
     * @see check()
     */
    protected $oAttrProperties;

    /**
     * Adaptater shell.
     * @var ShellAdapter
     */
    protected $oShell;

    /**
     * Adaptateur de log.
     * @var LoggerInterface
     */
    protected $oLogger;

    /**
     * @var array
     */
    protected $aConfig;

    /**
     * Adaptateur de propriétés.
     * @var PropertiesInterface
     */
    protected $oProperties;

    /**
     * Contenu XML de la tâche.
     * @var \SimpleXMLElement
     */
    protected $oXMLTask;

    /**
     * @var Project
     */
    protected $oProject;

    /**
     * Chaîne numérotant la tâche.
     * @var string
     * @see NumberingInterface::getNextCounterValue()
     */
    protected $sCounter;

    /**
     * Nom complet de la tâche, utilisé notamment dans le suivi d'exécution.
     * @var string
     */
    protected $sName;

    /**
     * Attributs XML de la tâche.
     * Tableau ((string) clé, (string) valeur).
     * @var array
     */
    protected $aAttValues;

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
    protected $aAttrProperties;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @throws \RuntimeException si appelée directement sur Task.
     */
    public static function getTagName ()
    {
        throw new \RuntimeException('Not implemented at this level!');
    }

    /**
     * Surcharge du constructeur, dont le premier paramètre est passé d'une instance de \SimpleXMLElement à
     * un tableau associatif attribut => valeur.
     * Ne peut être utilisé pour créer une instance de Project.
     *
     * @param array $aAttributes Tableau associatif listant des attributs et leur valeur.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     * @return Task
     * @throws \RuntimeException si appelée directement sur Task.
     */
    public static function getNewInstance (array $aAttributes, Project $oProject, DIContainer $oDIContainer)
    {
        $sAttributes = '';
        foreach ($aAttributes as $sName => $sValue) {
            $sAttributes .= ' ' . $sName . '="' . $sValue . '"';
        }
        $sXML = '<' . static::getTagName() . $sAttributes . ' />';

        $oXML = new \SimpleXMLElement($sXML);
        return new static($oXML, $oProject, $oDIContainer);
    }

    /**
     * Constructeur.
     * @param \SimpleXMLElement $oXMLTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oXMLTask, Project $oProject, DIContainer $oDIContainer)
    {
        $this->oXMLTask = $oXMLTask;
        $this->oProject = $oProject;

        $this->oDIContainer = $oDIContainer;
        $this->oShell = $this->oDIContainer->getShellAdapter();
        $this->oLogger = $this->oDIContainer->getLogger();
        $this->oProperties = $this->oDIContainer->getPropertiesAdapter();
        $this->oNumbering = $this->oDIContainer->getNumberingAdapter();
        $this->aConfig = $this->oDIContainer->getConfig();

        // TODO à injecter :
        $this->oAttrProperties = new AttributeProperties($this->oDIContainer);

        $sCounter = $this->oNumbering->getNextCounterValue();
        $this->sCounter = $sCounter;
        $this->sName = (strlen($this->sCounter) === 0 ? '' : $this->sCounter . '_') . get_class($this);

        $this->aAttrProperties = array();
        $this->fetchAttributes();
    }

    /**
     * Récupère les attributs XML du nœud $this->oXMLTask et les enregistre dans $this->aAttValues.
     */
    protected function fetchAttributes ()
    {
        $this->aAttValues = array();
        foreach ($this->oXMLTask->attributes() as $key => $val) {
            $this->aAttValues[$key] = (string)$val;
        }
    }

    /**
     * Appels combinés à expandPath() puis reroutePaths()
     *
     * @param string $sPath chemin pouvant contenir des paramètres
     * @return array liste de tous les chemins générés en remplaçant les paramètres par leurs valeurs
     * et en reroutant ceux tombant dans 'basedir'.
     * @see expandPath()
     * @see reroutePaths()
     */
    protected function processPath ($sPath)
    {
        $aExpandedPaths = $this->expandPath($sPath);
        $aReroutedPaths = $this->reroutePaths($aExpandedPaths);
        return $aReroutedPaths;
    }

    /**
     * Appel à processPath(), puis retourne le premier chemin récupéré
     * en s'assurant qu'il n'y en a pas d'autres.
     *
     * @param string $sPath chemin pouvant contenir des paramètres
     * @return string l'unique chemin généré en remplaçant les paramètres par leurs valeurs
     * et en reroutant le chemin s'il tombe dans 'basedir'.
     * @throws \RuntimeException si plus d'un chemin a été généré
     * @see processPath()
     */
    protected function processSimplePath ($sPath)
    {
        $aProcessedPaths = $this->processPath($sPath);
        if (count($aProcessedPaths) !== 1) {
            $sMsg = "String '$sPath' should return a single path after process: " . print_r($aProcessedPaths, true);
            throw new \RuntimeException($sMsg);
        }
        return reset($aProcessedPaths);
    }

    /**
     * Retourne la liste de tous les chemins générés en remplaçant les paramètres
     * du chemin spécifié par leurs valeurs.
     *
     * @param string $sPath chemin pouvant contenir des paramètres
     * @return array liste de tous les chemins générés en remplaçant les paramètres par leurs valeurs,
     */
    protected function expandPath ($sPath)
    {
        if (preg_match_all('/\$\{([^}]+)\}/i', $sPath, $aMatches) > 0) {
            // On traite dans un premier temps un maximum de remplacements sans récursivité :
            $aPaths = array($sPath);
            foreach ($aMatches[1] as $property) {
                $aToProcessPaths = $aPaths;
                $aPaths = array();

                $sRawValue = $this->oProperties->getProperty($property);
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
                $aPaths = array_merge($aPaths, $this->expandPath($sPath));
            }
            $aPaths = array_values(array_unique($aPaths));
        } else {
            $aPaths = array($sPath);
        }

        // Set default remote user if not specified:
        foreach ($aPaths as $idx => $sPath) {
            if (preg_match('#^[^:@]+:(?!//).+$#i', $sPath) === 1) {
                $aPaths[$idx] = $this->aConfig['default_remote_shell_user'] . '@' . $sPath;
            }
        }
        return $aPaths;
    }

    /**
     * Reroute de façon transparente tous les chemins système inclus ou égal à la valeur de la propriété 'basedir'
     * dans le répertoire de releases nommé de la valeur de 'basedir'
     * avec le suffixe $aConfig['symlink_releases_dir_suffix'].
     * Les autres chemins, ceux hors 'basedir', restent inchangés.
     *
     * @param array $aPaths liste de chemins sans paramètres (par exemple provenant de expandPath())
     * @return array liste de ces mêmes chemins en reroutant ceux tombant dans 'basedir'.
     */
    protected function reroutePaths (array $aPaths)
    {
        if ($this->oProperties->getProperty('with_symlinks') === 'true') {
            $sBaseSymLink = $this->oProperties->getProperty('basedir');
            $sReleaseSymLink = $sBaseSymLink . $this->aConfig['symlink_releases_dir_suffix'] . '/'
                             . $this->oProperties->getProperty('execution_id');
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

    /**
     * Centralisation de tous les chemins systèmes définis dans l'une ou l'autre des tâches.
     * Dédoublonnés et triés par ordre alphabétique.
     * Structure : array((string)path => true, ...)
     * @var array
     * @see registerPaths()
     */
    protected static $aRegisteredPaths = array();

    /**
     * Collecte les chemins système définis dans les attributs de la tâche,
     * et les centralise au niveau de la classe pour analyse ultérieure.
     */
    protected function registerPaths ()
    {
        foreach ($this->aAttrProperties as $sAttribute => $iProperties) {
            if ((($iProperties & AttributeProperties::DIR) > 0 || ($iProperties & AttributeProperties::FILE) > 0)
                && isset($this->aAttValues[$sAttribute])
            ) {
                self::$aRegisteredPaths[$this->aAttValues[$sAttribute]] = true;
            }
        }
        ksort(self::$aRegisteredPaths);
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        $this->check();
        $this->registerPaths();
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas d'attribut non permis
     * @see self::$aAttributeProperties
     */
    protected function check ()
    {
        $sMsg = "Check '" . $this->sName . "' task";
        if (! empty($this->aAttValues['name'])) {
            $sMsg .= ': \'' . $this->aAttValues['name'] . '\'';
        }
        $this->oLogger->info($sMsg . '+++');
        $this->oAttrProperties->checkAttributes($this->aAttrProperties, $this->aAttValues);
        $this->oLogger->info('---');
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::preExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function preExecute ()
    {
        $sMsg = "Execute '" . $this->sName . "' task";
        if (! empty($this->aAttValues['name'])) {
            $sMsg .= ': \'' . $this->aAttValues['name'] . '\'';
        }
        $this->oLogger->info($sMsg);
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
    }

    /**
     * Phase de post-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement finir par "parent::postExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function postExecute ()
    {
    }

    /**
     * Exécute la tâche en trois phases : pré-traitements, traitements centraux et post-traitements.
     * Si l'on a la classe F fille de la tâche P, alors on peut s'attendre à :
     *      P::preExecute()
     *      F::preExecute()
     *      P::centralExecute()
     *      F::centralExecute()
     *      F::postExecute()
     *      P::postExecute()
     *
     * @see preExecute()
     * @see centralExecute()
     * @see postExecute()
     */
    public function execute ()
    {
        $this->preExecute();
        $this->centralExecute();
        $this->postExecute();
    }
}

<?php

abstract class Task {

	/**
	 * Propriété d'attribut : autorise l'utilisation des '${parameter}'.
	 * @var int
	 */
	const ATTRIBUTE_ALLOW_PARAMETER = 1;

	/**
	 * Propriété d'attribut : l'attribut désigne un répertoire.
	 * @var int
	 */
	const ATTRIBUTE_DIR = 2;

	/**
	 * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les répertoires.
	 * @var int
	 */
	const ATTRIBUTE_DIRJOKER = 4;

	/**
	 * Propriété d'attribut : l'attribut désigne un fichier.
	 * @var int
	 */
	const ATTRIBUTE_FILE = 8;

	/**
	 * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les fichiers.
	 * @var int
	 */
	const ATTRIBUTE_FILEJOKER = 16;

	/**
	 * Propriété d'attribut : l'attribut est obligatoire.
	 * @var int
	 */
	const ATTRIBUTE_REQUIRED = 32;

	/**
	 * Propriété d'attribut : l'attribut est un fichier ou un répertoire source et doit donc exister.
	 * @var int
	 */
	const ATTRIBUTE_SRC_PATH = 64;

	/**
	 * Compteur d'instances pour s'y retrouver dans les backups des tâches.
	 * @var Numbering_Interface
	 * @see $sName
	 * @see $sBackupPath
	 */
	protected $oNumbering;

	/**
	 * Collection de services.
	 * @var ServiceContainer
	 */
	protected $oServiceContainer;

	/**
	 * Adaptater shell.
	 * @var Shell_Interface
	 */
	protected $oShell;

	/**
	 * Adaptateur de log.
	 * @var Logger_IndentedInterface
	 */
	protected $oLogger;

	/**
	 * Adaptateur de propriétés.
	 * @var Properties_Interface
	 */
	protected $oProperties;

	/**
	 * Contenu XML de la tâche.
	 * @var SimpleXMLElement
	 */
	protected $oTask;

	/**
	 * @var Task_Base_Project
	 */
	protected $oProject;

	/**
	 * Chaîne numérotant la tâche.
	 * @var string
	 * @see Numbering_Interface::getNextCounterValue()
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
	protected $aAttributes;

	/**
	 * Liste des propriétés des attributs déclarés de la tâche.
	 *
	 * Structure : array(
	 *    'attribute' => array(
	 *       [self::ATTRIBUTE_ALLOW_PARAMETER, self::ATTRIBUTE_DIR, self::ATTRIBUTE_DIRJOKER,
	 *       self::ATTRIBUTE_FILE, self::ATTRIBUTE_FILEJOKER, self::ATTRIBUTE_REQUIRED, self::ATTRIBUTE_SRC_PATH]
	 *    ), ...
	 * )
	 *
	 * @var array
	 * @see check()
	 */
	protected $aAttributeProperties;

	/**
	 * Chemin du répertoire backup dédié à la tâche.
	 * De la forme : '[base]/[index]_[name]',
	 *    où 'base' est fourni au constructeur,
	 *    où 'index' est l'ordre d'exécution de la tâche
	 *    et où 'name' est le nom de la classe de la tâche.
	 * @var string
	 */
	protected $sBackupPath;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		throw new RuntimeException('Not implemented!');
	}

	/**
	 * Surcharge du constructeur, dont le premier paramètre est passé d'une instance de SimpleXMLElement à
	 * un tableau associatif attribut => valeur.
	 *
	 * @param array $aAttributes Tableau associatif listant des attributs et leur valeur.
	 * @param Task_Base_Project $oProject Super tâche projet.
	 * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
	 * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
	 * @return Task
	 */
	public static function getNewInstance (array $aAttributes, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		$sAttributes = '';
		foreach ($aAttributes as $sName => $sValue) {
			$sAttributes .= ' ' . $sName . '="' . $sValue . '"';
		}
		$sXML = '<' . static::getTagName() . $sAttributes . ' />';

		$oXML = new SimpleXMLElement($sXML);
		return new static($oXML, $oProject, $sBackupPath, $oServiceContainer);
	}

	/**
	 * Constructeur.
	 *
	 * @param SimpleXMLElement $oTask Contenu XML de la tâche.
	 * @param Task_Base_Project $oProject Super tâche projet.
	 * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
	 * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
	 */
	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		$this->oTask = $oTask;
		$this->oProject = $oProject;

		$this->oServiceContainer = $oServiceContainer;
		$this->oShell = $this->oServiceContainer->getShellAdapter();
		$this->oLogger = $this->oServiceContainer->getLogAdapter();
		$this->oProperties = $this->oServiceContainer->getPropertiesAdapter();
		$this->oNumbering = $this->oServiceContainer->getNumberingAdapter();

		$sCounter = $this->oNumbering->getNextCounterValue();
		//$this->sCounter = (strlen($sCounter) === 2 ? '' : substr($sCounter, 2));
		$this->sCounter = $sCounter;
		$this->sName = (strlen($this->sCounter) === 0 ? '' : $this->sCounter . '_') . get_class($this);
		$this->sBackupPath = $sBackupPath . '/' . $this->sName;

		$this->aAttributeProperties = array();
		$this->fetchAttributes();
	}

	protected function fetchAttributes () {
		$this->aAttributes = array();
		foreach ($this->oTask->attributes() as $key => $val) {
			$this->aAttributes[$key] = (string)$val;
		}
	}

	/**
	 * Retourne la liste de tous les chemins générés en remplaçant les paramètres du chemin spécifié par leurs valeurs.
	 *
	 * @param string $sPath chemin pouvant contenir des paramètres
	 * @return array liste de tous les chemins générés en remplaçant les paramètres du chemin spécifié par leurs valeurs
	 */
	protected function _expandPaths ($sPath) {
		if (preg_match_all('/\$\{([^}]*)\}/i', $sPath, $aMatches) > 0) {
			$aPaths = array($sPath);
			foreach ($aMatches[1] as $property) {
				$aToProcessPaths = $aPaths;
				$aPaths = array();

				$raw_value = $this->oProperties->getProperty($property);
				$values = explode(' ', $raw_value);
				foreach ($aToProcessPaths as $s) {
					foreach ($values as $value) {
						$aPaths[] = str_replace('${' . $property . '}', $value, $s);
					}
				}
			}
		} else {
			$aPaths = array($sPath);
		}
		return $aPaths;
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
	 * @throws DomainException en cas de valeur non permise
	 * @see self::$aAttributeProperties
	 */
	public function check () {
		$this->oLogger->log("Check '" . $this->sName . "' task");
		$this->oLogger->indent();

		$aAvailablesAttributes = array_keys($this->aAttributeProperties);
		$aUnknownAttributes = array_diff(array_keys($this->aAttributes), $aAvailablesAttributes);
		if (count($aUnknownAttributes) > 0) {
			throw new DomainException(
				"Available attributes: " . print_r($aAvailablesAttributes, true)
				. " => Unknown attribute(s): " . print_r($aUnknownAttributes, true));
		}

		foreach ($this->aAttributeProperties as $sAttribute => $aProperties) {
			if (empty($this->aAttributes[$sAttribute]) && in_array(self::ATTRIBUTE_REQUIRED, $aProperties)) {
				throw new UnexpectedValueException("'$sAttribute' attribute is required!");
			} else if ( ! empty($this->aAttributes[$sAttribute])) {
				if (in_array(self::ATTRIBUTE_DIR, $aProperties) || in_array(self::ATTRIBUTE_FILE, $aProperties)) {
					$this->aAttributes[$sAttribute] = str_replace('\\', '/', $this->aAttributes[$sAttribute]);
				}

				if (preg_match('#[*?].*/#', $this->aAttributes[$sAttribute]) !== 0 && ! in_array(self::ATTRIBUTE_DIRJOKER, $aProperties)) {
					throw new DomainException("'*' and '?' jokers are not authorized for directory in '$sAttribute' attribute!");
				}

				if (preg_match('#[*?](.*[^/])?$#', $this->aAttributes[$sAttribute]) !== 0 && ! in_array(self::ATTRIBUTE_FILEJOKER, $aProperties)) {
					throw new DomainException("'*' and '?' jokers are not authorized for filename in '$sAttribute' attribute!");
				}

				if (preg_match('#\$\{[^}]*\}#', $this->aAttributes[$sAttribute]) !== 0 && ! in_array(self::ATTRIBUTE_ALLOW_PARAMETER, $aProperties)) {
					throw new DomainException("Parameters are not allowed in '$sAttribute' attribute!");
				}

				// Suppression de l'éventuel slash terminal :
				if (in_array(self::ATTRIBUTE_DIR, $aProperties)) {
					$this->aAttributes[$sAttribute] = preg_replace('#/$#', '', $this->aAttributes[$sAttribute]);
				}

				if (
						in_array(self::ATTRIBUTE_SRC_PATH, $aProperties)
						&& preg_match('#\*|\?#', $this->aAttributes[$sAttribute]) === 0
						&& $this->oShell->getFileStatus($this->aAttributes[$sAttribute]) === 0
				) {
					throw new UnexpectedValueException("File or directory '" . $this->aAttributes[$sAttribute] . "' not found!");
				}
			}
		}
		$this->oLogger->unindent();
	}

	public function execute () {
		$this->oLogger->log("Execute '" . $this->sName . "' task");
	}

	public abstract function backup ();
}
<?php

abstract class Task {

	/**
	 * Compteur d'instances pour s'y retrouver dans les backups des tâches.
	 * Défini à -1 pour que la tâche projet soit en 0 et la 1re 'vraie' tâche en 1.
	 * @var int
	 * @see $sName
	 * @see $sBackupPath
	 */
	private static $iCounter = -1;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		throw new RuntimeException('Not implemented!');
	}

	/**
	 * Adaptater shell.
	 * @var Shell_Interface
	 */
	protected $oShell;

	/**
	 * Adaptateur de log.
	 * @var Logger_Interface
	 */
	protected $oLogger;

	/**
	 * Contenu XML de la tâche.
	 * @var SimpleXMLElement
	 */
	protected $oTask;

	/**
	 * @var Task_Base_Project
	 */
	protected $oProject;

	protected $sName;

	/**
	 * Attributs XML de la tâche.
	 * Tableau ((string) clé, (string) valeur).
	 * @var array
	 */
	protected $aAttributes;

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

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, Shell_Interface $oShell, Logger_Interface $oLogger) {
		$this->oTask = $oTask;
		$this->oProject = $oProject;
		$this->sName = sprintf('%03d', (++self::$iCounter)) . '_' . get_class($this);
		$this->sBackupPath = $sBackupPath . '/' . $this->sName;
		$this->oShell = $oShell;
		$this->oLogger = $oLogger;
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
	protected function expandPaths ($sPath) {
		if (preg_match_all('/\$\{([^}]*)\}/i', $sPath, $aMatches) > 0) {
			$aPaths = array($sPath);
			foreach ($aMatches[1] as $property) {
				$aToProcessPaths = $aPaths;
				$aPaths = array();

					$raw_value = $this->oProject->getProperty($property);
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

	public function check () {
		$this->oLogger->log("Check '" . $this->sName . "' task...");

		$aAvailablesAttributes = array_keys($this->aAttributeProperties);
		$aUnknownAttributes = array_diff(array_keys($this->aAttributes), $aAvailablesAttributes);
		if (count($aUnknownAttributes) > 0) {
			throw new UnexpectedValueException("Available attributes: " . print_r($aAvailablesAttributes, true) . " => Unknown attribute(s): " . print_r($aUnknownAttributes, true));
		}

		foreach ($this->aAttributeProperties as $sAttribute => $aProperties) {
			if (empty($this->aAttributes[$sAttribute])) {
				if (in_array('required', $aProperties)) {
					throw new UnexpectedValueException("'$sAttribute' attribute is required!");
				}
			} else {
				if (in_array('dir', $aProperties) || in_array('file', $aProperties)) {
					$this->aAttributes[$sAttribute] = str_replace('\\', '/', $this->aAttributes[$sAttribute]);
				}

				if (preg_match('#[*?].*/#', $this->aAttributes[$sAttribute]) !== 0 && ! in_array('dirjoker', $aProperties)) {
					throw new DomainException("'*' and '?' jokers are not authorized for directory in '$sAttribute' attribute!");
				}

				if (preg_match('#[*?](.*[^/])?$#', $this->aAttributes[$sAttribute]) !== 0 && ! in_array('filejoker', $aProperties)) {
					throw new DomainException("'*' and '?' jokers are not authorized for filename in '$sAttribute' attribute!");
				}

				if (preg_match('#\$\{[^}]*\}#', $this->aAttributes[$sAttribute]) !== 0 && ! in_array('allow_parameters', $aProperties)) {
					throw new DomainException("Parameters are not allowed in '$sAttribute' attribute!");
				}

				// Suppression de l'éventuel slash terminal :
				if (in_array('dir', $aProperties)) {
					$this->aAttributes[$sAttribute] = preg_replace('#/$#', '', $this->aAttributes[$sAttribute]);
				}

				if (
						in_array('srcpath', $aProperties)
						&& preg_match('#\*|\?#', $this->aAttributes[$sAttribute]) === 0
						&& $this->oShell->getFileStatus($this->aAttributes[$sAttribute]) === 0
				) {
					throw new RuntimeException("File or directory '" . $this->aAttributes[$sAttribute] . "' not found!");
				}
			}
		}

		$this->oLogger->log("OK.\n");
	}

	public abstract function execute ();

	public abstract function backup ();
}
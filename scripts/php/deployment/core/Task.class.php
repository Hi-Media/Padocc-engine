<?php

abstract class Task {

	/**
	 * Compteur d'instances pour s'y retrouver dans les backups des tâches.
	 * @var int
	 * @see $sBackupDir
	 */
	private static $iCounter = 0;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		throw new RuntimeException('Unimplemented!');
	}

	/**
	 * Contenu XML de la tâche.
	 * @var SimpleXMLElement
	 */
	private $oTask;

	/**
	 * Attributs XML de la tâche.
	 * Tableau ((string) clé, (string) valeur).
	 * @var array
	 */
	protected $aAttributes;

	protected $sBackupDir;

	public function __construct (SimpleXMLElement $oTask, $sBackupDir) {
		$this->oTask = $oTask;
		$this->sBackupDir = $sBackupDir . '/' . (++self::$iCounter) . '_' . get_class($this);

		// Récupération des attributs :
		$this->aAttributes = array();
		foreach ($this->oTask->attributes() as $key => $val) {
			$this->aAttributes[$key] = (string)$val;
		}

		$this->_check();
	}

	protected abstract function _check();

	public abstract function execute ();
}
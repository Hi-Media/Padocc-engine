<?php

abstract class Task {

	/**
	 * Compteur d'instances pour s'y retrouver dans les backups des tâches.
	 * @var int
	 * @see $sBackupPath
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

	/**
	 * Chemin du répertoire backup dédié à la tâche.
	 * De la forme : '[base]/[index]_[name]',
	 *    où 'base' est fourni au constructeur,
	 *    où 'index' est l'ordre d'exécution de la tâche
	 *    et où 'name' est le nom de la classe de la tâche.
	 * @var string
	 */
	protected $sBackupPath;

	public function __construct (SimpleXMLElement $oTask, $sBackupPath) {
		$this->oTask = $oTask;
		$this->sBackupPath = $sBackupPath . '/' . sprintf('%03d', (++self::$iCounter)) . '_' . get_class($this);

		// Récupération des attributs :
		$this->aAttributes = array();
		foreach ($this->oTask->attributes() as $key => $val) {
			$this->aAttributes[$key] = (string)$val;
		}

		$this->_check();
	}

	protected abstract function _check();

	public abstract function execute ();

	public abstract function backup ();
}
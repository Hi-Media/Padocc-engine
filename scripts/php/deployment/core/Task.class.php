<?php

abstract class Task {

	private static $iCounter = 0;

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
	 * @var array
	 */
	protected $aAttributes;

	protected $sBackupDir;

	public function __construct (SimpleXMLElement $oTask, $sBackupDir) {
		$this->oTask = $oTask;
		$this->sBackupDir = $sBackupDir . '/' . (++self::$iCounter) . '_' . get_class($this);

		$this->aAttributes = array();
		foreach ($this->oTask->attributes() as $key => $val) {
			$this->aAttributes[$key] = (string)$val;
		}
		//print_r($this->aAttributes);

		$this->_check();
	}

	protected abstract function _check();

	public abstract function execute ();
}
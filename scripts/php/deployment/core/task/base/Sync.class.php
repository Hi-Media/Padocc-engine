<?php

class Task_Base_Sync extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'sync';
	}

	public function __construct (SimpleXMLElement $oTask, $sBackupDir) {
		parent::__construct($oTask, $sBackupDir);
	}

	protected function _check () {

	}

	public function execute () {}
}
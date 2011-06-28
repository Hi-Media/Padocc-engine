<?php

class Task_Base_Environment extends Task_Base_Target {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'env';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, Shell_Interface $oShell, Logger_Interface $oLogger) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oShell, $oLogger);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'mail' => array()
		);
	}
}

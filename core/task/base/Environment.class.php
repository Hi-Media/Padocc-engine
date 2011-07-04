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

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'mail' => array(),
			'propertyinifile' => array('srcpath'),
			'propertyshellfile' => array('srcpath'),
		);
	}
}

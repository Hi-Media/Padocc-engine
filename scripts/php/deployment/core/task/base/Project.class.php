<?php

class Task_Base_Project extends Task {

	private $aTasks;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'project';
	}

	public function __construct ($sProjectName, $sTargetName, $sExecutionID) {
		$sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
		$oProject = Tasks::getProject($sProjectName);

		parent::__construct($oProject, $oProject, $sBackupPath);
		$this->aAttributeProperties = array(
			'name' => array('required')
		);
		$oTarget = Tasks::getTarget($this->oProject, $sTargetName);
		$this->aTasks = Tasks::getTaskInstances($oTarget, $this->oProject, $sBackupPath);	// et non $this->sBackupPath, pour les sous-tâches
	}

	public function check () {
		parent::check();
		foreach ($this->aTasks as $oTask) {
			$oTask->check();
		}
	}

	public function execute () {
		foreach ($this->aTasks as $oTask) {
			$oTask->backup();
			$oTask->execute();
		}
	}

	public function backup () {}
}
<?php

class Task_Base_Call extends Task {

	protected $aTasks;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'call';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, IShell $oShell, ILog $oLog) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oShell, $oLog);
		$this->aAttributeProperties = array(
			'target' => array('required')
		);
		$oTarget = Tasks::getTarget($this->oProject->getSXE(), $this->aAttributes['target']);
		$this->aTasks = Tasks::getTaskInstances($oTarget, $this->oProject, $sBackupPath, $this->oShell, $this->oLog); // et non $this->sBackupPath, pour les sous-tâches
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
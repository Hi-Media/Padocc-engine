<?php

class Task_Base_Call extends Task {

	/**
	 * Tâche appelée.
	 * @var Task
	 */
	protected $oBoundTask;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'call';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, Shell_Interface $oShell, Logger_Interface $oLogger) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oShell, $oLogger);
		$this->aAttributeProperties = array(
			'target' => array('required')
		);
		self::addCounterDivision();
		$oTarget = Tasks::getTarget($this->oProject->getSXE(), $this->aAttributes['target']);
		//$this->aTasks = Tasks::getTaskInstances($oTarget, $this->oProject, $sBackupPath, $this->oShell, $this->oLogger); // et non $this->sBackupPath, pour les sous-tâches
		$this->oBoundTask = new Task_Base_Target($oTarget, $this->oProject, $sBackupPath, $this->oShell, $this->oLogger);
		self::removeCounterDivision();
	}

	public function check () {
		parent::check();
		$this->oBoundTask->check();
	}

	public function execute () {
		$this->oBoundTask->backup();
		$this->oBoundTask->execute();
	}

	public function backup () {}
}

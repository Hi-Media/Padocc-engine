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

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'target' => array('required')
		);
		$this->oNumbering->addCounterDivision();
		$this->oBoundTask = $this->getBoundTask($sBackupPath);
		//$oTarget = Tasks::getTarget($this->oProject->getSXE(), $this->aAttributes['target']);
		//$this->oBoundTask = new Task_Base_Target($oTarget, $this->oProject, $sBackupPath, $this->oShell, $this->oLogger);
		$this->oNumbering->removeCounterDivision();
	}

	protected function getBoundTask ($sBackupPath) {
		//$oTarget = Tasks::getTarget($this->oProject->getSXE(), $this->aAttributes['target']);
		$aTargets = $this->oProject->getSXE()->xpath("target[@name='" . $this->aAttributes['target'] . "']");
		if (count($aTargets) !== 1) {
			throw new Exception("Target '" . $this->aAttributes['target'] . "' not found or not unique in this project!");
		}
		return new Task_Base_Target($aTargets[0], $this->oProject, $sBackupPath, $this->oShell, $this->oLogger);
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

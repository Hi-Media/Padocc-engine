<?php

class Task_Base_Target extends Task {

	protected $aTasks;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'target';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'mail' => array()
		);
		$this->oNumbering->addCounterDivision();
		$this->aTasks = $this->getTaskInstances($oTask, $this->oProject, $sBackupPath); // et non $this->sBackupPath, pour les sous-tâches
		$this->oNumbering->removeCounterDivision();
	}

	/**
	 * Retourne la liste des instances de tâches correspondant à chacune des tâches XML devant être exécutée
	 * à l'intérieur du noeud XML spécifié.
	 *
	 * @param SimpleXMLElement $oTarget
	 * @param Task_Base_Project $oProject
	 * @param string $sBackupPath
	 * @param Shell_Interface $oShell
	 * @param Logger_Interface $oLogger
	 * @return array liste d'instances de type Task
	 * @throws Exception si tag XML inconnu.
	 * @see Task
	 */
	private function getTaskInstances (SimpleXMLElement $oTarget, Task_Base_Project $oProject, $sBackupPath) {
		$this->oLogger->log('Initialize tasks...');
		$aAvailableTasks = Tasks::getAvailableTasks();

		// Mise à plat des tâches car SimpleXML regroupe celles successives de même nom
		// dans un tableau et les autres sont hors tableau :
		$aTasks = array();
		foreach ($oTarget->children() as $sTag => $mTasks) {
			if (is_array($mTasks)) {
				foreach ($mTasks as $oTask) {
					$aTasks[] = array($sTag, $oTask);
				}
			} else {
				$aTasks[] = array($sTag, $mTasks);
			}
		}

		// Création des instances de tâches :
		$aTaskInstances = array();
		foreach ($aTasks as $aTask) {
			list($sTag, $oTask) = $aTask;
			if ( ! isset($aAvailableTasks[$sTag])) {
				throw new RuntimeException("Unkown task tag: '$sTag'!");
			} else {
				$aTaskInstances[] = new $aAvailableTasks[$sTag]($oTask, $oProject, $sBackupPath, $this->oServiceContainer);
			}
		}

		return $aTaskInstances;
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

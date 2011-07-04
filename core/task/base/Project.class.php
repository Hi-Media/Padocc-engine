<?php

class Task_Base_Project extends Task_Base_Call {

	/**
	 * Nom de l'environnement (prod, QA, ...) cible.
	 * @var string
	 */
	private $sEnvName;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'project';
	}

	public function __construct ($sProjectName, $sEnvName, $sExecutionID, ServiceContainer $oServiceContainer) {
		$this->sEnvName = $sEnvName;
		$sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
		$oProject = Tasks::getProject($sProjectName);

		parent::__construct($oProject, $this, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'propertyfile' => array('srcpath'),
			'propertyshellfile' => array('srcpath'),
			'env' => array('required')
		);

		//echo 'Generate and load servers.ini...';
		//echo 'OK' . "\n";
		$this->initProperties();
	}

	protected function fetchAttributes () {
		parent::fetchAttributes();
		$this->aAttributes['env'] = $this->sEnvName;
	}

	protected function getBoundTask ($sBackupPath) {
		//$oTarget = Tasks::getTarget($this->oProject->getSXE(), $this->aAttributes['target']);
		$aTargets = $this->oProject->getSXE()->xpath("env[@name='" . $this->aAttributes['env'] . "']");
		if (count($aTargets) !== 1) {
			throw new Exception("Environment '" . $this->aAttributes['env'] . "' not found or not unique in this project!");
		}
		return new Task_Base_Environment($aTargets[0], $this->oProject, $sBackupPath, $this->oServiceContainer);
	}

	private function initProperties () {
		if ( ! empty($this->aAttributes['propertyfile'])) {
			$this->oProperties->loadConfigIniFile($this->aAttributes['propertyfile']);
		}
		if ( ! empty($this->aAttributes['propertyshellfile'])) {
			$this->oProperties->loadConfigShellFile($this->aAttributes['propertyshellfile']);
		}
	}

	public function check () {
		parent::check();
	}

	public function execute () {
		parent::execute();
	}

	public function backup () {}

	public function getSXE () {
		return $this->oTask;
	}
}

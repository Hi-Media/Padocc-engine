<?php

class Task_Base_Project extends Task {

	private $aTasks;

	private $aProperties;

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

		parent::__construct($oProject, $this, $sBackupPath);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'propertyfile' => array(),
			'propertyshellfile' => array()
		);

		//echo 'Generate and load servers.ini...';
		//echo 'OK' . "\n";
		$this->_initProperties($sProjectName);

		$oTarget = Tasks::getTarget($this->getSXE(), $sTargetName);
		$this->aTasks = Tasks::getTaskInstances($oTarget, $this, $sBackupPath);	// et non $this->sBackupPath, pour les sous-tâches
	}

	private function _initProperties ($sProjectName) {
		if ( ! empty($this->aAttributes['propertyfile'])) {
			$this->_loadPropertyFile($this->aAttributes['propertyfile']);
		} else if ( ! empty($this->aAttributes['propertyshellfile'])) {
			if ( ! file_exists($this->aAttributes['propertyshellfile'])) {
				throw new Exception("Property file '" . $this->aAttributes['propertyshellfile'] . "' not found!");
			}
			$sPropertyIniPath = DEPLOYMENT_PROJECTS_DIR . "/$sProjectName.ini";
			Shell:exec(DEPLOYMENT_INC_DIR . '/cfg2ini.inc.sh "' . $this->aAttributes['propertyshellfile'] . '" "' . $sPropertyIniPath . '"');
			$this->_loadPropertyFile($sPropertyIniPath);
		} else {
			$this->aProperties = array();
		}
	}

	private function _loadPropertyFile ($sPropertyPath) {
		if ( ! file_exists($sPropertyPath)) {
			throw new Exception("Property file '$sPropertyPath' not found!");
		}
		$this->aProperties = parse_ini_file($sPropertyPath);
		if ($this->aProperties === false) {
			throw new Exception("Load property file '$sPropertyPath' failed!");
		}
	}

	public function check () {
		parent::check();

		if ( ! empty($this->aAttributes['propertyfile']) && ! empty($this->aAttributes['propertyshellfile'])) {
			throw new Exception("Attributes 'propertyfile' and 'propertyshellfile' are exclusive!");
		}

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

	public function getSXE () {
		return $this->oTask;
	}

	public function getProperty ($sPropertyName) {
		if ( ! isset($this->aProperties[$sPropertyName])) {
			throw new Exception("Unknown property '$sPropertyName'!");
		}
		return $this->aProperties[$sPropertyName];
	}
}
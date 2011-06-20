<?php

class Task_Base_Project extends Task_Base_Call {

	private $aProperties;
	private $sTargetName;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'project';
	}

	public function __construct ($sProjectName, $sTargetName, $sExecutionID, Shell_Interface $oShell, Logger_Interface $oLogger) {
		$this->sTargetName = $sTargetName;
		$sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
		$oProject = Tasks::getProject($sProjectName);

		parent::__construct($oProject, $this, $sBackupPath, $oShell, $oLogger);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'propertyfile' => array(),
			'propertyshellfile' => array(),
			'target' => array('required')
		);

		//echo 'Generate and load servers.ini...';
		//echo 'OK' . "\n";
		$this->initProperties($sProjectName);
	}

	protected function fetchAttributes () {
		parent::fetchAttributes();
		$this->aAttributes['target'] = $this->sTargetName;
	}

	private function initProperties ($sProjectName) {
		if ( ! empty($this->aAttributes['propertyfile'])) {
			$this->loadPropertyFile($this->aAttributes['propertyfile']);
		} else if ( ! empty($this->aAttributes['propertyshellfile'])) {
			if ( ! file_exists($this->aAttributes['propertyshellfile'])) {
				throw new Exception("Property file '" . $this->aAttributes['propertyshellfile'] . "' not found!");
			}
			$sPropertyIniPath = DEPLOYMENT_RESOURCES_DIR . "/$sProjectName.ini";
			$this->oShell->exec(DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/cfg2ini.inc.sh "' . $this->aAttributes['propertyshellfile'] . '" "' . $sPropertyIniPath . '"');
			$this->loadPropertyFile($sPropertyIniPath);
		} else {
			$this->aProperties = array();
		}
	}

	private function loadPropertyFile ($sPropertyPath) {
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
	}

	public function backup () {}

	public function getSXE () {
		return $this->oTask;
	}

	public function getProperty ($sPropertyName) {
		if ( ! isset($this->aProperties[$sPropertyName])) {
			throw new DomainException("Unknown property '$sPropertyName'!");
		}
		return $this->aProperties[$sPropertyName];
	}
}

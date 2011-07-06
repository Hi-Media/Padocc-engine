<?php

class Task_Base_Cvsexport extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'cvsexport';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'repository' => array('file', 'required'),
			//'ref' => array('required'),
			'module' => array('dir', 'required'),
			'srcdir' => array('dir'),
			'destdir' => array('dir', 'required', 'allow_parameters')
		);
	}

	public function check () {
		parent::check();
		if (empty($this->aAttributes['srcdir'])) {
			$this->aAttributes['srcdir'] =
				DEPLOYMENT_REPOSITORIES_DIR . '/cvs/'
				. $this->oProperties->getProperty('project_name') . '_'
				. $this->oProperties->getProperty('environment_name');
		}
	}

	public function execute () {
		$result = $this->oShell->exec(
			DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/cvsexport.inc.sh'
			. ' "' . $this->aAttributes['repository'] . '"'
			. ' "' . $this->aAttributes['module'] . '"'
			. ' "' . $this->aAttributes['srcdir'] . '"'
		);
		$this->oLogger->log(implode("\n", $result));

		$sCVSPath = $this->aAttributes['srcdir'] . '/' . $this->aAttributes['module'];
		$results = $this->oShell->sync($sCVSPath . '/*', $this->expandPaths($this->aAttributes['destdir']));
		foreach ($results as $result) {
			$this->oLogger->log($result);
		}
	}

	public function backup () {
		/*if ($this->oShell->getFileStatus($this->aAttributes['destdir']) !== 0) {
			list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($this->aAttributes['destdir']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
				. pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
			$this->oShell->backup($this->aAttributes['destdir'], $sBackupPath);
		}*/
	}
}
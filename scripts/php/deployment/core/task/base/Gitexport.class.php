<?php

class Task_Base_Gitexport extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'gitexport';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath) {
		parent::__construct($oTask, $oProject, $sBackupPath);
		$this->aAttributeProperties = array(
			'repository' => array('file', 'required'),
			'branch' => array('dir', 'required'),
			'destdir' => array('dir', 'required')
		);
	}

	public function check () {
		parent::check();
	}

	public function execute () {
		Shell::copy($this->aAttributes['src'], $this->aAttributes['destdir']);
	}

	public function backup () {
		if (Shell::getFileStatus($this->aAttributes['destdir']) !== 0) {
			list($bIsRemote, $aMatches) = Shell::isRemotePath($this->aAttributes['destdir']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
				. pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
			Shell::backup($this->aAttributes['destdir'], $sBackupPath);
		}
	}
}
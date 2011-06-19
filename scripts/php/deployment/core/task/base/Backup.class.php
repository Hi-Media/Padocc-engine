<?php

class Task_Base_Backup extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'backup';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, IShell $oShell, ILog $oLog) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oShell, $oLog);
		$this->aAttributeProperties = array(
			'src' => array('srcpath', 'file', 'dir', 'filejoker', 'required'),
			'destfile' => array('file', 'required')
		);
	}

	protected function _check () {
		parent::check();
	}

	public function execute () {
		$this->oShell->backup($this->aAttributes['src'], $this->aAttributes['destfile']);
	}

	public function backup () {
		if ($this->oShell->getFileStatus($this->aAttributes['destfile']) !== 0) {
			list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($this->aAttributes['destfile']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/' . pathinfo($aMatches[2], PATHINFO_BASENAME);
			$this->oShell->copy($this->aAttributes['destfile'], $sBackupPath, true);
		}
	}
}
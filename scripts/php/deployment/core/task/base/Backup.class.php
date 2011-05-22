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

	public function __construct (SimpleXMLElement $oTask, $sBackupPath) {
		parent::__construct($oTask, $sBackupPath);
		$this->aAttributeProperties = array(
			'src' => array('srcpath', 'file', 'dir', 'filejoker'),
			'destfile' => array('file'),
		);
	}

	protected function _check () {
		if (empty($this->aAttributes['src']) || empty($this->aAttributes['destfile'])) {
			throw new Exception("Must define both 'src' and 'destfile' attributes!");
		}
	}

	public function execute () {
		Shell::backup($this->aAttributes['src'], $this->aAttributes['destfile']);
	}

	public function backup () {
		if (Shell::getFileStatus($this->aAttributes['destfile']) !== 0) {
			list($bIsRemote, $aMatches) = Shell::isRemotePath($this->aAttributes['destfile']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/' . pathinfo($aMatches[2], PATHINFO_BASENAME);
			Shell::copy($this->aAttributes['destfile'], $sBackupPath, true);
		}
	}
}
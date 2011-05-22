<?php

class Task_Base_Sync extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'sync';
	}

	public function __construct (SimpleXMLElement $oTask, SimpleXMLElement $oProject, $sBackupPath) {
		parent::__construct($oTask, $oProject, $sBackupPath);
		$this->aAttributeProperties = array(
			'src' => array('srcpath', 'file', 'dir', 'filejoker', 'required'),
			'destdir' => array('dir', 'required')
		);
	}

	public function check () {
		parent::check();
		if (preg_match('#\*|\?#', $this->aAttributes['src']) === 0) {
			if (Shell::getFileStatus($this->aAttributes['src']) === 2) {
				$this->aAttributes['destdir'] .= '/' . substr(strrchr($this->aAttributes['src'], '/'), 1);
				$this->aAttributes['src'] .= '/*';
			}
		}
	}

	public function execute () {
		Shell::sync($this->aAttributes['src'], $this->aAttributes['destdir']);
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
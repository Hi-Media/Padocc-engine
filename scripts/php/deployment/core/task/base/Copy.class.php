<?php

class Task_Base_Copy extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'copy';
	}

	public function __construct (SimpleXMLElement $oTask, $sBackupPath) {
		parent::__construct($oTask, $sBackupPath);
		$this->aAttributeProperties = array(
			'src' => array('srcpath', 'file', 'dir', 'filejoker'),
			'destdir' => array('dir'),
		);
	}

	// TODO si *|? alors s'assurer qu'il en existe ?
	// TODO droit seulement à \w et / et ' ' ?
	public function check () {
		parent::check();

		if (empty($this->aAttributes['src']) || empty($this->aAttributes['destdir'])) {
			throw new Exception("Must define both 'src' and 'destdir' attributes!");
		}

		if (preg_match('#\*|\?#', $this->aAttributes['src']) === 0) {
			if (Shell::getFileStatus($this->aAttributes['src']) === 2) {
				$this->aAttributes['destdir'] .= '/' . substr(strrchr($this->aAttributes['src'], '/'), 1);
				$this->aAttributes['src'] .= '/*';
			}
		}
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
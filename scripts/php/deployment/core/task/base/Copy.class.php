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

	public function __construct (SimpleXMLElement $oTask, $sBackupDir) {
		parent::__construct($oTask, $sBackupDir);
	}

	protected function _check () {
		$aAvailablesAttributes = array('src', 'dest');
		$aUnknownAttributes = array_diff(array_keys($this->aAttributes), $aAvailablesAttributes);
		if (count($aUnknownAttributes) > 0) {
			throw new Exception(
				"Available attributes: " . print_r($aAvailablesAttributes, true)
				. " => Unknown attribute(s): " . print_r($aUnknownAttributes, true)
			);
		}

		if (empty($this->aAttributes['src']) || empty($this->aAttributes['dest'])) {
			throw new Exception("Must define both src and dest attributes!");
		}

		if (Shell::getFileStatus($this->aAttributes['src']) === 0) {
			throw new Exception("File '" . $this->aAttributes['src'] . "' not found!");
		}
	}

	public function execute () {
		$iFileStatus = Shell::getFileStatus($this->aAttributes['dest']);
		if ($iFileStatus > 0) {
			list($bIsRemote, $aMatches) = Shell::isRemotePath($this->aAttributes['dest']);
			$sBackupDir = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupDir;
			Shell::mkdir($sBackupDir);
			Shell::copy($this->aAttributes['dest'], $sBackupDir . '/' . pathinfo($this->aAttributes['dest'], PATHINFO_BASENAME));
		}
		Shell::copy($this->aAttributes['src'], $this->aAttributes['dest']);
	}
}
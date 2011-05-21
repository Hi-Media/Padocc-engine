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
	}

	protected function _check () {
		$aAvailablesAttributes = array('src', 'destfile');
		$aUnknownAttributes = array_diff(array_keys($this->aAttributes), $aAvailablesAttributes);
		if (count($aUnknownAttributes) > 0) {
			throw new Exception("Available attributes: " . print_r($aAvailablesAttributes, true) . " => Unknown attribute(s): " . print_r($aUnknownAttributes, true));
		}

		if (empty($this->aAttributes['src']) || empty($this->aAttributes['destfile'])) {
			throw new Exception("Must define both 'src' and 'destfile' attributes!");
		}

		if (preg_match('#[*?].*/#', $this->aAttributes['src']) !== 0) {
			throw new Exception("'*' and '?' are only authorized for filename in 'src' attribute!");
		}

		if (preg_match('/[*?]/', $this->aAttributes['destfile']) !== 0) {
			throw new Exception("'*' and '?' are not authorized in 'destfile' attribute!");
		}

		$this->aAttributes['src'] = preg_replace('#/$#', '', $this->aAttributes['src']);
		$this->aAttributes['destfile'] = preg_replace('#/$#', '', $this->aAttributes['destfile']);

		if (preg_match('#\*|\?#', $this->aAttributes['src']) === 0 && Shell::getFileStatus($this->aAttributes['src']) === 0) {
			throw new Exception("File '" . $this->aAttributes['src'] . "' not found!");
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
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
	}

	protected function _check () {
		$aAvailablesAttributes = array('src', 'dest');
		$aUnknownAttributes = array_diff(array_keys($this->aAttributes), $aAvailablesAttributes);
		if (count($aUnknownAttributes) > 0) {
			throw new Exception("Available attributes: " . print_r($aAvailablesAttributes, true) . " => Unknown attribute(s): " . print_r($aUnknownAttributes, true));
		}

		if (empty($this->aAttributes['src']) || empty($this->aAttributes['dest'])) {
			throw new Exception("Must define both 'src' and 'dest' attributes!");
		}

		if (preg_match('/[*?]/', $this->aAttributes['src'] . $this->aAttributes['dest']) !== 0) {
			throw new Exception("'*' and '?' are not authorized in 'src' or 'dest' attribute!");
		}

		$this->aAttributes['src'] = preg_replace('#/$#', '', $this->aAttributes['src']);
		$this->aAttributes['dest'] = preg_replace('#/$#', '', $this->aAttributes['dest']);

		if (Shell::getFileStatus($this->aAttributes['src']) === 0) {
			throw new Exception("File '" . $this->aAttributes['src'] . "' not found!");
		}
	}

	public function execute () {
		$this->_backup();
		Shell::copy($this->aAttributes['src'], $this->aAttributes['dest']);
	}

	// TODO tar/gz ?
	protected function _backup () {
		if (Shell::getFileStatus($this->aAttributes['dest']) !== 0) {
			list($bIsRemote, $aMatches) = Shell::isRemotePath($this->aAttributes['dest']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath;
			//Shell::copy($this->aAttributes['dest'], $sBackupPath . '/' . pathinfo($this->aAttributes['dest'], PATHINFO_BASENAME));
			Shell::backup($this->aAttributes['dest'], $sBackupPath);
		}
	}
}
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

	public function __construct (SimpleXMLElement $oTask, $sBackupPath) {
		parent::__construct($oTask, $sBackupPath);
	}

	protected function _check () {
		$aAvailablesAttributes = array('src', 'destdir');
		$aUnknownAttributes = array_diff(array_keys($this->aAttributes), $aAvailablesAttributes);
		if (count($aUnknownAttributes) > 0) {
			throw new Exception("Available attributes: " . print_r($aAvailablesAttributes, true) . " => Unknown attribute(s): " . print_r($aUnknownAttributes, true));
		}

		if (empty($this->aAttributes['src']) || empty($this->aAttributes['destdir'])) {
			throw new Exception("Must define both 'src' and 'destdir' attributes!");
		}

		if (preg_match('#[*?].*/#', $this->aAttributes['src']) !== 0) {
			throw new Exception("'*' and '?' are only authorized for filename in 'src' attribute!");
		}
		if (preg_match('#[*?].*/#', $this->aAttributes['destdir']) !== 0) {
			throw new Exception("'*' and '?' are only authorized for filename in 'destdir' attribute!");
		}

		$this->aAttributes['src'] = preg_replace('#/$#', '', $this->aAttributes['src']);
		$this->aAttributes['destdir'] = preg_replace('#/$#', '', $this->aAttributes['destdir']);

		if (preg_match('#\*|\?#', $this->aAttributes['src']) === 0) {
			$iSrcFileStatus = Shell::getFileStatus($this->aAttributes['src']);
			if ($iSrcFileStatus === 0) {
				throw new Exception("File '" . $this->aAttributes['src'] . "' not found!");
			} else if ($iSrcFileStatus === 2) {
				$this->aAttributes['destdir'] .= '/' . substr(strrchr($this->aAttributes['src'], '/'), 1);
				$this->aAttributes['src'] .= '/*';
			} else {
				//$this->aAttributes['dest'] .= '/' . substr(strrchr($this->aAttributes['src'], '/'), 1);
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
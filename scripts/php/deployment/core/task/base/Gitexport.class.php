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
			'ref' => array('required'),
			'srcdir' => array('dir', 'required'),
			'destdir' => array('dir', 'required')
		);
	}

	public function check () {
		parent::check();
		/*if ( ! empty($this->aAttributes['branch']) XOR ! empty($this->aAttributes['tag'])) {
			throw new Exception("Attributes 'branch' and 'tag' are exclusive and one of them must be filled!");
		}*/
	}

	public function execute () {
		$result = Shell::exec(
			DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_INC_DIR . '/gitexport.inc.sh'
			. ' "' . $this->aAttributes['repository'] . '"'
			. ' "' . $this->aAttributes['ref'] . '"'
			. ' "' . $this->aAttributes['srcdir'] . '"'
			. ' "' . $this->aAttributes['destdir'] . '"'
		);
		var_dump(implode("\n", $result));
		$result = Shell::sync($this->aAttributes['srcdir'] . '/*', $this->aAttributes['destdir']);
		var_dump(implode("\n", $result));
	}

	public function backup () {
		/*if (Shell::getFileStatus($this->aAttributes['destdir']) !== 0) {
			list($bIsRemote, $aMatches) = Shell::isRemotePath($this->aAttributes['destdir']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
				. pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
			Shell::backup($this->aAttributes['destdir'], $sBackupPath);
		}*/
	}
}
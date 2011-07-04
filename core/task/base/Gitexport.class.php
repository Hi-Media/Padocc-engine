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

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'repository' => array('file', 'required'),
			'ref' => array('required'),
			'srcdir' => array('dir', 'required'),
			'destdir' => array('dir', 'required', 'allow_parameters')
		);
	}

	public function check () {
		parent::check();
		/*if ( ! empty($this->aAttributes['branch']) XOR ! empty($this->aAttributes['tag'])) {
			throw new Exception("Attributes 'branch' and 'tag' are exclusive and one of them must be filled!");
		}*/
	}

	public function execute () {
		$result = $this->oShell->exec(
			DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/gitexport.inc.sh'
			. ' "' . $this->aAttributes['repository'] . '"'
			. ' "' . $this->aAttributes['ref'] . '"'
			. ' "' . $this->aAttributes['srcdir'] . '"'
		);
		$this->oLogger->log(implode("\n", $result));

		$result = $this->oShell->sync($this->aAttributes['srcdir'] . '/*', $this->expandPaths($this->aAttributes['destdir']));
		$this->oLogger->log(implode("\n", $result));
	}

	public function backup () {
		/*if ($this->oShell->getFileStatus($this->aAttributes['destdir']) !== 0) {
			list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($this->aAttributes['destdir']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
				. pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
			$this->oShell->backup($this->aAttributes['destdir'], $sBackupPath);
		}*/
	}
}
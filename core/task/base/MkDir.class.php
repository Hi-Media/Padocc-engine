<?php

class Task_Base_MkDir extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'mkdir';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'destdir' => array('dir', 'required', 'allow_parameters'),
			'mode' => array(),
		);
	}

	public function check () {
		parent::check();
	}

	public function execute () {
		parent::execute();
		$sMode = (empty($this->aAttributes['mode']) ? '' : $this->aAttributes['mode']);

		$aDestDirs = $this->_expandPaths($this->aAttributes['destdir']);
		foreach ($aDestDirs as $sDestDir) {
			$this->oShell->mkdir($sDestDir, $sMode);
		}
	}

	public function backup () {
	}
}
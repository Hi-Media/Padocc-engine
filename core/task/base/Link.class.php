<?php

class Task_Base_Link extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'link';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'src' => array('required', 'file', 'dir'),
			'target' => array('file', 'dir', 'required'),
			'server' => array('allow_parameters'),
		);
	}

	public function check () {
		parent::check();

		list($bIsSrcRemote, $aSrcMatches) = $this->oShell->isRemotePath($this->aAttributes['src']);
		list($bIsDestRemote, $aDestMatches) = $this->oShell->isRemotePath($this->aAttributes['target']);
		if ($bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] != $aDestMatches[1]) {
			throw new DomainException('Servers must be equals!'
				. ' Src=' . $this->aAttributes['src'] . ' Target=' . $this->aAttributes['target']);
		}

		if ( ! empty($this->aAttributes['server']) && ($bIsSrcRemote || $bIsDestRemote)) {
			throw new DomainException('Multiple server declaration!' . ' Server=' . $this->aAttributes['server']
				. ' Src=' . $this->aAttributes['src'] . ' Target=' . $this->aAttributes['target']);
		}
	}

	public function execute () {
		if ( ! empty($this->aAttributes['server'])) {
			$aTargetPaths = $this->expandPaths($this->aAttributes['server'] . ':' . $this->aAttributes['target']);
			foreach ($aTargetPaths as $sTargetPath) {
				list(, $aDestMatches) = $this->oShell->isRemotePath($sTargetPath);
				$sSrc = $aDestMatches[1] . ':' . $this->aAttributes['src'];
				$this->oShell->createLink($sSrc, $sTargetPath);
			}
		} else {
			$this->oShell->createLink($this->aAttributes['src'], $this->aAttributes['target']);
		}
	}

	public function backup () {
	}
}
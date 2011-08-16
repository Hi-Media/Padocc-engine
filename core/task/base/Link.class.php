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

	/**
	 * Constructeur.
	 *
	 * @param SimpleXMLElement $oTask Contenu XML de la tâche.
	 * @param Task_Base_Project $oProject Super tâche projet.
	 * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
	 * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
	 */
	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'src' => Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_DIR,
			'target' => Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED,
			'server' => Task::ATTRIBUTE_ALLOW_PARAMETER
		);
	}

	/**
	 * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
	 * Lance une exception si tel n'est pas le cas.
	 *
	 * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
	 * doit permettre de remonter au plus tôt tout dysfonctionnement.
	 * Appelé avant la méthode execute().
	 *
	 * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
	 * @throws DomainException en cas de valeur non permise
	 */
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
		parent::execute();
		$this->oLogger->indent();
		if ( ! empty($this->aAttributes['server'])) {
			$aTargetPaths = $this->_processPath($this->aAttributes['server'] . ':' . $this->aAttributes['target']);
			foreach ($aTargetPaths as $sTargetPath) {
				list(, $aDestMatches) = $this->oShell->isRemotePath($sTargetPath);
				$sSrc = $this->_processSimplePath($aDestMatches[1] . ':' . $this->aAttributes['src']);
				$this->oShell->createLink($sSrc, $sTargetPath);
			}
		} else {
			$this->oShell->createLink($this->aAttributes['src'], $this->aAttributes['target']);
		}
		$this->oLogger->unindent();
	}

	public function backup () {
	}
}
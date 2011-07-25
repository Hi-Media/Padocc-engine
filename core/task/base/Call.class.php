<?php

class Task_Base_Call extends Task {

	/**
	 * Tâche appelée.
	 * @var Task
	 */
	protected $oBoundTask;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'call';
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
			'target' => array('required')
		);
		$this->initProperties();
		$this->oNumbering->addCounterDivision();
		$this->oBoundTask = $this->getBoundTask($sBackupPath);
		$this->oNumbering->removeCounterDivision();
	}

	protected function initProperties () {
		if ( ! empty($this->aAttributes['propertyshellfile'])) {
			$this->oProperties->loadConfigShellFile($this->aAttributes['propertyshellfile']);
		}
		if ( ! empty($this->aAttributes['propertyinifile'])) {
			$this->oProperties->loadConfigIniFile($this->aAttributes['propertyinifile']);
		}
	}

	protected function getBoundTask ($sBackupPath) {
		$aTargets = $this->oProject->getSXE()->xpath("target[@name='" . $this->aAttributes['target'] . "']");
		if (count($aTargets) !== 1) {
			throw new Exception("Target '" . $this->aAttributes['target'] . "' not found or not unique in this project!");
		}
		return new Task_Base_Target($aTargets[0], $this->oProject, $sBackupPath, $this->oServiceContainer);
	}

	/**
	 * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
	 * Lance une exception si tel n'est pas le cas.
	 *
	 * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
	 * doit permettre de remonter au plus tôt tout dysfonctionnement.
	 * Appelé avant la méthode execute().
	 *
	 * @throws UnexpectedValueException
	 * @throws DomainException
	 * @throws RuntimeException
	 */
	public function check () {
		parent::check();
		$this->oBoundTask->check();
	}

	public function execute () {
		parent::execute();

		$this->oLogger->indent();
		$this->oLogger->unindent();

		$this->oBoundTask->backup();
		$this->oBoundTask->execute();
	}

	public function backup () {}
}

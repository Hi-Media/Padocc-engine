<?php

class Task_Base_Call extends Task_WithProperties {

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
		$this->aAttributeProperties = array_merge($this->aAttributeProperties, array(
			'target' => array(Task::ATTRIBUTE_REQUIRED)
		));
		$this->oBoundTask = $this->getBoundTask($sBackupPath);
	}

	/**
	 * Retourne une instance de la tâche target appelée.
	 *
	 * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
	 * @return Task_Base_Target instance de la tâche target appelée.
	 * @throws UnexpectedValueException si cible non trouvée ou non unique.
	 */
	protected function getBoundTask ($sBackupPath) {
		$aTargets = $this->oProject->getSXE()->xpath("target[@name='" . $this->aAttributes['target'] . "']");
		if (count($aTargets) !== 1) {
			throw new UnexpectedValueException("Target '" . $this->aAttributes['target'] . "' not found or not unique in this project!");
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
	 * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
	 * @throws DomainException en cas de valeur non permise
	 */
	public function check () {
		parent::check();
		$this->oBoundTask->check();
	}

	public function execute () {
		parent::execute();
		$this->oBoundTask->backup();
		$this->oBoundTask->execute();
	}

	public function backup () {}
}

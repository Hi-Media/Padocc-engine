<?php

class Task_Base_Project extends Task_Base_Call {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'project';
	}

	private $sEnvName;

	/**
	 * Constructeur.
	 *
	 * @param string $sProjectName Nom du projet.
	 * @param string $sEnvName Environnement sélectionné.
	 * @param string $sExecutionID Identifiant d'exécution.
	 * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
	 */
	public function __construct ($sProjectName, $sEnvName, $sExecutionID, ServiceContainer $oServiceContainer) {
		$sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
		$oProject = Tasks::getProject($sProjectName);
		$this->sEnvName = $sEnvName;

		parent::__construct($oProject, $this, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array_merge($this->aAttributeProperties, array(
			'name' => array(Task::ATTRIBUTE_REQUIRED),
		));
		unset($this->aAttributeProperties['target']);
	}

	/**
	 * Retourne une instance de la tâche environnement appelée.
	 *
	 * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
	 * @return Task_Base_Environment instance de la tâche environnement appelée.
	 * @throws UnexpectedValueException si cible non trouvée ou non unique.
	 */
	protected function getBoundTask ($sBackupPath) {
		$sEnvName = $this->sEnvName;
		$aTargets = $this->oProject->getSXE()->xpath("env[@name='$sEnvName']");
		if (count($aTargets) !== 1) {
			throw new UnexpectedValueException("Environment '$sEnvName' not found or not unique in this project!");
		}
		return new Task_Base_Environment($aTargets[0], $this->oProject, $sBackupPath, $this->oServiceContainer);
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
	}

	public function execute () {
		parent::execute();
	}

	public function backup () {}

	public function getSXE () {
		return $this->oTask;
	}
}

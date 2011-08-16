<?php

class Task_Base_Environment extends Task_Base_Target {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'env';
	}

	/**
	 * Tâche de switch de symlink sous-jacente.
	 * @var Task_Base_Link
	 */
	//private $oLinkTask;

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
			'name' => Task::ATTRIBUTE_REQUIRED,
			'mailto' => 0,
			'withsymlink' => 0
		));

		// Création de switch de symlink sous-jacente :
		/*if ( ! empty($this->aAttributes['withsymlink'])) {
			$this->oNumbering->addCounterDivision();
			$sSrcDir = preg_replace('#/$#', '', $this->aAttributes['srcdir']) . '/*';
			$this->oCopyTask = Task_Base_Copy::getNewInstance(array(
				'src' => $sSrcDir,
				'destdir' => $this->aAttributes['destdir']
			), $oProject, $sBackupPath, $oServiceContainer);
			$this->oNumbering->removeCounterDivision();
		}*/
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
		if ( ! empty($this->aAttributes['withsymlink'])) {
			$this->oProperties->addProperty('symlink', $this->aAttributes['withsymlink']);
		}
	}
}

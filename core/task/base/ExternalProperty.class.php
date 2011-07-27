<?php

class Task_Base_ExternalProperty extends Task {

	const sExternalPropertyPrefix = 'external_property_';
	private static $iCounter = 0;

	private $iNumber;

	protected $aTasks;

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'externalproperty';
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
			'name' => array('required'),
			'description' => array('required'),
		);
		$this->iNumber = ++self::$iCounter;
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
		$this->oLogger->indent();
		$this->oLogger->log("Set external property '" . $this->aAttributes['name'] . "' (description: '" . $this->aAttributes['description'] . "')");
		try {
			$sValue = $this->oProperties->getProperty(self::sExternalPropertyPrefix . $this->iNumber);
		} catch (UnexpectedValueException $e) {
			throw new UnexpectedValueException("Property '" . $this->aAttributes['name'] . "' undefined!");
		}
		$this->oProperties->addProperty($this->aAttributes['name'], $sValue);
		$this->oLogger->unindent();
	}

	public function backup () {}
}

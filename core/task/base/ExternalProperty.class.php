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

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'description' => array('required'),
		);
		$this->iNumber = ++self::$iCounter;
	}

	public function check () {
		parent::check();
	}

	public function execute () {
		parent::execute();
		try {
			$sValue = $this->oProperties->getProperty(self::sExternalPropertyPrefix . $this->iNumber);
		} catch (DomainException $e) {
			throw new DomainException('Property "' . $this->aAttributes['name'] . '" undefined!');
		}
		$this->oProperties->addProperty($this->aAttributes['name'], $sValue);
	}

	public function backup () {}
}

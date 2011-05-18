<?php

abstract class Task {

	public static function getTagName () {
		throw new RuntimeException('Unimplemented!');
	}

	/**
	 * Contenu XML de la tâche.
	 * @var SimpleXMLElement
	 */
	private $oTask;

	/**
	 * Attributs XML de la tâche.
	 * @var array
	 */
	protected $aAttributes;

	public function __construct (SimpleXMLElement $oTask) {
		$this->oTask = $oTask;

		$this->aAttributes = array();
		foreach ($this->oTask->attributes() as $key => $val) {
			$this->aAttributes[$key] = (string)$val;
		}
		print_r($this->aAttributes);

		$aAvailableAttributes = array_flip($this->getAvailableAttributes());
		foreach ($this->aAttributes as $sAttribute => $foo) {
			if ( ! isset($aAvailableAttributes[$sAttribute])) {
				throw new Exception("Unknown '$sAttribute' attribute! XML: " . print_r($this->oTask, true));
			}
		}

		foreach ($this->getMandatoryAttributes() as $sAttribute) {
			if (empty($this->aAttributes[$sAttribute])) {
				throw new Exception("Empty or missing '$sAttribute' attribute! XML: " . print_r($this->oTask, true));
			}
		}
	}

	protected abstract function getAvailableAttributes();
	protected abstract function getMandatoryAttributes();

	public abstract function execute ();
}
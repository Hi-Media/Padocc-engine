<?php

class Task_Base_Sync extends Task {

	public static function getTagName () {
		return 'sync';
	}

	public function __construct (SimpleXMLElement $oTask) {
		parent::__construct($oTask);
	}

	protected function getAvailableAttributes() {
		return array('src', 'dest');
	}

	protected function getMandatoryAttributes () {
		return array();
	}

	public function execute () {}
}
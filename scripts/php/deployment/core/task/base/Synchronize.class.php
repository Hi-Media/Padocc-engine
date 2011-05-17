<?php

class Task_Base_Synchronize extends Task {

	public static function getTagName () {
		return 'synchronize';
	}

	public function __construct (SimpleXMLElement $oTask) {
		parent::__construct($oTask);
	}
}
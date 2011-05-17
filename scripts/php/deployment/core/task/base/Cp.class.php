<?php

class Task_Base_Cp extends Task {

	public static function getTagName () {
		return 'cp';
	}

	public function __construct (SimpleXMLElement $oTask) {
		parent::__construct($oTask);
	}
}
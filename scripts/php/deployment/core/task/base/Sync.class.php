<?php

class Task_Base_Sync extends Task {

	public static function getTagName () {
		return 'sync';
	}

	public function __construct (SimpleXMLElement $oTask, $sBackupDir) {
		parent::__construct($oTask, $sBackupDir);
	}

	protected function _check () {

	}

	public function execute () {}
}
<?php

class Task_Base_Copy extends Task {

	public static function getTagName () {
		return 'copy';
	}

	public function __construct (SimpleXMLElement $oTask) {
		parent::__construct($oTask);
	}

	protected function getAvailableAttributes () {
		return array('src', 'dest');
	}

	protected function getMandatoryAttributes () {
		return array('src', 'dest');
	}

	public function execute () {
		if ( ! file_exists($this->aAttributes['src'])) {
			throw new Exception("File '" . $this->aAttributes['src'] . "' not found!");
		}
		if (file_exists($this->aAttributes['dest'])) {

		}
		Shell::exec("cp -f ");
	}
}
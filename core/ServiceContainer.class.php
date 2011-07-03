<?php

class ServiceContainer {

	/**
	 * Logger.
	 * @var Logger_Interface
	 */
	private $oLogger;

	/**
	 * Properties.
	 * @var Properties_Interface
	 */
	private $oProperties;

	/**
	 * Adaptateur Shell.
	 * @var Shell_Interface
	 */
	private $oShell;

	public function __construct () {
		$this->oLogger = NULL;
		$this->oProperties = NULL;
		$this->oShell = NULL;
	}

	public function setLogAdapter (Logger_Interface $oLogger) {
		$this->oLogger = $oLogger;
		return $this;
	}

	public function setPropertiesAdapter (Properties_Interface $oProperties) {
		$this->oProperties = $oProperties;
		return $this;
	}

	public function setShellAdapter (Shell_Interface $oShell) {
		$this->oShell = $oShell;
		return $this;
	}

	public function getLogAdapter () {
		return $this->oLogger;
	}

	public function getPropertiesAdapter () {
		return $this->oProperties;
	}

	public function getShellAdapter () {
		return $this->oShell;
	}
}

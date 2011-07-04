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

	/**
	 * Adaptateur de numérotation.
	 * @var Numbering_Interface
	 */
	private $oNumbering;

	/**
	 * Adaptateur d'envoi mail.
	 * @var Mail_Interface
	 */
	private $oMail;

	public function __construct () {
		$this->oLogger = NULL;
		$this->oProperties = NULL;
		$this->oShell = NULL;
		$this->oMail = NULL;
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

	public function setNumberingAdapter (Numbering_Interface $oNumbering) {
		$this->oNumbering = $oNumbering;
		return $this;
	}

	public function setMailAdapter (Mail_Interface $oMail) {
		$this->oMail = $oMail;
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

	public function getNumberingAdapter () {
		return $this->oNumbering;
	}

	public function getMailAdapter () {
		return $this->oMail;
	}
}

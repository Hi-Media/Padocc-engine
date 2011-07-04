<?php

class Properties_Adapter implements Properties_Interface {

	/**
	 * @var array
	 */
	private $aProperties;

	/**
	 * Shell adapter.
	 * @var Shell_Interface
	 */
	private $oShell;

	public function __construct (Shell_Interface $oShell) {
		$this->aProperties = array();
		$this->oShell = $oShell;
	}

	public function getProperty ($sPropertyName) {
		if ( ! isset($this->aProperties[$sPropertyName])) {
			throw new DomainException("Unknown property '$sPropertyName'!");
		}
		return $this->aProperties[$sPropertyName];
	}

	public function loadConfigIniFile ($sIniPath) {
		if ( ! file_exists($sIniPath)) {
			throw new Exception("Property file '$sIniPath' not found!");
		}

		$aProperties = parse_ini_file($sIniPath);
		if ($aProperties === false) {
			throw new Exception("Load property file '$sIniPath' failed!");
		}

		$this->aProperties = array_merge($this->aProperties, $aProperties);
		return $this;
	}

	public function loadConfigShellFile ($sConfigShellPath) {
		if ( ! file_exists($sConfigShellPath)) {
			throw new Exception("Property file '$sConfigShellPath' not found!");
		}
		$sConfigIniPath = DEPLOYMENT_RESOURCES_DIR . strrchr($sConfigShellPath, '/') . '.ini';
		$this->oShell->exec(DEPLOYMENT_BASH_PATH . ' ' . __DIR__ . "/cfg2ini.inc.sh '$sConfigShellPath' '$sConfigIniPath'");
		return $this->loadConfigIniFile($sConfigIniPath);
	}
}

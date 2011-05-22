<?php

class Deployment {

	public function __construct ($sProjectName, $sTargetName, $sExecutionID) {
		echo 'Generate and load servers.ini...';
		$servers = $this->_loadServersIniFile(DEPLOYMENT_CONF_DIR . "/master_synchro.cfg");
		echo 'OK' . "\n";
		echo 'Initialize tasks...';
		$oProject = new Task_Base_Project($sProjectName, $sTargetName, $sExecutionID);
		echo 'OK' . "\n";
		echo 'Check tasks...';
		$oProject->check();
		echo 'OK' . "\n";
		echo 'Execute tasks...';
		$oProject->execute();
		echo 'OK' . "\n";
	}

	private function _loadServersIniFile ($sMasterSynchroPath) {
		$sServersIniPath = DEPLOYMENT_CONF_DIR . "/servers.ini";
		Shell:exec("~/deployment/scripts/php/deployment/inc/cfg2ini.inc.sh $sMasterSynchroPath $sServersIniPath");
		$servers = parse_ini_file($sServersIniPath);
		return $servers;
	}
}

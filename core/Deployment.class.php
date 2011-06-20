<?php

class Deployment {

	public function __construct ($sProjectName, $sEnvName, $sExecutionID) {
		$oLogger = new Logger_Adapter(DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
		$oShell = new Shell_Adapter($oLogger);
		$oLogger->log('Initialize tasks...');
		$oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $oShell, $oLogger);
		$oLogger->log('OK' . "\n");
		$oLogger->log('Check tasks...');
		$oProject->check();
		$oLogger->log('OK' . "\n");
		$oLogger->log('Execute tasks...');
		$oProject->execute();
		$oLogger->log('OK' . "\n");
	}
}

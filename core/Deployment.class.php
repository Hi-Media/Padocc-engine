<?php

class Deployment {

	public function __construct ($sProjectName, $sEnvName, $sExecutionID) {
		$oLogger = new Logger_Adapter(DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
		$oShell = new Shell_Adapter($oLogger);
		$oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $oShell, $oLogger);
		$oLogger->log('Check tasks...' . "\n");
		$oProject->check();
		$oLogger->log('Execute tasks...' . "\n");
		$oProject->execute();
	}
}

<?php

class Deployment {

	public function __construct ($sProjectName, $sTargetName, $sExecutionID) {
		echo 'Initialize tasks...';
		$oProject = new Task_Base_Project($sProjectName, $sTargetName, $sExecutionID);
		echo 'OK' . "\n";
		echo 'Check tasks...';
		$oProject->check();
		echo 'OK' . "\n";
		echo 'Execute tasks...';
		//$oProject->execute();
		echo 'OK' . "\n";
	}
}

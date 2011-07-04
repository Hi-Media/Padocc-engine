<?php

class Deployment {

	private $oLogger;
	private $oServiceContainer;

	public function __construct () {
		$this->oLogger = new Logger_Adapter(DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
		$oShell = new Shell_Adapter($this->oLogger);

		$this->oServiceContainer = new ServiceContainer();
		$this->oServiceContainer->setLogAdapter($this->oLogger);
		$this->oServiceContainer->setShellAdapter($oShell);
	}

	public function run ($sProjectName, $sEnvName, $sExecutionID) {
		$oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $this->oServiceContainer);
		$this->oLogger->log('Check tasks...');
		$oProject->check();
		$this->oLogger->log('Execute tasks...');
		$oProject->execute();
	}

	public function getProjectsEnvsList () {
		$aAvailableTargetsByProject = array();
		$aAllProjectsName = Tasks::getAllProjectsName();
		/*
		if(empty($aAllProjectsName))
			throw new RuntimeException('No project found', 1);
		*/
		if(!empty($aAllProjectsName)) {
			foreach($aAllProjectsName as $sProjectName) {
				$aAvailableTargetsByProject[$sProjectName] = Tasks::getAvailableTargetsList($sProjectName);
			}
		}

		return $aAvailableTargetsByProject;
	}

	public function getProjectConfig($sProjectName)
	{

	}
}


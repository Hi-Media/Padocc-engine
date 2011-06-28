<?php

class Deployment {

	private $oLogger;

	public function __construct () {
		$this->oLogger = new Logger_Adapter(DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);

	}

	public function run($sProjectName, $sEnvName, $sExecutionID)
	{
		$oShell = new Shell_Adapter($this->oLogger);
		$oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $oShell, $this->oLogger);
		$this->oLogger->log('Check tasks...');
		$oProject->check();
		$this->oLogger->log('Execute tasks...');
		$oProject->execute();
	}

	public function getProjectsEnvsList()
	{
		$aAvailableTargetsByProject = array();
		$aAllProjectsName = Tasks::getAllProjectsName();
		/*
		if(empty($aAllProjectsName))
			throw new RuntimeException('No project found', 1);
		*/
		if(!empty($aAllProjectsName))
		{
			foreach($aAllProjectsName as $sProjectName)
			{
				$aAvailableTargetsByProject[$sProjectName] = Tasks::getAvailableTargetsList($sProjectName);
			}
		}

		return $aAvailableTargetsByProject;
	}

	public function getProjectConfig($sProjectName)
	{

	}
}


<?php

class Deployment {

	private $oLogger;
	private $oServiceContainer;

	public function __construct () {
		$this->oLogger = new Logger_Adapter(DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
		$oShell = new Shell_Adapter($this->oLogger);

		$this->oServiceContainer = new ServiceContainer();
		$this->oServiceContainer
			->setLogAdapter($this->oLogger)
			->setShellAdapter($oShell)
			->setPropertiesAdapter(new Properties_Adapter($oShell))
			->setNumberingAdapter(new Numbering_Adapter())
			->setMailAdapter(new Mail_Adapter());
	}

	public function run ($sProjectName, $sEnvName, $sExecutionID) {
		$oProperties = $this->oServiceContainer->getPropertiesAdapter();
		$oProperties->addProperty('project_name', $sProjectName);
		$oProperties->addProperty('environment_name', $sEnvName);
		$oProperties->addProperty('execution_id', $sExecutionID);

		$oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $this->oServiceContainer);
		$this->oLogger->log('Check tasks...');
		$oProject->check();
		$this->oLogger->log('Execute tasks...');
		$oProject->execute();
	}

	public function getProjectsEnvsList () {
		$aAllProjectsName = Tasks::getAllProjectsName();
		$aAvailableTargetsByProject = array();
		if ( ! empty($aAllProjectsName)) {
			foreach ($aAllProjectsName as $sProjectName) {
				$aAvailableTargetsByProject[$sProjectName] = Tasks::getAvailableTargetsList($sProjectName);
			}
		}
		return $aAvailableTargetsByProject;
	}

	public function getProjectConfig($sProjectName)
	{

	}
}


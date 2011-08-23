<?php

class Deployment
{

    private $oLogger;
    private $oServiceContainer;

    public function __construct ()
    {
        $iDebugMode = (DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
        $oBaseLogger = new Logger_Adapter($iDebugMode);
        $this->oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($this->oLogger);

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($this->oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new Properties_Adapter($oShell))
            ->setNumberingAdapter(new Numbering_Adapter());
    }

    private function _setExternalProperties (array $aExternalProperties=array()) {
        $oProperties = $this->oServiceContainer->getPropertiesAdapter();
        foreach ($aExternalProperties as $i => $sValue) {
            $sKey = Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
            $oProperties->setProperty($sKey, str_replace('&#0160;', ' ', $sValue));
        }
    }

    public function run ($sProjectName, $sEnvName, $sExecutionID, array $aExternalProperties=array())
    {
        $oProperties = $this->oServiceContainer->getPropertiesAdapter();
        $oProperties->setProperty('project_name', $sProjectName);
        $oProperties->setProperty('environment_name', $sEnvName);
        $oProperties->setProperty('execution_id', $sExecutionID);

        $this->_setExternalProperties($aExternalProperties);

        $oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $this->oServiceContainer);
        $this->oLogger->log('Check tasks:');
        $this->oLogger->indent();
        $oProject->setUp();
        $this->oLogger->unindent();
        $this->oLogger->log('Execute tasks:');
        $this->oLogger->indent();
        $oProject->execute();
        $this->oLogger->unindent();
    }

    public function getProjectsEnvsList ()
    {
        $aAllProjectsName = Task_Base_Project::getAllProjectsName();
        $aTargetsByProject = array();
        if ( ! empty($aAllProjectsName)) {
            foreach ($aAllProjectsName as $sProjectName) {
                $aTargetsByProject[$sProjectName] = Task_Base_Target::getAvailableTargetsList($sProjectName);
            }
        }
        ksort($aTargetsByProject);
        return $aTargetsByProject;
    }
}

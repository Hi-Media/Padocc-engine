<?php

class Deployment
{

    private $oLogger;
    private $oServiceContainer;

    public function __construct ()
    {
        $oBaseLogger = new Logger_Adapter(DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
        $this->oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($this->oLogger);

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($this->oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new Properties_Adapter($oShell))
            ->setNumberingAdapter(new Numbering_Adapter());
    }

    public function run ($sProjectName, $sEnvName, $sExecutionID, array $aExternalProperties=array())
    {
        $oProperties = $this->oServiceContainer->getPropertiesAdapter();
        $oProperties->setProperty('project_name', $sProjectName);
        $oProperties->setProperty('environment_name', $sEnvName);
        $oProperties->setProperty('execution_id', $sExecutionID);

        // Gestion des propriétés externes :
        foreach ($aExternalProperties as $i => $sValue) {
            $sKey = Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
            $oProperties->setProperty($sKey, str_replace('&#0160;', ' ', $sValue));
        }

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
        $aAvailableTargetsByProject = array();
        if ( ! empty($aAllProjectsName)) {
            foreach ($aAllProjectsName as $sProjectName) {
                $aAvailableTargetsByProject[$sProjectName] = Task_Base_Target::getAvailableTargetsList($sProjectName);
            }
        }
        ksort($aAvailableTargetsByProject);
        return $aAvailableTargetsByProject;
    }
}

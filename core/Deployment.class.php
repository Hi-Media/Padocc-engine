<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Deployment
{

    private $_oLogger;
    private $_oServiceContainer;

    public function __construct ()
    {
        $iDebugMode = (DEPLOYMENT_DEBUG_MODE === 1 ? Logger_Interface::DEBUG : Logger_Interface::INFO);
        $oBaseLogger = new Logger_Adapter($iDebugMode);
        $this->_oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($this->_oLogger);

        $this->_oServiceContainer = new ServiceContainer();
        $this->_oServiceContainer
            ->setLogAdapter($this->_oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new Properties_Adapter($oShell))
            ->setNumberingAdapter(new Numbering_Adapter());
    }

    private function _setExternalProperties (array $aExternalProperties=array())
    {
        $oProperties = $this->_oServiceContainer->getPropertiesAdapter();
        foreach ($aExternalProperties as $i => $sValue) {
            $sKey = Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
            $oProperties->setProperty($sKey, str_replace('&#0160;', ' ', $sValue));
        }
    }

    public function run ($sProjectName, $sEnvName, $sExecutionID, array $aExternalProperties=array())
    {
        $oProperties = $this->_oServiceContainer->getPropertiesAdapter();
        $oProperties->setProperty('project_name', $sProjectName);
        $oProperties->setProperty('environment_name', $sEnvName);
        $oProperties->setProperty('execution_id', $sExecutionID);

        $this->_setExternalProperties($aExternalProperties);

        $oProject = new Task_Base_Project($sProjectName, $sEnvName, $sExecutionID, $this->_oServiceContainer);
        $this->_oLogger->log('Check tasks:');
        $this->_oLogger->indent();
        $oProject->setUp();
        $this->_oLogger->unindent();
        $this->_oLogger->log('Execute tasks:');
        $this->_oLogger->indent();
        $oProject->execute();
        $this->_oLogger->unindent();
    }

    public function getProjectsEnvsList ()
    {
        $aAllProjectsName = Task_Base_Project::getAllProjectsName(DEPLOYMENT_RESOURCES_DIR);
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

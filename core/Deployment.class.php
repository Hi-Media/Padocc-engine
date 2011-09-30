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
        $oProperties->setProperty('tmpdir', DEPLOYMENT_TMP_DIR . '/deploy_' . $sExecutionID);

        $this->_setExternalProperties($aExternalProperties);

        $sProjectPath = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
        $oProject = new Task_Base_Project($sProjectPath, $sEnvName, $this->_oServiceContainer);
        $this->_oLogger->log('Check tasks:');
        $this->_oLogger->indent();
        $oProject->setUp();
        $this->_oLogger->unindent();
        $this->_oLogger->log('Execute tasks:');
        $this->_oLogger->indent();
        $oProject->execute();
        $this->_oLogger->unindent();
    }

    /* Structure :
     * {
     * 		"rts":{"dev":[],"qa":[],"pre-prod":[]},
     * 		"tests":{
     * 			"tests_gitexport":{"rts_ref":"Branch or tag to deploy"},
     * 			"tests_languages":{"t1":"Branch","t2":"or tag","t3":"or tag"},
     * 			"all_tests":[]},
     * 		"ptpn":{"prod":[]}
     * }
     */
    public static function getProjectsEnvsList ()
    {
        $aAllProjectsName = Task_Base_Project::getAllProjectsName(DEPLOYMENT_RESOURCES_DIR);
        $aEnvsByProject = array();
        if ( ! empty($aAllProjectsName)) {
            foreach ($aAllProjectsName as $sProjectName) {
                $sProjectPath = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
                $aEnvsByProject[$sProjectName] = Task_Base_Target::getAvailableEnvsList($sProjectPath);
            }
        }
        ksort($aEnvsByProject);
        return $aEnvsByProject;
    }
}

<?php

namespace ede_deploy_core;
/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class ede_deploy_core
{
    /**
     * Instance de services.
     * @var ServiceContainer
     */
    private $_oServiceContainer;

    /**
     * Constructeur.
     */
    public function __construct ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::DEBUG);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new Shell_Adapter($oLogger);

        $this->_oServiceContainer = new ServiceContainer();
        $this->_oServiceContainer
            ->setLogAdapter($oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new Properties_Adapter($oShell))
            ->setNumberingAdapter(new Numbering_Adapter());
    }

    /**
     * Enregistre les propriétés externes dans l'instance Properties_Interface.
     *
     * @param array $aExternalProperties tableau indexé des valeurs ordonnées des propriétés externes.
     */
    private function _setExternalProperties (array $aExternalProperties)
    {
        $oProperties = $this->_oServiceContainer->getPropertiesAdapter();
        foreach ($aExternalProperties as $i => $sValue) {
            $sKey = Task_Base_ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
            $oProperties->setProperty($sKey, str_replace('&#0160;', ' ', $sValue));
        }
    }

    /**
     * Exécute le déploiement.
     *
     * @param string $sProjectName
     * @param string $sEnvName
     * @param string $sExecutionID au format YYYYMMDDHHMMSS_xxxxx, où x est un chiffre aléatoire,
     * par exemple '20111026142342_07502'
     * @param array $aExternalProperties tableau indexé des valeurs ordonnées des propriétés externes.
     * @param string $sRollbackID identifiant de déploiement sur lequel effectuer un rollback,
     * par exemple '20111026142342_07502'
     */
    public function run ($sProjectName, $sEnvName, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        $this->_oServiceContainer->getPropertiesAdapter()
            ->setProperty('project_name', $sProjectName)
            ->setProperty('environment_name', $sEnvName)
            ->setProperty('execution_id', $sExecutionID)
            ->setProperty('tmpdir', DEPLOYMENT_TMP_DIR . '/deploy_' . $sExecutionID)
            ->setProperty('rollback_id', $sRollbackID);

        $this->_setExternalProperties($aExternalProperties);

        $sProjectPath = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
        $oProject = new Task_Base_Project($sProjectPath, $sEnvName, $this->_oServiceContainer);
        $oLogger = $this->_oServiceContainer->getLogAdapter();
        $oLogger->log('Check tasks:');
        $oLogger->indent();
        $oProject->setUp();
        $oLogger->unindent();
        $oLogger->log('Execute tasks:');
        $oLogger->indent();
        $oProject->execute();
        $oLogger->unindent();
    }

    /**
     * Retourne la liste des environnements de chaque projet,
     * avec pour chacun d'eux la liste des paramètres externes.
     *
     * Structure :
     * {
     * 		"rts":{"dev":[],"qa":[],"pre-prod":[]},
     * 		"tests":{
     * 			"tests_gitexport":{"rts_ref":"Branch or tag to deploy"},
     * 			"tests_languages":{"t1":"Branch","t2":"or tag","t3":"or tag"},
     * 			"all_tests":[]},
     * 		"ptpn":{"prod":[]}
     * }
     *
     * @return array la liste des environnements de chaque projet,
     * avec pour chacun d'eux la liste des paramètres externes.
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

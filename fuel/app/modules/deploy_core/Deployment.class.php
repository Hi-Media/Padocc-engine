<?php
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Base\Target;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Deployment
{
    /**
     * Instance de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Constructeur.
     */
    public function __construct ()
    {
        $oBaseLogger = new Logger_Adapter(LoggerInterface::DEBUG);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new ShellAdapter($oLogger);

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new PropertiesAdapter($oShell))
            ->setNumberingAdapter(new NumberingAdapter());
    }

    /**
     * Enregistre les propriétés externes dans l'instance PropertiesInterface.
     *
     * @param array $aExternalProperties tableau indexé des valeurs ordonnées des propriétés externes.
     */
    private function _setExternalProperties (array $aExternalProperties)
    {
        $oProperties = $this->oDIContainer->getPropertiesAdapter();
        foreach ($aExternalProperties as $i => $sValue) {
            $sKey = ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
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
        $this->oDIContainer->getPropertiesAdapter()
            ->setProperty('project_name', $sProjectName)
            ->setProperty('environment_name', $sEnvName)
            ->setProperty('execution_id', $sExecutionID)
            ->setProperty('tmpdir', DEPLOYMENT_TMP_DIR . '/deploy_' . $sExecutionID)
            ->setProperty('rollback_id', $sRollbackID);

        $this->_setExternalProperties($aExternalProperties);

        $sProjectPath = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
        $oProject = new Project($sProjectPath, $sEnvName, $this->oDIContainer);
        $oLogger = $this->oDIContainer->getLogger();
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
        $aAllProjectsName = Project::getAllProjectsName(DEPLOYMENT_RESOURCES_DIR);
        $aEnvsByProject = array();
        if (! empty($aAllProjectsName)) {
            foreach ($aAllProjectsName as $sProjectName) {
                $sProjectPath = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
                $aEnvsByProject[$sProjectName] = Target::getAvailableEnvsList($sProjectPath);
            }
        }
        ksort($aEnvsByProject);
        return $aEnvsByProject;
    }
}

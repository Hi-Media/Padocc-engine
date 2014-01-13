<?php

namespace Himedia\Padocc;

use Himedia\Padocc\Properties\PropertiesInterface;
use Himedia\Padocc\Task\Base\ExternalProperty;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Base\Target;
use Psr\Log\LoggerInterface;

/**
 * Définit une propriété externe qu'il sera obligatoire de fournir lors de tout déploiement.
 * Cette propriété est par la suite réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <externalproperty name="ref" description="Branch or tag to deploy" />
 *
 * @category TwengaDeploy
 * @package Core
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
     * Adaptateur de propriétés.
     * @var PropertiesInterface
     */
    protected $oProperties;

    /**
     * Adaptateur de log.
     * @var LoggerInterface
     */
    protected $oLogger;

    /**
     * @var array
     */
    protected $aConfig;

    /**
     * Constructeur.
     */
    public function __construct (DIContainer $oDIContainer)
    {
        $this->oDIContainer = $oDIContainer;
        $this->oLogger = $this->oDIContainer->getLogger();
        $this->oProperties = $this->oDIContainer->getPropertiesAdapter();
        $this->aConfig = $this->oDIContainer->getConfig();
    }

    /**
     * Enregistre les propriétés externes dans l'instance PropertiesInterface.
     *
     * @param array $aExternalProperties tableau indexé des valeurs ordonnées des propriétés externes.
     */
    private function setExternalProperties (array $aExternalProperties)
    {
        foreach ($aExternalProperties as $i => $sValue) {
            $sKey = ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
            $this->oProperties->setProperty($sKey, str_replace('&#0160;', ' ', $sValue));
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
        $this->oProperties
            ->setProperty('project_name', $sProjectName)
            ->setProperty('environment_name', $sEnvName)
            ->setProperty('execution_id', $sExecutionID)
            ->setProperty('tmpdir', $this->aConfig['dir']['tmp'] . '/deploy_' . $sExecutionID)
            ->setProperty('rollback_id', $sRollbackID);

        $this->setExternalProperties($aExternalProperties);

        $sProjectPath = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
        $oProject = new Project($sProjectPath, $sEnvName, $this->oDIContainer);
        $this->oLogger->info('Check tasks:+++');
        $oProject->setUp();
        $this->oLogger->info('---Execute tasks:+++');
        $oProject->execute();
        $this->oLogger->info('---');
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

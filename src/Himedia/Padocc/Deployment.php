<?php

namespace Himedia\Padocc;

use Himedia\Padocc\Task\Base\ExternalProperty;
use Himedia\Padocc\Task\Base\Project;

/**
 * Deployment
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
final class Deployment
{
    /**
     * @var DIContainerInterface
     */
    private $oDIContainer;

    /**
     * @var array
     */
    private $aConfig;

    /**
     * Constructor.
     *
     * @param DIContainerInterface $oDIContainer Service container
     */
    public function __construct(DIContainerInterface $oDIContainer)
    {
        $this->aConfig      = $oDIContainer->getConfig();
        $this->oDIContainer = $oDIContainer;
    }

    /**
     * Gets the service container.
     *
     * @return DIContainerInterface
     */
    public function getContainer()
    {
        return $this->oDIContainer;
    }

    /**
     * Exécute le déploiement.
     *
     * @param string $xmlPathOrString     Path to the project XML configuration file or XML file content.
     * @param string $sEnvironment        Name of the environment where to deploy.
     * @param string $sExecutionID        au format YYYYMMDDHHMMSS_xxxxx, où x est un nombre aléatoire
     * @param array  $aExternalProperties tableau associatif nom/valeur des propriétés externes.
     * @param string $sRollbackID         identifiant de déploiement sur lequel effectuer un rollback
     */
    public function run($xmlPathOrString, $sEnvironment, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        $oLogger = $this->getContainer()->getLogger();

        // Interprets the project XML configuration into a SimpleXML object
        $oXmlProject = Project::getSXEProject($xmlPathOrString);

        if (file_exists($xmlPathOrString)) {
            $oLogger->info(sprintf('Project loaded from file %s', realpath($xmlPathOrString)));
        }

        $this->registerProperties(array(
            'project_name'     => (string)$oXmlProject['name'],
            'environment_name' => $sEnvironment,
            'execution_id'     => $sExecutionID,
            'tmpdir'           => $this->aConfig['dir']['tmp'] . '/deploy_' . $sExecutionID,
            'rollback_id'      => $sRollbackID
        ));

        $this->registerProperties($aExternalProperties, true, ExternalProperty::EXTERNAL_PROPERTY_PREFIX);

        $oProject = new Project($oXmlProject, $sEnvironment, $this->getContainer());

        $oLogger->info('Check tasks:+++');
        $oProject->setUp();

        $oLogger->info('---Execute tasks:+++');
        $oProject->execute();

        $oLogger->info('---');
    }

    /**
     * Registers external properties.
     *
     * @param array  $aProperties
     * @param bool   $bEscape
     * @param string $sPrefix
     */
    private function registerProperties(array $aProperties, $bEscape = false, $sPrefix = '')
    {
        foreach ($aProperties as $sName => $sValue) {
            $sQualifiedName = $sPrefix . $sName;
            $sFilteredValue = $bEscape ? str_replace('&#0160;', ' ', $sValue) : $sValue;
            $this->getContainer()->getPropertiesAdapter()->setProperty($sQualifiedName, $sFilteredValue);
        }
    }
}

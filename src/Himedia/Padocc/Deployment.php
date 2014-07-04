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
     * @param string $environment         Name of the environment where to deploy.
     * @param string $sExecutionID        au format YYYYMMDDHHMMSS_xxxxx, où x est un nombre aléatoire
     * @param array  $aExternalProperties tableau associatif nom/valeur des propriétés externes.
     * @param string $sRollbackID         identifiant de déploiement sur lequel effectuer un rollback
     */
    public function run($xmlPathOrString, $environment, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        $logger = $this->getContainer()->getLogger();

        // Interprets the project XML configuration into a SimpleXML object
        $xmlProject = Project::getSXEProject($xmlPathOrString);

        if (file_exists($xmlPathOrString)) {
            $logger->info(sprintf('Project loaded from file %s', realpath($xmlPathOrString)));
        }

        $this->registerProperties(array(
            'project_name'     => (string)$xmlProject['name'],
            'environment_name' => $environment,
            'execution_id'     => $sExecutionID,
            'tmpdir'           => $this->aConfig['dir']['tmp'] . '/deploy_' . $sExecutionID,
            'rollback_id'      => $sRollbackID
        ));

        $this->registerProperties($aExternalProperties, true, ExternalProperty::EXTERNAL_PROPERTY_PREFIX);

        $project = new Project($xmlProject, $environment, $this->getContainer());

        $logger->info('Check tasks:+++');
        $project->setUp();

        $logger->info('---Execute tasks:+++');
        $project->execute();

        $logger->info('---');
    }

    /**
     * Registers external properties.
     *
     * @param array  $properties
     * @param bool   $escape
     * @param string $prefix
     */
    private function registerProperties(array $properties, $escape = false, $prefix = '')
    {
        foreach ($properties as $name => $value) {
            $qualifiedName = $prefix . $name;
            $filteredValue = $escape ? str_replace('&#0160;', ' ', $value) : $value;
            $this->getContainer()->getPropertiesAdapter()->setProperty($qualifiedName, $filteredValue);
        }
    }
}

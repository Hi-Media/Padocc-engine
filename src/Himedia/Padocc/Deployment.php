<?php

namespace Himedia\Padocc;

use Himedia\Padocc\Task\Base\ExternalProperty;
use Himedia\Padocc\Task\Base\Project;

/**
 * Deployment
 *
 *
 *
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
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

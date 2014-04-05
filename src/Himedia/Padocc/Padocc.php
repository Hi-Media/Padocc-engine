<?php

namespace Himedia\Padocc;

use GAubry\Logger\ColoredIndentedLogger;
use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Task\Base\Target;

/**
 * API
 *
 * @package Himedia\Padocc
 */
class Padocc {

    /**
     * @var array
     */
    private $aConfig;

    public function __construct (array $aConfig)
    {
        $this->aConfig = $aConfig;
    }

    public function getEnvAndExtParameters ($sXmlProjectPath)
    {
        $aEnv = Target::getAvailableEnvsList($sXmlProjectPath);
        return $aEnv;
    }

    /**
     * Exécute le déploiement sans supervisor et sans trace en DB.
     *
     * @param string $sXmlProjectPath chemin vers le XML de configuration du projet
     * @param string $sEnvName
     * @param string $sExecutionID au format YYYYMMDDHHMMSS_xxxxx, où x est un nombre aléatoire,
     * par exemple '20111026142342_07502'
     * @param array $aExternalProperties tableau associatif nom/valeur des propriétés externes.
     * @param string $sRollbackID identifiant de déploiement sur lequel effectuer un rollback,
     * par exemple '20111026142342_07502'
     */
    public function runWOSupervisor ($sXmlProjectPath, $sEnvName, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        // Build dependency injection container
        $oLogger      = new ColoredIndentedLogger($this->aConfig['GAubry\Logger\ColoredIndentedLogger']);
        $oShell       = new ShellAdapter($oLogger, $this->aConfig['GAubry\Shell']);
        $oDIContainer = new DIContainer();
        $oDIContainer
            ->setLogger($oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new Properties\Adapter($oShell, $this->aConfig['Himedia\Padocc']))
            ->setNumberingAdapter(new Numbering\Adapter())
            ->setConfig($this->aConfig['Himedia\Padocc']);

        $oDeployment = new Deployment($oDIContainer);
        $oDeployment->run($sXmlProjectPath, $sEnvName, $sExecutionID, $aExternalProperties, $sRollbackID);
    }

    /**
     * Exécute le déploiement avec supervisor et avec trace en DB.
     *
     * @param string $sXmlProjectPath chemin vers le XML de configuration du projet
     * @param string $sEnvName
     * @param string $sExecutionID au format YYYYMMDDHHMMSS_xxxxx, où x est un nombre aléatoire,
     * par exemple '20111026142342_07502'
     * @param array $aExternalProperties tableau associatif nom/valeur des propriétés externes.
     * @param string $sRollbackID identifiant de déploiement sur lequel effectuer un rollback,
     * par exemple '20111026142342_07502'
     */
    public function run ($sXmlProjectPath, $sEnvName, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        $sSupervisorBin = $this->aConfig['Himedia\Padocc']['dir']['vendor'] . '/bin/supervisor.sh';
        $sSupervisorParams = '--conf=' . $this->aConfig['Himedia\Padocc']['dir']['conf'] . '/supervisor.sh';
        $sPadoccBin = $this->aConfig['Himedia\Padocc']['dir']['src'] . '/padocc.php';
        $sPadoccParams = "--action=deploy-wos --xml=$sXmlProjectPath --env=$sEnvName";
        foreach ($aExternalProperties as $sName => $sValue) {
            $sPadoccParams .= " -p $sName='$sValue'";
        }
        $sCmd = "$sSupervisorBin $sSupervisorParams $sPadoccBin \"$sPadoccParams\"";
        var_dump($sCmd);

        $fp = popen($sCmd, 'r');
        while (! feof($fp)) {
            set_time_limit (100);
            $results = fgets($fp, 256);
            if (strlen($results) > 0) {
                echo $results;
            }
        }
    }
}

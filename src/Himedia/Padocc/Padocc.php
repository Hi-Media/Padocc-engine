<?php

namespace Himedia\Padocc;

use GAubry\Helpers\Helpers;
use GAubry\Logger\ColoredIndentedLogger;
use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\DB\DeploymentMapper;
use Himedia\Padocc\DB\PDOAdapter;
use Himedia\Padocc\Task\Base\Project;
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

    public function getInfoLog ($sExecId)
    {
        $sInfoLogPath = sprintf($this->aConfig['Himedia\Padocc']['info_log_path_pattern'], $sExecId);
        if (! file_exists($sInfoLogPath)) {
            throw new \RuntimeException("File not found: '$sInfoLogPath'!");
        }
        $sContent = file_get_contents($sInfoLogPath);
        return $sContent;
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

    public function enqueue ($sXmlProjectPath, $sEnvName, array $aExternalProperties)
    {
        $oDB = PDOAdapter::getInstance($this->aConfig['Himedia\Padocc']['db']);
        $oDeploymentMapper = new DeploymentMapper($oDB);
        $sExecId = date('YmdHis') . sprintf('_%05d', rand(0, 99999));
        $oProject = Project::getSXEProject($sXmlProjectPath);
        $sProjectName = (string)$oProject['name'];
        $aParameters = array(
            'exec_id' => $sExecId,
            'xml_path' => $sXmlProjectPath,
            'project_name' => $sProjectName,
            'env_name' => $sEnvName,
            'external_properties' => json_encode($aExternalProperties),
            'status' => DeploymentStatus::QUEUED,
            'nb_warnings' => 0,
            'date_queue' => date('Y-m-d H:i:s'),
            'is_rollbackable' => 0
        );
        $oDeploymentMapper->insert($aParameters);
        return $sExecId;
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
     * @return string $sExecutionID
     * @throws \RuntimeException if Supervisor log result are unexpected.
     */
    public function run ($sXmlProjectPath, $sEnvName, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        $oDB = PDOAdapter::getInstance($this->aConfig['Himedia\Padocc']['db']);
        $oDeploymentMapper = new DeploymentMapper($oDB);
        $sExecId = date('YmdHis') . sprintf('_%05d', rand(0, 99999));
        $oProject = Project::getSXEProject($sXmlProjectPath);
        $sProjectName = (string)$oProject['name'];
        $aParameters = array(
            'exec_id' => $sExecId,
            'xml_path' => $sXmlProjectPath,
            'project_name' => $sProjectName,
            'env_name' => $sEnvName,
            'external_properties' => json_encode($aExternalProperties),
            'status' => DeploymentStatus::IN_PROGRESS,
            'nb_warnings' => 0,
            'date_start' => date('Y-m-d H:i:s'),
            'is_rollbackable' => 0
        );
        $oDeploymentMapper->insert($aParameters);

        $sSupervisorBin = $this->aConfig['Himedia\Padocc']['dir']['vendor'] . '/bin/supervisor.sh';
        $sSupervisorParams = '--conf=' . $this->aConfig['Himedia\Padocc']['dir']['conf'] . '/supervisor.sh '
                           . "--exec-id=$sExecId";
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

        $sInfoLogPath = sprintf($this->aConfig['Himedia\Padocc']['info_log_path_pattern'], $sExecId);
        $aResult = Helpers::exec("tail -n1 '$sInfoLogPath'");
        if (preg_match('/^[0-9 :-]{22}cs;\[SUPERVISOR\] (OK|ERROR|WARNING \(#(\d+)\))\s*$/', $aResult[0], $aMatches) !== 1) {
            throw new \RuntimeException("Supervisor log result unexpected! Log file: '$sInfoLogPath'.");
        } elseif ($aMatches[1] == 'OK') {
            $sStatus = DeploymentStatus::SUCCESSFUL;
            $iNbWarnings = 0;
        } elseif ($aMatches[1] == 'ERROR') {
            $sStatus = DeploymentStatus::FAILED;
            $iNbWarnings = 0;
        } else {
            $sStatus = DeploymentStatus::WARNING;
            $iNbWarnings = $aMatches[2];
        }
        $aParameters = array(
            'exec_id' => $sExecId,
            'status' => $sStatus,
            'nb_warnings' => $iNbWarnings,
            'date_end' => date('Y-m-d H:i:s')
        );
        $oDeploymentMapper->update($aParameters);
        return $sExecId;
    }
}

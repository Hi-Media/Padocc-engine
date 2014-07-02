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
class Padocc
{

    /**
     * @var array
     */
    private $aConfig;

    private $oDeploymentMapper;

    public function __construct (array $aConfig)
    {
        $this->aConfig = $aConfig;
        $oDB = PDOAdapter::getInstance($this->aConfig['Himedia\Padocc']['db']);
        $this->oDeploymentMapper = new DeploymentMapper($oDB);
    }

    /**
     * Return environments and external properties of specified project.
     *
     * Example: array(
     *     'dev' => array(),
     *     'qa' => array(
     *         'ref' => "Branch or tag to deploy",
     *         …
     *     )
     * )
     *
     * @param string $sXmlProject XML project path or XML data
     * @return array environments and external properties of specified project.
     */
    public function getEnvAndExtParameters ($sXmlProject)
    {
        $aEnv = Target::getAvailableEnvsList($sXmlProject);
        return $aEnv;
    }

    /**
     * Return triplet containing DB record of specified exec_id, content of execution log, and errors.
     *
     * @param $sExecId
     * @return array triplet containing DB record of specified exec_id, content of execution log, and errors.
     * @throws \RuntimeException if info log or error log not found
     */
    public function getStatus ($sExecId)
    {
        $aFilter = array(
            array(
                array('exec_id' => $sExecId)
            )
        );
        $aResult = $this->oDeploymentMapper->select($aFilter);
        $aRecord = $aResult[0];

        if ($aRecord['status'] == DeploymentStatus::QUEUED) {
            $sInfoLog = '';
            $sErrorLog = '';
        } else {
            $sInfoLogPath = sprintf($this->aConfig['Himedia\Padocc']['info_log_path_pattern'], $sExecId);
            if (! file_exists($sInfoLogPath)) {
                throw new \RuntimeException("File not found: '$sInfoLogPath'!");
            }
            $sInfoLog = file_get_contents($sInfoLogPath);

            $sErrorLogPath = sprintf($this->aConfig['Himedia\Padocc']['error_log_path_pattern'], $sExecId);
            if (! file_exists($sErrorLogPath)) {
                throw new \RuntimeException("File not found: '$sErrorLogPath'!");
            }
            $sErrorLog = file_get_contents($sErrorLogPath);
        }

        return array(
            'record'    => $aRecord,
            'info-log'  => $sInfoLog,
            'error-log' => $sErrorLog
        );
    }

    public function getQueueAndRunning ()
    {
        $aFilter = array(
            array(
                array('status' => DeploymentStatus::QUEUED),
                array('status' => DeploymentStatus::IN_PROGRESS)
            )
        );
        $aResult = $this->oDeploymentMapper->select($aFilter, array('exec_id ASC'));
        return $aResult;
    }

    public function getLatestDeployments ($sProjectName, $sEnvName)
    {
        $aFilter = array(
            array(
                array('status' => DeploymentStatus::SUCCESSFUL),
                array('status' => DeploymentStatus::WARNING),
                array('status' => DeploymentStatus::FAILED)
            )
        );
        if (! empty($sProjectName)) {
            $aFilter[] = array(array('project_name' => $sProjectName));
        }
        if (! empty($sEnvName)) {
            $aFilter[] = array(array('env_name' => $sEnvName));
        }
        $aResult = $this->oDeploymentMapper->select($aFilter, array('exec_id ASC'));
        return $aResult;
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
    public function runWOSupervisor (
        $sXmlProjectPath,
        $sEnvName,
        $sExecutionID,
        array $aExternalProperties,
        $sRollbackID
    ) {
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
        $this->oDeploymentMapper->insert($aParameters);
        return $sExecId;
    }

    public function dequeue ()
    {
        $aFilter = array(
            array(
                array('status' => DeploymentStatus::QUEUED)
            )
        );
        $aResult = $this->oDeploymentMapper->select($aFilter, array('exec_id ASC'), 1);

        if (count($aResult) > 0) {
            $aOldestQueued = $aResult[0];
            var_dump($aOldestQueued);

            $sExecId = $this->run(
                $aOldestQueued['xml_path'],
                $aOldestQueued['env_name'],
                $aOldestQueued['exec_id'],
                json_decode($aOldestQueued['external_properties'], true),
                ''
            );
        } else {
            $sExecId = '';
        }
        return $sExecId;
    }

    /**
     * Exécute le déploiement avec supervisor et avec trace en DB.
     *
     * @param string $sXmlProjectPath chemin vers le XML de configuration du projet
     * @param string $sEnvName
     * @param string $sExecId au format YYYYMMDDHHMMSS_xxxxx, où x est un nombre aléatoire,
     * par exemple '20111026142342_07502'. Si vide, en génère un.
     * @param array $aExternalProperties tableau associatif nom/valeur des propriétés externes.
     * @param string $sRollbackID identifiant de déploiement sur lequel effectuer un rollback,
     * par exemple '20111026142342_07502'
     * @return string $sExecutionID
     * @throws \RuntimeException if Supervisor log result are unexpected.
     */
    public function run ($sXmlProjectPath, $sEnvName, $sExecId, array $aExternalProperties, $sRollbackID)
    {
        $aFilter = array(
            array(
                array('exec_id' => $sExecId)
            )
        );
        $aResult = $this->oDeploymentMapper->select($aFilter);
        $bAlreadyInDB = (count($aResult) === 1);

        if (empty($sExecId)) {
            $sExecId = date('YmdHis') . sprintf('_%05d', rand(0, 99999));
        }
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
        if ($bAlreadyInDB) {
            $this->oDeploymentMapper->update($aParameters);
        } else {
            $this->oDeploymentMapper->insert($aParameters);
        }

        $sSupervisorBin = $this->aConfig['Himedia\Padocc']['dir']['vendor'] . '/bin/supervisor.sh';
        $sSupervisorParams = '--conf=' . $this->aConfig['Himedia\Padocc']['supervisor_config'] . ' '
                           . "--exec-id=$sExecId";
        $sPadoccBin = $this->aConfig['Himedia\Padocc']['dir']['src'] . '/padocc.php';
        $sPadoccParams = "--action=deploy-wos --xml=$sXmlProjectPath --env=$sEnvName --exec-id=$sExecId";
        foreach ($aExternalProperties as $sName => $sValue) {
            $sPadoccParams .= " -p $sName='$sValue'";
        }
        $sCmd = "$sSupervisorBin $sSupervisorParams $sPadoccBin \"$sPadoccParams\"";
        var_dump($sCmd);

        $fp = popen($sCmd, 'r');
        while (! feof($fp)) {
            set_time_limit(100);
            $results = fgets($fp, 256);
            if (strlen($results) > 0) {
                echo $results;
            }
        }

        $sInfoLogPath = sprintf($this->aConfig['Himedia\Padocc']['info_log_path_pattern'], $sExecId);
        $aResult = Helpers::exec("tail -n1 '$sInfoLogPath'");
        if (preg_match(
            '/^[0-9 :-]{22}cs;\[SUPERVISOR\] (OK|ERROR|WARNING \(#(\d+)\))\s*$/',
            $aResult[0],
            $aMatches
        ) !== 1) {
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
        $this->oDeploymentMapper->update($aParameters);
        return $sExecId;
    }
}

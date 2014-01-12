<?php
class Deployment
{
    const DEPLOYMENT_SCRIPT_NAME = 'deployment.php';
    const SUPERVISOR_STATUS_WAITING 	= 1;
    const SUPERVISOR_STATUS_IN_PROGRESS = 2;
    const SUPERVISOR_STATUS_END_OK 		= 3;
    const SUPERVISOR_STATUS_END_WARNING = 4;
    const SUPERVISOR_STATUS_END_ERROR 	= 5;

    const NB_PROCESSED_DEMANDS_TO_DISPLAY = 40;

    public function __construct($oSsh)
    {
        $this->oSsh = $oSsh;
    }

    private function execSSH ($sCmd) {
        $sErrorMsg = exec('whoami', $aResult, $iReturnCode);
        $sServer = DEPLOYMENT_SSH_LOGIN . '@' . DEPLOYMENT_SSH_SERVER;
        $sCmd = 'ssh -T ' . $sServer . " /bin/bash <<EOF\n$sCmd\nEOF\n";
        $sFullCmd = '( ' . $sCmd . ' ) 2>&1';
        /*$sErrorMsg = exec($sFullCmd, $aResult, $iReturnCode);
        if ($iReturnCode !== 0) {
            throw new \RuntimeException(implode("\n", $aResult), $iReturnCode);
        }*/

        $sResult = $this->oSsh->exec($sFullCmd, '');
        return $sResult;
    }

    private function sendFileSsh($sSource, $sDest)
    {
        $sServer = DEPLOYMENT_SSH_LOGIN . '@' . DEPLOYMENT_SSH_SERVER;
        $sCmd = 'scp '.$sSource.' '.$sServer.':/'.$sDest;
        $sFullCmd = '( ' . $sCmd . ' ) 2>&1';
        $sResult = $this->oSsh->exec($sFullCmd, '');

        return $sResult;
    }


    private function _callDBSupervisor (array $aParameters) {
		$sParameters = base64_encode(serialize($aParameters));
        $sCmd = 'php -q ' . DEPLOYMENT_SUPERVISOR_SCRIPT_PATH . '/supervisor_data_access.php ' . $sParameters;
        $sResult = $this->execSSH($sCmd);
        $aResult = unserialize(base64_decode($sResult));

        if ($aResult === false) {
        	//var_dump($aParameters);
        	//var_dump($sCmd);
        	//var_dump($sResult);
        	//var_dump(base64_decode($sResult));
        	//var_dump(unserialize(base64_decode($sResult)));
        	throw new \RuntimeException(
        		'Parameters: ' . print_r($aParameters, true)
        		. "\nRaw result: '" . $sResult . "'"
        	);
        }
        return $aResult;
    }

    /**
     * Return all environment for all projets
     */
    public function getProjectsEnvsList()
    {
        $aCommand = array(
            'php '.DEPLOYMENT_DEPLOYMENT_SCRIPT_PATH.'/' . self::DEPLOYMENT_SCRIPT_NAME . ' --getProjectsEnvsList && echo',
        );
        $aProjectsEnvList = $this->execSSH(implode(';', $aCommand));
        return $aProjectsEnvList;
    }

    /**
     * Return the external parameter that can be send for an env of an project
     * @param string $sProjectName Project Name
     * @param string $sEnvName Environnement name of the project
     */
    public function getUserParameterList($sProjectName, $sEnvName)
    {
        //TODO: implement getUserParameterList($sProjectName, $sEnvName)
    }

    /**
     * Return the project config content
     * @param unknown_type $sProjectName
     */
    public function getProjectConfig($sProjectName) {
        $sConfigFileName = $sProjectName.'.xml';
        $aCommand2 = array(
            'cat '.DEPLOYMENT_DEPLOYMENT_SCRIPT_PATH.'/resources/'.$sConfigFileName.' && echo',
        );

        $sConfig = $this->execSSH(implode(';', $aCommand2));
        if($sConfig === false) {
            throw new Exception('An error occured while trying to retrieve config file ' . $sConfigFileName);
        }
        return $sConfig;
    }

    public function setProjectConfig($sProjectName, $sXml) {
        $sConfigFileName = $sProjectName.'.xml';

        if(!empty($sProjectName) && !empty($sXml))
        {
            $sTmpFile = tempnam("/tmp", "");
            chmod($sTmpFile, 0777);
            file_put_contents($sTmpFile, $sXml);
            $this->sendFileSsh($sTmpFile, DEPLOYMENT_DEPLOYMENT_SCRIPT_PATH.'/resources/'.$sConfigFileName);
        }
    }

    public function getLogs($sExecutionId) {
        $aCommand = array(
            '/bin/bash ' . DEPLOYMENT_SUPERVISOR_SCRIPT_PATH . '/supervisor.sh --get-logs deployment.php ' . $sExecutionId,
        );

        $sXML = $this->execSSH(implode(';', $aCommand));
        $oLogs = new SimpleXMLElement($sXML);
        $aRawLogs = explode("\n", (string)$oLogs->info);
        $sErrors = nl2br((string)$oLogs->error, true);

        $aLogs = array();
        $iSum = 0;
        $iLastTs = 0;
        foreach ($aRawLogs as $sLine) {
            if (! empty($sLine)) {
                preg_match('/^([^;]+);(.*)$/', $sLine, $aMatches);
                $sTimestamp = $aMatches[1];
                $sMsg = $aMatches[2];

                // Gestion de l'indentation :
                if (preg_match('/^(\s+)/', $sMsg, $aMatches) === 1) {
                    $iLevel = round(strlen($aMatches[1])/3);
                } else {
                    $iLevel = 0;
                }

                // Elapsed time :
                $d = explode(':', strtr(substr($sTimestamp, 0, -2), '- ', '::'));
                $iTimestamp = $d[6] + 100*mktime($d[3], $d[4], $d[5], $d[1], $d[2], $d[0]);
                if ($iLastTs === 0) {
                    $iElapsedTime = 0;
                } else {
                    // Si pb de centièmes de secondes (100cs mémorisés en tant que 00cs)  :
                    if ($iTimestamp < $iLastTs) {
                        $iTimestamp += 100;
                        $sTimestamp = date('Y-m-d H:i:s', substr($iTimestamp, 0, -2)) . ' ' . substr($iTimestamp, -2) . 'cs';
                    }
                    $iElapsedTime = $iTimestamp - $iLastTs;
                }

                $iLastTs = $iTimestamp;
                $iSum += $iElapsedTime;
                //$sElapsedTime = ($iElapsedTime>=60 ? floor($iElapsedTime/60) . 'min ' : '') . ($iElapsedTime%60) . 's';

                $aLogs[] = array(
                    'ts' => $sTimestamp,
                    'elapsed_time' => $iElapsedTime,
                    'sum' => $iSum,
                    'msg' => trim($sMsg),
                    'lvl' => $iLevel
                );
            }
        }

        $iTotalTime = $iSum;
        $count=count($aLogs);
        $aLogs[$count-1]['section'] = 0;
        for ($i=$count-2; $i>=0; $i--) {
            $sum = 0;
            $lvl = $aLogs[$i]['lvl'];
            for ($j=$i+1; $j<$count; $j++) {
                $sum += $aLogs[$j]['elapsed_time'];
                if ($aLogs[$j]['lvl'] <= $lvl) {
                    break;
                }
            }
            $aLogs[$i]['section'] = $sum;
            $aLogs[$i]['section_percent'] = round($sum*100/$iTotalTime);
        }

        return array($aLogs, $sErrors);
    }

    public function formatLogs (array $aLogs) {
        $sInfos = '<table><thead>
            <tr>
                <th class="first">Timestamp</th>
                <th>From<br />start</th>
                <th>In sub-<br />section</th>
                <th colspan="2">Message</th>
            </tr></thead><tbody>' . "\n";
        $iNbWarnings = 0;
        foreach ($aLogs as $aRow) {
            $timestamp = isset($aRow['ts']) ? $aRow['ts'] : '';
            $msg = $aRow['msg'];

            // Gestion de l'indentation :
            $sClassIndent = 'indent' . $aRow['lvl'];

            // Gestion du type de la ligne :
            if (preg_match('/^\s*\[START\]|START/', $msg) === 1) {
                $sClass = 'start';
            } elseif (preg_match('/^\s*(?:\[DEBUG\]|DEBUG )(.*)$/', $msg, $aM2) === 1) {
                $sClass = 'debug';
                $msg = $aM2[1];
            } elseif (preg_match('/^\s*(?:\[OK\]|OK)/', $msg) === 1) {
                $sClass = 'ok';
            } elseif (preg_match('/^\s*(?:\[MAILTO\]|MAILTO )(.*)/', $msg, $aM2) === 1) {
                $sClass = 'mail';
                $msg = 'mailto: ' . $aM2[1];
            } elseif (preg_match('/^\s*(?:\[WARNING\]|WARNING)(.*)$/', $msg, $aMatches) === 1) {
                $sClass = 'warning';
                if (! empty($aMatches[1])) {
                    $iNbWarnings++;
                    $msg = $aMatches[1];
                } else {
                    $msg = "Completed with $iNbWarnings warning" . ($iNbWarnings > 1 ? 's' : '') . '.';
                }
            } elseif (preg_match('/^\s*(?:\[ERROR\]|ERROR)/', $msg) === 1) {
                $sClass = 'failure';
            } elseif (preg_match('/^\s*Execute( tasks|\s+\'\d+(\.\d+)*_Task[^\']*\'\s+task)/i', $msg) === 1) {
                $sClass = 'task_execute';
            } elseif (preg_match('/^\s*Check( tasks|\s+\'\d+(\.\d+)*_Task[^\']*\'\s+task)/i', $msg) === 1) {
                $sClass = 'task_check';
            } else {
                $sClass = 'normal';
            }

            $timestamp = str_replace(' ', ', ', substr($timestamp, 11));
            $text = str_replace('\n', '<br />', $msg);

            if ($aRow['sum'] < 5) {
                $sum = '~0';
            } elseif ($aRow['sum'] < 10*100) {
                $sum = number_format($aRow['sum']/100, 1, '.', ' ') . 's';
            } else {
                $sum = ($aRow['sum']>=60*100 ? floor($aRow['sum']/100/60) . 'min ' : '') . round(($aRow['sum']%6000)/100) . 's';
            }

            if ($aRow['section'] < 5) {
                $section = '~0';
            } elseif ($aRow['section'] < 10*100) {
                $section = number_format($aRow['section']/100, 1, '.', ' ') . 's';
            } else {
                $section = ($aRow['section']>=60*100 ? floor($aRow['section']/100/60) . 'min ' : '') . round(($aRow['section']%6000)/100) . 's';
            }
            if ($aRow['section'] >= 1*100 && $aRow['lvl'] >= 2 && $aRow['section_percent'] >= 10) {
                $sSectionClass = ' class="section' . floor($aRow['section_percent']/10)
                    . '" title="' . $aRow['section_percent'] . '% of total elapsed time"';
            } else {
                $sSectionClass = '';
            }

            $sInfos .= '<tr class="' . $sClass . '"><th>' . $timestamp . '</th>'
                . '<th>' . $sum . '</th>'
                . '<th' . $sSectionClass . '>' . $section . '</th>'
                . '<th class="icon"></th>'
                . '<td class="' . $sClassIndent . ' text">'. $text . '</td></tr>' . "\n";
        }

        if (
            preg_match('/^\s*\[OK\]|OK/', $msg) === 0
            && preg_match('/^\s*\[ERROR\]|ERROR/', $msg) === 0
            && preg_match('/^Completed with \d+ warning/', $msg) === 0
        ) {
            $sInfos .= '<tr class="normal"><th class="in_progress" colspan="5">&nbsp;</th></tr>' . "\n";
        }

        $sInfos .= '</tbody></table>' . "\n";
        return $sInfos;
    }

    public function getQueue() {
        $aParameters = array('action' => 'getQueue');
        $aResult = $this->_callDBSupervisor($aParameters);
        return $aResult;
    }

    public function getProcessedDemands($sProject) {
    	$aParameters = array(
            'action' => 'getProcessedDemands',
            'project' => ( ! empty($sProject) ? $sProject : ''),
           	'limit' => self::NB_PROCESSED_DEMANDS_TO_DISPLAY
        );
        $aResult = $this->_callDBSupervisor($aParameters);
        return $aResult;
    }

    public function getProcessedDemandsByEnv($sProject) {

       // $sProject = Input::get('p');
        $aProjetsEnvsList = json_decode($this->getProjectsEnvsList(), true);

        if (empty($sProject)) {
            throw new Exception('Aucun projet défini');
        } elseif (! isset($aProjetsEnvsList[$sProject])) {
            throw new Exception('Aucune configuration n\'a été trouvé pour le projet' . $sProject);
        }

        $aEnvs = array_keys($aProjetsEnvsList[$sProject]);
        $aResults = array();
        foreach ($aEnvs as $sEnv){
            $aParameters = array(
                'action' => 'getProcessedDemands',
                'project' => $sProject,
                'env' => $sEnv,
            	'status' => self::SUPERVISOR_STATUS_END_OK . ',' . self::SUPERVISOR_STATUS_END_WARNING,
            	'limit' => 1
            );
            $aResult = $this->_callDBSupervisor($aParameters);
            $aResults[] = isset($aResult[0]) ? $aResult[0] : array();
        }
        return $aResults;
    }

    public function getAvailableRollbacks ($sProject, $sEnv) {
    	global $oUser;

    	$aProjectsEnvsList = json_decode($this->getProjectsEnvsList(), true);
        if (empty($sProject) || empty($sEnv)) {
            throw new Exception('Projet ou env non défini !');
        } elseif (! isset($aProjectsEnvsList[$sProject][$sEnv])) {
            throw new Exception("Aucune configuration n'a été trouvé pour le projet '$sProject' et l'environnement '$sEnv' !");
        }

        $aParameters = array(
            'action' => 'getProcessedDemands',
            'project' => $sProject,
            'env' => $sEnv,
        	'parameters' => 'NOT LIKE \'%"--rollback=%\'',
        	'status' => self::SUPERVISOR_STATUS_END_OK . ',' . self::SUPERVISOR_STATUS_END_WARNING,
         	'limit' => 5
        );
        $aResults = $this->_callDBSupervisor($aParameters);

        $sHTML = '';
        foreach ($aResults as $aRow) {
        	// Status:
        	if ($aRow['supervisor_demand_status_id'] == self::SUPERVISOR_STATUS_END_OK) {
        		$sStatusClass = 'ok';
        	} elseif ($aRow['supervisor_demand_status_id'] == self::SUPERVISOR_STATUS_END_WARNING) {
        		$sStatusClass = 'warning';
        	} else {
        		throw new Exception("Invalid status: '" . $aRow['supervisor_demand_status_id'] . "'!");
        	}

        	// Parameters:
        	$aParameters = array_slice(str_getcsv($aRow['parameters'], ";", '"', '\\'), 2);
        	$aParametersName = array_values($aProjectsEnvsList[$sProject][$sEnv]);
        	$aHTMLParameters = array();
			for ($i=0; $i<count($aParametersName); $i++) {
				$sValue = (isset($aParameters[$i]) ? $aParameters[$i] : '??');
				$aHTMLParameters[] = '<b class="parameter">' . $aParametersName[$i] . ':</b> ' . $sValue . '';
			}
			$sParameters = implode(', ', $aHTMLParameters);
			if ($sParameters != '') {
				$sParameters .= '.';
			} else {
				$sParameters = '<b class="parameter">No parameters.</b>';
			}

			// Link
			// ?instigator_email=geoffroy.aubry%40twenga.com&project=tests&env=tests_languages
			//&addparam_t1=a&addparam_t2=z&addparam_t3=v&action=deployment&m=addDemand
			$aData = array(
				//'instigator_email' => $oUser->getEmail(),
                'instigator_email' => 'tony.caron@twenga.com',
				'project' => $sProject,
				'env' => $sEnv,
				'action' => 'deployment',
				'm' => 'addDemand'
			);
			foreach (array_keys($aProjectsEnvsList[$sProject][$sEnv]) as $sParamName) {
				$aData['addparam_' . $sParamName] = '-';
			}
			$aData['addparam_' . 'rollback'] = '--rollback=' . $aRow['execution_id'];
			$sLink = '?' . http_build_query($aData);

			// LI
        	$sHTML .= '<li>'
        			. '<span class="">' . $aRow['date_end'] . '</span>'
        			. '<div class="' . $sStatusClass . '">' . $sParameters . '</div>'
        			. '<a class="rollback" href="' . $sLink . '" onclick="return confirm(\'Are you sure to want to rollback?\');">rollback !</a>'
        			. '</li>';
        }

        //$sHTML .= print_r($aProjectsEnvsList[$sProject][$sEnv], true);
        //$sHTML .= print_r($aResults, true);
        if ($sHTML == '') {
        	$sHTML = '<i>No available rollback!</i>';
        }
        return $sHTML;
    }

    public function getProcessedRollbackDemand($sRollbackID) {
        $aParameters = array(
            'action' => 'getProcessedDemands',
            'execution_id' => $sRollbackID,
        	'limit' => 1
        );
        $aResult = $this->_callDBSupervisor($aParameters);
        return $aResult;
    }

    public function addDemand($sInstigatorEmail, $sProjectName, $sEnvName, array $aExternalProperties=array())
    {
        setcookie('last_deployed_project', $sProjectName);
        $_COOKIE['last_deployed_project'] = $sProjectName;
        setcookie('dashboard_last_selected_project', $sProjectName);
        $_COOKIE['dashboard_last_selected_project'] = $sProjectName;

        $aParameters = array_merge(array($sProjectName, $sEnvName), $aExternalProperties);
        foreach ($aParameters as $i => $sValue) {
            $aParameters[$i] = str_replace(' ', '&#0160;', $sValue);
        }
        $sParameters = ' "' . implode('" "', $aParameters) . '"';
        $aCommand2 = array(
            '/bin/bash ' . DEPLOYMENT_SUPERVISOR_SCRIPT_PATH . '/supervisor.sh --instigator-email=' . $sInstigatorEmail
                . ' --add ' . self::DEPLOYMENT_SCRIPT_NAME . $sParameters
        );
        $sConfig = $this->execSSH(implode(';', $aCommand2));
    }
}

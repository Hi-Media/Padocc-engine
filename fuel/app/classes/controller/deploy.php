<?php

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 * 
 * @package  app
 * @extends  Controller
 */

use \Model\Project;
use \Model\Configuration;
use \Model\User;
use \Model\DeployQueue;
class Controller_Deploy extends Controller
{



	/**
	 * The basic welcome message
	 * 
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$view = View_Smarty::forge('deploy.tpl');

		// TODO REMOVE
		User::login("tony.caron@twenga.com");

		$aProjectListByGroup = Project::listingByGroup();
		$aProjectGroupList = array();
		foreach($aProjectListByGroup as $aProject)
		{
			$aProjectGroupList[$aProject['GROUP']][] = $aProject;
		}		

		$aUserList = User::listing();
		$view->set('aProjectGroupList', $aProjectGroupList);
		$view->set('USER_ID', User::getLoggedUserId());		

		




		return Response::forge($view);
	}

	public function action_add()
	{
		$iInstigatorId = User::getLoggedUserId();
		$iProjectId = Input::post('PROJECT_ID');
		$iProjectConfigurationId = Input::post('PROJECT_CONFIGURATION_ID');
		$sEnvironment = Input::post('PROJECT_ENVIRONMENT');
		$aExternalProperty = Input::post('EXTERNAL_PROPERTY');


		$val = Validation::forge();
		$val->add('PROJECT_ID')->add_rule('required');
		$val->add('PROJECT_CONFIGURATION_ID')->add_rule('required');
		$val->add('PROJECT_ENVIRONMENT')->add_rule('required');
		if (!$val->run()) die;

		// TODO message waring AIR
		if(DeployQueue::isInProgress($iProjectId) === true) die;
		

		$iDeployQueueId = DeployQueue::add($iProjectId, $iProjectConfigurationId, $sEnvironment, $aExternalProperty, $iInstigatorId);

		return json_encode($iDeployQueueId);
	
	}

	public function action_get_project_configurations()
	{
		$iProjectId = Input::post("PROJECT_ID");
		$aConfiguration = Configuration::getProjectConfigurations($iProjectId);

		return json_encode($aConfiguration);
	}

	public function action_get_configuration()
	{
		$iConfigurationId = Input::post("PROJECT_CONFIGURATION_ID");
		$aConfiguration = Configuration::getConfiguration($iConfigurationId);

		return json_encode($aConfiguration);
	}

	public static function getLogs($iDeployQueueId)
	{
		$sLogPath = \Config::get('log_path');
		$aDeploy = DeployQueue::get($iDeployQueueId);
		
		if(empty($aDeploy['EXECUTION_ID']))
			return false;

		$sFile = $sLogPath."deploy/".$aDeploy['EXECUTION_ID'];

		if(!is_file($sFile))
			return false;


		//TODO s'occuper d'afficher les erreur
		$sError = "";
		
		$sRawLogs = file_get_contents($sFile);
		$aRawLogs = explode("\n", (string)$sRawLogs);
        $sErrors = nl2br((string)$sError, true);

        $aLogs = array();
        $iSum = 0;
        $iLastTs = 0;
        foreach ($aRawLogs as $sLine) {
            if ( ! empty($sLine)) {
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
	
	//public function action_get_logs ($sProject, $sEnv, $sExecutionId)
	public function action_get_logs ()
	{
		$iDeployQueueId = Input::post('DEPLOY_QUEUE_ID');
		if(NULL == $iDeployQueueId) return json_encode('error');

		$aLog = self::getLogs($iDeployQueueId);

		if(false === $aLog) return json_encode('wait');

		$sErrors = $aLog[1];
		$aLogs = $aLog[0];
		
		$sInfos = self::formatLogs($aLogs);
		
		if ( ! empty($sErrors)) {
			$sErrors = '<h2 class="failure">Error message:</h3><div class="error_msg">' . "\n" . $sErrors . '</div>' . "\n";
		}
		 $sOut = $sInfos . $sErrors;
         $sOut = str_replace('Execute', '<span class="exec">Execute</span>', $sOut);
         $sOut = str_replace('Check', '<span class="exec">Check</span>', $sOut);
        return json_encode($sOut);
	}

	public static function formatLogs (array $aLogs) {
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
            } else if (preg_match('/^\s*(?:\[DEBUG\]|DEBUG )(.*)$/', $msg, $aM2) === 1) {
                $sClass = 'debug';
                $msg = $aM2[1];
            } else if (preg_match('/^\s*(?:\[OK\]|OK)/', $msg) === 1) {
                $sClass = 'ok';
            } else if (preg_match('/^\s*(?:\[MAILTO\]|MAILTO )(.*)/', $msg, $aM2) === 1) {
                $sClass = 'mail';
                $msg = 'mailto: ' . $aM2[1];
            } else if (preg_match('/^\s*(?:\[WARNING\]|WARNING)(.*)$/', $msg, $aMatches) === 1) {
                $sClass = 'warning';
                if ( ! empty($aMatches[1])) {
                    $iNbWarnings++;
                    $msg = $aMatches[1];
                } else {
                    $msg = "Completed with $iNbWarnings warning" . ($iNbWarnings > 1 ? 's' : '') . '.';
                }
            } else if (preg_match('/^\s*(?:\[ERROR\]|ERROR)/', $msg) === 1) {
                $sClass = 'failure';
            } else if (preg_match('/^\s*Execute( tasks|\s+\'\d+(\.\d+)*_Task[^\']*\'\s+task)/i', $msg) === 1) {
                $sClass = 'task_execute';
            } else if (preg_match('/^\s*Check( tasks|\s+\'\d+(\.\d+)*_Task[^\']*\'\s+task)/i', $msg) === 1) {
                $sClass = 'task_check';
            } else {
                $sClass = 'normal';
            }

            $timestamp = str_replace(' ', ', ', substr($timestamp, 11));
            $text = str_replace('\n', '<br />', $msg);

            if ($aRow['sum'] < 5) {
                $sum = '~0';
            } else if ($aRow['sum'] < 10*100) {
                $sum = number_format($aRow['sum']/100, 1, '.', ' ') . 's';
            } else {
                $sum = ($aRow['sum']>=60*100 ? floor($aRow['sum']/100/60) . 'min ' : '') . round(($aRow['sum']%6000)/100) . 's';
            }

            if ($aRow['section'] < 5) {
                $section = '~0';
            } else if ($aRow['section'] < 10*100) {
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
}
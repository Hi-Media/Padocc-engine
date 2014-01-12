<?php

class Controller_Deployment extends Controller
{

	private $oDb;
	private $oSsh;

	public function __construct()
	{
		$this->oSsh = new \Twenga\Ssh(DEPLOYMENT_AAI_SSH_SERVER, DEPLOYMENT_AAI_SSH_LOGIN, DEPLOYMENT_AAI_SSH_PASSWORD);
	}

	public function action_index()
	{
		$view = View_Smarty::forge('dashboard/deployment.tpl');
		global $oUser;
		$oDeployement = new Deployment($this->oSsh);
		$jsonProjectsEnvsList = $oDeployement->getProjectsEnvsList();
		//$jsonProjectsEnvsList = $this->_filterProjectEnvList($jsonProjectsEnvsList, $oUser->getEmail());
		$jsonProjectsEnvsList = $this->_filterProjectEnvList($jsonProjectsEnvsList, "tony.caron@twenga.com");



		$view->set('aProjectsEnvsList', $jsonProjectsEnvsList, false);
		$view->set('sInstigatorEmail', "tony.caron@twenga.com");
		//$this->oSmarty->assign('sInstigatorEmail', $oUser->getEmail());

		return Response::forge($view);

	}

	public function xdoAddDemand($sInstigatorEmail, $sProject, $sEnv, array $aExternalProperties=array()) {



		$sProject = Input::get('Project');
		$sEnv = Input::get('ProjectEnv');
		$aExternalProperties = Input::get('ProjectEnv');

// TODO
		/*$aParams = array();
$aAdditionalParameters = array();
foreach ($_GET as $sKey => $sParam) {
	if ($sKey!='action' && $sKey != 'm') {
		if (strpos($sKey, 'addparam_') === 0) {
			$aAdditionalParameters[substr($sKey, strlen('addparam_'))] = $sParam;
		} else {
			$aParams[] = $sParam;
		}
	}
}
if (count($aAdditionalParameters) > 0) {
	$aParams[] = $aAdditionalParameters;
}*/

		Cookie::set('last_deployed_project', $sProject);
		Cookie::set('dashboard_last_selected_project', $sProject);



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







		$oDeployement = new Deployment($this->oSsh);
		try {
			$sConfig = $oDeployement->addDemand($sInstigatorEmail, $sProject, $sEnv, $aExternalProperties);
			header('Location: /?action=queue&mode=add&project=' . $sProject . '&env=' . $sEnv);
			exit;
		} catch(\Exception $Ex) {
			// TODO
			$this->oSmarty->assign('sStatus', 'Error');
			$this->oSmarty->assign('sProject', $sProject);
			$this->oSmarty->assign('sEnv', $sEnv);
			$this->aSmarty[] = 'deployment/confirmation.tpl';
		}
	}

/*
function addDemand () {
    checkScriptCalled
    checkDemand $SCRIPT_PARAMETERS
    local date="$(date +'%Y-%m-%d %H:%M:%S')"

    local parameters=$(convertList2CSV $SCRIPT_PARAMETERS)
    SUPERVISOR_ID=$(execQuery "\
        INSERT INTO SUPERVISOR_DEMAND ( \
            DATE_INSERT, SCRIPT_NAME, PARAMETERS, INSTIGATOR_EMAIL, SUPERVISOR_DEMAND_STATUS_ID \
        ) VALUES('$date', '$SCRIPT_NAME', '$parameters', '$INSTIGATOR_EMAIL', $SUPERVISOR_STATUS_WAITING); \
        SELECT LAST_INSERT_ID()")
}*/



	/**
	 * Permissions de déploiement en fonction de l'adresse email (hors suffixe '@twenga.com').
	 * Structure de $DEPLOYMENT_AAI_PROJECTS_ACL : array(
	 *     '*' ou 'email' => array(
	 *         'project1' => array('*', 'env1', ...),
	 *         ...
	 *     ),
	 *     ...
	 * )
	 */
	private function _filterProjectEnvList ($jsonProjectsEnvsList, $sUserEmail) {
		$ACL = $GLOBALS['DEPLOYMENT_AAI_PROJECTS_ACL'];
		$aFullProjectsEnvsList = json_decode($jsonProjectsEnvsList, true);
		$sShortEmail = substr($sUserEmail, 0, strpos($sUserEmail, '@'));

		$aProjectsEnvsList = array();
		foreach ($aFullProjectsEnvsList as $sProject => $aEnvs) {
			$aAuthEnvs = array();

			// Gestion des permissions communes :
			if (isset($ACL['*'])) {
				if (isset($ACL['*']['*'])) {
					if (in_array('*', $ACL['*']['*'])) {
						$aAuthEnvs = array('*');
					} else {
						$aAuthEnvs = $ACL['*']['*'];
					}
				} elseif (isset($ACL['*'][$sProject])) {
					$aAuthEnvs = $ACL['*'][$sProject];
				}
			}

			// Gestion des permissions spécifiques à l'utilisateur :
			if (isset($ACL[$sShortEmail])) {
				if (isset($ACL[$sShortEmail]['*'])) {
					if (in_array('*', $ACL[$sShortEmail]['*'])) {
						$aAuthEnvs = array_merge($aAuthEnvs, array('*'));
					} else {
						$aAuthEnvs = array_merge($aAuthEnvs, $ACL[$sShortEmail]['*']);
					}
				} elseif (isset($ACL[$sShortEmail][$sProject])) {
					$aAuthEnvs = array_merge($aAuthEnvs, $ACL[$sShortEmail][$sProject]);
				}
			}

			// Filtrage en fonction des permissions générales et spécifiques :
			$bIsWithoutAnyEnv = true;
			$aFilteredEnvs = array();
			foreach ($aEnvs as $sEnv => $aData) {
				if (in_array('*', $aAuthEnvs) || in_array($sEnv, $aAuthEnvs)) {
					$aFilteredEnvs[$sEnv] = $aData;
					$bIsWithoutAnyEnv = false;
				} else {
					$aFilteredEnvs['DISABLED_' . $sEnv] = array();
				}
			}
			if ($bIsWithoutAnyEnv) {
				$aProjectsEnvsList['DISABLED_' . $sProject] = array();
			} else {
				$aProjectsEnvsList[$sProject] = $aFilteredEnvs;
			}
		}

		return json_encode($aProjectsEnvsList);
	}

	public function action_GetConfig($sProjectName)
	{
		$oDeployement = new Deployment($this->oSsh);
		try
		{
			$sConfig = $oDeployement->getProjectConfig($sProjectName);
			header("content-type: application/xml");
			echo $sConfig;
		}
		catch(\Exception $Ex)
		{
			header("HTTP/1.1 404 Not found");
			header("Status: 404 Not Found");
			echo $Ex->getMessage();
		}

		exit;
	}

	public function action_getlogs()
	{
		$sProject = Input::get("p");
		$sEnv = Input::get("e");
		$sStartDate = Input::get("sd");
		$sExecutionId =Input::get("id");
		$oDeployement = new Deployment($this->oSsh);

		list($aLogs, $sErrors) = $oDeployement->getLogs($sExecutionId);
		$sInfos = $oDeployement->formatLogs($aLogs);
		if (! empty($sErrors)) {
			$sErrors = '<h2 class="failure">Error message:</h3><div class="error_msg">' . "\n" . $sErrors . '</div>' . "\n";
		}
		$sOut = $sInfos . $sErrors;
		echo $sOut;

		return Response::forge();
	}

	public function doGetForm($sProject, $sEnv) {
		$this->oSmarty->assign('sProject', $sProject);
		$this->oSmarty->assign('sEnv', $sEnv);
		$this->oSmarty->display('deployment/form.tpl');
		exit;
	}

	public function doAddDemand($sInstigatorEmail, $sProject, $sEnv, array $aExternalProperties=array()) {
		$oDeployement = new Deployment($this->oSsh);
		try {
			$sConfig = $oDeployement->addDemand($sInstigatorEmail, $sProject, $sEnv, $aExternalProperties);
			header('Location: /?action=queue&mode=add&project=' . $sProject . '&env=' . $sEnv);
			exit;
		} catch(\Exception $Ex) {
			// TODO
			$this->oSmarty->assign('sStatus', 'Error');
			$this->oSmarty->assign('sProject', $sProject);
			$this->oSmarty->assign('sEnv', $sEnv);
			$this->aSmarty[] = 'deployment/confirmation.tpl';
		}
	}
}


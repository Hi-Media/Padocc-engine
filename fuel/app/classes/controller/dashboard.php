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

use \Model\DeployQueue;
use \Model\Project;

class Controller_Dashboard extends Controller
{

	/**
	 * The basic welcome message
	 * 
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$view = View_Smarty::forge('dashboard.tpl');

		$aProjectListByGroup = Project::listingByGroup();
		$aProjectGroupList = array();
		foreach($aProjectListByGroup as $aProject)
		{
			$aProjectGroupList[$aProject['GROUP']][] = $aProject;
		}		

		
		$view->set('aProjectGroupList', $aProjectGroupList);

		$aDeployQueue = DeployQueue::listing();

		$view->set('aDeployQueue', $aDeployQueue);

		return Response::forge($view);
	}

	public static function action_get_queue()
	{
		$iProjectId = Input::post('PROJECT_ID');
		if(NULL !==  $iProjectId && "NULL" != $iProjectId)
			$aDeployQueue = DeployQueue::listing($iProjectId);
		else
			$aDeployQueue = DeployQueue::listing();
		
		$aReturn = array();	

		foreach ($aDeployQueue as $key => $v) 
		{
			$aReturn["aaData"][] = array(
				$v["NAME"],
				$v["REVISION"],
				$v["ENVIRONMENT"],
				$v["EXTERNAL_PROPERTY"],
				$v["FIRSTNAME"]." ".$v["LASTNAME"],
				$v["DATE_START"],
				$v["DATE_END"],
				0
			);
		}

		return json_encode($aReturn);
	}

	public static function action_get_graph()
	{
		$iProjectId = Input::post('PROJECT_ID');
		if(NULL !==  $iProjectId && "NULL" != $iProjectId)
		{
			$aStat = DeployQueue::statByProject($iProjectId);
			$sDbRowOrder = "ENVIRONMENT";
		}
		else
		{
			$aStat = DeployQueue::stat();
			$sDbRowOrder = "NAME";
		}
		$iNbEntries = count($aStat);

		if(!$iNbEntries) return json_encode(false);
		
		$aReturn = $aTmp = array();

		// GET MIN & MAX DATE + INTERVAL
		$iMinDate  = Date::create_from_string($aStat[0]['DATE'], 'mysql')->get_timestamp();
		$iMaxDate = $iNbEntries>1 ? (Date::create_from_string($aStat[$iNbEntries-1]['DATE'], 'mysql')->get_timestamp())  : $iMinDate;
		$oMinDate = date_create(Date::forge($iMinDate)->format("%Y-%m-01"));
		$oMaxDate = date_create(Date::forge($iMaxDate)->format("%Y-%m-01"));
		$interval = $oMinDate->diff($oMaxDate);
		$iNbMonth = ($interval->y * 12) + $interval->m; 

		$aMonth = array();
		$aMonth[$oMinDate->format('Ym')] = 0;
		$aResult["categories"][] = $oMinDate->format('F Y');
		for($i=0; $i<$iNbMonth; $i++)
		{
			$oDate = $oMinDate->add(new DateInterval('P1M'));
			$aMonth[$oDate->format('Ym')] = 0;
			$aResult["categories"][] = $oMinDate->format('F Y');			
		}	 
	
		foreach($aStat as $k=>$v)
		{
			if(!isset($aResult["series"][$v[$sDbRowOrder]]["data"]))
				$aResult["series"][$v[$sDbRowOrder]]["data"] = $aMonth;

			$aResult["series"][$v[$sDbRowOrder]]["name"] = $v[$sDbRowOrder];
			$aResult["series"][$v[$sDbRowOrder]]["data"][$v["YM"]]=(int)$v["NB"];

			$aTmp[] = &$aResult["series"][$v[$sDbRowOrder]]["data"];
		}

		foreach($aTmp as $k => $v)
		{		
			ksort($aTmp[$k]);
			$aTmp[$k] = array_values($aTmp[$k]);
		}

		$aResult['series'] = array_values($aResult['series']);
		return json_encode($aResult);
	}

}

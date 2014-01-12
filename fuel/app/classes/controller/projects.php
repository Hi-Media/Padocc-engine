<?php

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @extends  Controller
 */

use \Model\Project;
use \Model\Configuration;
use \Model\User;
class Controller_Projects extends Controller
{



	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$view = View_Smarty::forge('dashboard/projects.tpl');

		// TODO REMOVE
		User::login("tony.caron@twenga.com");

		$aUserList = User::listing();
		$aProjectGroup = Project::listingGroup();
		$view->set('aUserList', $aUserList);
		$view->set('aProjectGroup', $aProjectGroup);
		$view->set('USER_ID', User::getLoggedUserId());


		return Response::forge($view);
	}

	public function action_list()
	{
		$view = View_Smarty::forge('project/list.tpl');
		return Response::forge($view);
	}

	public function action_add()
	{
		$iCreatorId = User::getLoggedUserId();
		$iOwnerId = Input::post('USER_ID');
		$sProjectName = Input::post('PROJECT_NAME');
		$sProjectGroup = Input::post('PROJECT_GROUP');
		$sConfiguration = Input::post('PROJECT_CONFIGURATION');

		$val = Validation::forge();
		$val->add('USER_ID', 'Your username')->add_rule('required');
		$val->add('PROJECT_NAME', 'Your username')->add_rule('required');
		$val->add('PROJECT_GROUP', 'Your username')->add_rule('required');
		$val->add('PROJECT_CONFIGURATION', 'Your username')->add_rule('required');
		if (!$val->run()) die;

		if(Project::exist($sProjectName) === true) die;

		$iProjectId = Project::add($sProjectName, $sProjectGroup, $iOwnerId, $iCreatorId);

		Configuration::add($iProjectId, $iCreatorId, $sConfiguration);



		Message::addAir('The project '.$sProjectName.' is born !');

		return Response::redirect('projects');
	}

	public function action_project_exist()
	{
		$sProjectName = Input::post('PROJECT_NAME');
		$iProjectExist = Project::exist($sProjectName);
		echo json_encode(!$iProjectExist);
		return Response::forge();
	}

	public function action_configuration_valid()
	{
		try
		{
			Configuration::checkXmlConfiguration(Input::post('PROJECT_CONFIGURATION'));
		}
		catch (\Exception $e)
		{
			return ''.json_encode($e->getMessage()).'';
		}

		return json_encode(true);
	}



}

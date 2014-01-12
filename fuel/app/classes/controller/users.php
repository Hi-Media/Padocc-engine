<?php

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @extends  Controller
 */

use \Model\User;
class Controller_Users extends Controller
{



	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$view = View_Smarty::forge('users.tpl');

		return Response::forge($view);
	}

	public function action_add()
	{
		$sFirstName = Input::post('FIRSTNAME');
		$sLastName = Input::post('LASTNAME');
		$sEmail = Input::post('EMAIL');

		$val = Validation::forge();
		$val->add('FIRSTNAME', 'Your username')->add_rule('required');
		$val->add('LASTNAME', 'Your username')->add_rule('required');
		$val->add('EMAIL', 'Your username')->add_rule('valid_email');
		if (!$val->run()) die;

		if(Users::exist($sEmail) === true) return Response::redirect('welcome/404');

		$iUserId = Users::add($sFirstName, $sLastName, $sEmail);

		Message::addAir($sFirstName.' is born !');

		return Response::redirect('users');

	}

	public function action_email_exist()
	{

		$sEmail = Input::post('EMAIL');
		$iUserExist = Users::exist($sEmail);die;
		//echo json_encode(!$iUserExist);
		return Response::redirect('users');
	}




}

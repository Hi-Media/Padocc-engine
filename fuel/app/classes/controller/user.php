<?php

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @extends  Controller
 */

class Controller_User extends Controller
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
		if (!$val->run()) return json_encode(false);

		if(\Model\User::exist($sEmail) === true) return json_encode(false);

		$iUserId = \Model\User::add($sFirstName, $sLastName, $sEmail);

		//Message::addAir($sFirstName.' is born !');

		return json_encode(true);

	}

	public  function action_email_exist()
	{
		$sEmail = Input::post('EMAIL');
		$iUserExist = \Model\User::exist($sEmail);
		return json_encode(!$iUserExist);
		//return new Response();
	}




}

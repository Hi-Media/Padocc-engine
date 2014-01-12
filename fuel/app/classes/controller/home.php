<?php

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @extends  Controller
 */

class Controller_Home extends Controller
{



	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		$view = View_Smarty::forge('home.tpl');
		return Response::forge($view);
	}

}

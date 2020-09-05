<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Countries;
use Models\Tokens;
use Models\Users;
use Models\Cache;

class AuthController extends \Phalcon\Mvc\Controller
{
	public function signinAction()
	{
		if($this->auth->getData())
		{
			header("Location: "._PANEL_ROOT_."/");
			exit;
		}

		$this->view->partial("auth/login");
		exit;
	}

	public function ajaxAction()
	{
		header('Content-type: text/json');
		if($this->auth->getData())
		{
			$response = ["status" => "success"];
		}
		else
		{
			$response = ["status" => "error", "description" => (string)$this->auth->getError()];
		}
		exit(json_encode($response, true));
	}
}
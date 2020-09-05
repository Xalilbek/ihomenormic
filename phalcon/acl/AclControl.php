<?php
use \Phalcon\Events\Event;
use \Phalcon\Mvc\Dispatcher;

use Models\LogsPanelAccess;
use Lib\MyMongo;

class AclControl extends \Phalcon\Mvc\User\Component
{
	protected $_module;

	public function __construct($module)
	{
		$this->_module = $module;
	}

	public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
	{
        //echo $resource = $this->_module . '-' . $dispatcher->getControllerName(), PHP_EOL; // frontend-dashboard
		//echo $access = $dispatcher->getActionName();

		/**
		$vars               = $_REQUEST;
		unset($vars["_url"]);

		$insert = [
			//"user_id"       => (int)@Users::$data->id,
			"url"           => @$_SERVER["REQUEST_URI"],
			"ip"            => @$_SERVER["REMOTE_ADDR"],
			"browser"       => @$_SERVER["HTTP_USER_AGENT"],
			"variables"     => strlen(json_encode($vars, true)) > 1000 ? substr(json_encode($vars, true),0,1000): $vars,
			"created_at"    => MyMongo::getDate(),
		];
		LogsPanelAccess::insert($insert); */

		$this->view->setVar("controller", $dispatcher->getControllerName());
		$this->view->setVar("action", $dispatcher->getActionName());
	}
}
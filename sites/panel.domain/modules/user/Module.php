<?php

namespace Multiple;

use Lib\AuthPanel;
use Lib\Lib;
use Lib\MyMongo;
use Lib\Parameters;
use Lib\Translation;
use Lib\Permission;

class Module
{
	public function registerAutoloaders()
	{
		$loader = new \Phalcon\Loader();

		$loader->registerNamespaces(array(
			'Controllers' => '../modules/user/controllers/',
		));

		$loader->register();
	}

	public function registerServices($di)
	{
		$di->set('permission', function ()
		{
			$class = new Permission();
			return $class;
		});

		$di->set('dispatcher', function () {
			$dispatcher = new \Phalcon\Mvc\Dispatcher();

			//Attach a event listener to the dispatcher
			$eventManager = new \Phalcon\Events\Manager();
			$eventManager->attach('dispatch', new \AclPanel('user'));

			$dispatcher->setEventsManager($eventManager);
			$dispatcher->setDefaultNamespace("Controllers");
			return $dispatcher;
		});

		$di->set('view', function () {
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir('../modules/user/views/');
			return $view;
		});

	}

}
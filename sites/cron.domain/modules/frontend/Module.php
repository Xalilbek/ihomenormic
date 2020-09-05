<?php

namespace Multiple;

use Lib\Lib;
use Lib\Parameters;
use Lib\Translation;

class Module
{
	public function registerAutoloaders()
	{
		$loader = new \Phalcon\Loader();

		$loader->registerNamespaces(array(
			'Controllers' => '../modules/frontend/controllers/',
		));

		$loader->register();
	}

	public function registerServices($di)
	{
		$di->set('lang', function ()
		{
			$translation = new Translation();
			$translation->init(2);
			return $translation;
		});

		$di->set('lib', function ()
		{
			$class = new Lib();
			return $class;
		});

		$di->set('parameters', function ()
		{
			$class = new Parameters();
			return $class;
		});

		$di->set('dispatcher', function () {
			$dispatcher = new \Phalcon\Mvc\Dispatcher();

			//Attach a event listener to the dispatcher
			$eventManager = new \Phalcon\Events\Manager();
			$eventManager->attach('dispatch', new \AclControl('panel'));

			$dispatcher->setEventsManager($eventManager);
			$dispatcher->setDefaultNamespace("Controllers");
			return $dispatcher;
		});

		$di->set('view', function () {
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir('../modules/frontend/views/');
			return $view;
		});

	}

}
<?php
error_reporting(0);
ini_set("display_errors", "1");

define("_ROOT_", "");
require '../../settings.php';

class Application extends \Phalcon\Mvc\Application
{
	protected function _registerServices()
	{
        $di = new \Phalcon\DI\FactoryDefault();

		$loader = new \Phalcon\Loader();

        $loader->registerDirs(
            [
                __DIR__ . '/../../../phalcon/acl/'
            ]
        );

        $loader->registerNamespaces(array(
            'Phalcon' => '/home/nav/incubator/Library/Phalcon/',
            'Models' 	=> '../../../phalcon/models/',
            'Lib' 		=> '../../../phalcon/library/',
        ))->register();

        define('DEBUG', false);
        if(DEBUG)
        {
            error_reporting(1);
            (new Phalcon\Debug)->listen();
        }

		$di->set('mymongo', function ()
		{
			$mongo = new \Lib\MyMongo();
			return $mongo;
		}, true);

		$di->set('router', function()
        {

			$router = new \Phalcon\Mvc\Router();

			$router->setDefaultModule("frontend");

			$router->add('/:controller/:action/:int', array(
				'module' 		=> 'frontend',
				'controller' 	=> 1,
				'action' 		=> 2,
				'id' 			=> 3,
			));

			$router->add('/:controller/:action', [
				'module' => 'frontend',
				'controller' => 1,
				'action' => 2,
			]);

			$router->add('/:controller', [
				'module' => 'frontend',
				'controller' => 1,
				'action' => 2,
			]);

			return $router;

		});

		$this->setDI($di);
	}

	public function main()
	{
		$this->_registerServices();

		$this->registerModules(array(
			'frontend' => array(
				'className' => 'Multiple\Module',
				'path' => '../modules/frontend/Module.php'
			),
		));

		echo $this->handle()->getContent();
	}
}

$application = new Application();
$application->main();

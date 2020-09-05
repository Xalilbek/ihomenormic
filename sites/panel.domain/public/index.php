<?php
error_reporting(0);
ini_set("display_errors", "1");

require '../../settings.php';

$userScheme = json_decode($_SERVER["HTTP_CF_VISITOR"], true);
if(@$userScheme["scheme"] == "http"){
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit;
}

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

        define('DEBUG', (int)@$_SERVER["HTTP_IS_ADMIN"] == 1 ? true : false);
        if(DEBUG)
        {
            error_reporting(1);
            (new Phalcon\Debug)->listen();
        }

		$di->set('lang', function ()
		{
			$translation = new \Lib\Translation();
			$translation->init(3);
			return $translation;
		});

		$di->set('lib', function ()
		{
			$class = new \Lib\Lib();
			return $class;
		});

		$di->set('auth', function ()
		{
			$class = new \Lib\AuthPanel();
			return $class;
		});

		$di->set('mymongo', function ()
		{
			$class = new \Lib\MyMongo();
			return $class;
		});

		$di->set('parameters', function ()
		{
			$class = new \Lib\Parameters();
			return $class;
		});

		$di->set('logger', function ()
		{
			$class = new \Lib\Logger();
			return $class;
		});

		$this->auth->init($this->request, $this->lang, $this->lib);

		if($this->auth->getData()){
			define("DEFAULT_MODULE", ($this->auth->getData()->type == "moderator" ? "admin": "user"));
		}else{
			define("DEFAULT_MODULE", "admin");
		}

		$di->set('router', function()
        {

			$router = new \Phalcon\Mvc\Router();

			$router->setDefaultModule(DEFAULT_MODULE);

			$router->add('/:controller/:action/:int', array(
				'controller' 	=> 1,
				'action' 		=> 2,
				'id' 			=> 3,
			));

			$router->add('/:controller/:action', array(
				'controller' => 1,
				'action' => 2,
			));

			$router->add('/:controller', array(
				'controller' => 1,
				'action' => 2,
			));

			$router->add('/:controller/:int', array(
				'controller' 	=> 1,
				'action' 		=> 'index',
				'id' 			=> 2,
			));

			return $router;

		});

		$this->setDI($di);
	}

	public function main()
	{
		$this->_registerServices();

		$this->registerModules(array(
			'admin' => array(
				'className' => 'Multiple\Module',
				'path' => '../modules/admin/Module.php'
			),
			'user' => array(
				'className' => 'Multiple\Module',
				'path' => '../modules/user/Module.php'
			),
		));

		echo $this->handle()->getContent();
	}
}

$application = new Application();
$application->main();

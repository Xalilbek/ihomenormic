<?php
namespace Controllers;

class DocsController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->partial("docs/index");
		exit;
	}
}
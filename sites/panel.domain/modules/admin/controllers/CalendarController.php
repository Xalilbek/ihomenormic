<?php
namespace Controllers;

use Models\Cache;
use Models\Todo;
use Models\Users;

class CalendarController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$currentDateTime = $this->mymongo->getDate(strtotime(date("Y-m-d 00:00:00")));

		$bind = [
			"date" => [
				'$gte' => $currentDateTime,
				//'$lte' => $this->mymongo->getDate(time()+30*24*3600)
			],
			"is_deleted" => 0
		];
		if($this->request->get("case_id") > 0)
			$bind["case"] = (int)$this->request->get("case_id");
		if($this->request->get("employee") > 0)
			$bind["employee"] = (int)$this->request->get("employee");
		if((int)$this->request->get("target") == 1)
			$bind["moderator"] = (int)$this->auth->getData()->id;
		if((int)$this->request->get("target") == 2)
			$bind["employee"] = [
				'$gt' => 0
			];

		$todoEvents = Todo::find([
			$bind,
			"sort"		=> [
				"_id"	=> 1
			],
			"limit"		=> 100,
		]);




		$this->view->setVar("todoEvents", $todoEvents);
		$this->view->setVar("todoData", $todoData);
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]));
	}



}
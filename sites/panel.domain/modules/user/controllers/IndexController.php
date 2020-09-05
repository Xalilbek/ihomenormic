<?php
namespace Controllers;

use Models\Cache;
use Models\Cases;
use Models\Parameters;
use Models\TimeRecords;
use Models\Todo;
use Models\Users;

class IndexController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$binds = [];
		$binds["is_deleted"] = 0;

		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

		$timeRecords = TimeRecords::find([
			$binds,
			"sort"=> ["_id"	=> -1],
			"limit"	=> 100,
		]);


		$currentDateTime = $this->mymongo->getDate(time() - 30 * 24 * 3600);

		$bind = [
			"date" => ['$gte' => $currentDateTime,],
			"is_deleted" => 0
		];


		if($this->auth->getData()->type == "citizen")
		{
			$bind["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$bind["employee"] = (int)$this->auth->getData()->id;
		}

		$todoEvents = Todo::find([
			$bind,
			"sort"		=> [
				"date_deadline"	=> -1
			],
			"limit"		=> 500,
		]);

		//echo "<pre>";var_dump($bind);exit;

		$employeesQuery = Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]);
		$employees = [];
		foreach($employeesQuery as $value)
				$employees[(int)$value->id] = $value;


		$calendarStatuses = $this->parameters->getList($this->lang, "calendar_statuses", [], true);

		$this->view->setVar("todoEvents", $todoEvents);
		$this->view->setVar("calendarStatuses", $calendarStatuses);
		$this->view->setVar("employees", $employees);
		$this->view->setVar("timeRecords", $timeRecords);
		$this->view->setVar("todoCats", $this->parameters->getList($this->lang, "todo_categories", [], true));
	}

	public function logoutAction()
	{
		setcookie("id", 0, time()-365*24*3600, "/");
		setcookie("pw", 0, time()-365*24*3600, "/");
		header("location: "._PANEL_ROOT_."/auth/signin");
		exit;
	}

	public function addAction()
	{
		$error 			= false;
		$success 		= false;

		$type 			= strtolower($this->request->get("type")) == "event" ? "event": "todo";
		$title 			= trim($this->request->get("title"));
		$lead 			= trim($this->request->get("lead"));
		$time_start 	= trim($this->request->get("time_start"));
		$time_end 		= trim($this->request->get("time_end"));
		$category		= (int)$this->request->get("category");
		$status			= (int)$this->request->get("status");

		$date 			= trim($this->request->get("date"));
		$dateDeadline 	= trim($this->request->get("date_deadline"));

		$datetime			= $this->lib->dateFomDanish($date)." ".(strlen($time_start) > 0 ? $time_start.":00": "00:00:00");
		$deadlinedatetime	= $this->lib->dateFomDanish($dateDeadline)." ".(strlen($time_end) > 0 ? $time_end.":00": "00:00:00");


		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (!strtotime($datetime))
			{
				$error = $this->lang->get("DateTimeError", "Date or Time is wrong");
			}
			elseif (strlen($date) > 0 && !strtotime($deadlinedatetime))
			{
				$error = $this->lang->get("DateTimeError", "Date or Time is wrong");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError");
			}
			else
			{
				$moderators = [];
				foreach($this->request->get("moderator") as $value)
					$moderators[] = (int)$value;

				$employees = [];
				foreach($this->request->get("employee") as $value)
					$employees[] = (int)$value;

				$citizens = [];
				foreach($this->request->get("citizen") as $value)
					$citizens[] = (int)$value;

				if($this->auth->getData()->type == "citizen")
				{
					$citizens[] = (int)$this->auth->getData()->id;
				}
				else
				{
					$employees[] = (int)$this->auth->getData()->id;
				}

				$lead = [];
				foreach($this->request->get("lead") as $value)
					$lead[] = (int)$value;

				$userInsert = [
					"id"							=> Todo::getNewId(),
					"creator_id"					=> (int)$this->auth->getData()->id,
					"type"							=> $type,
					"title"							=> $title,
					"category"						=> $category,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"case"							=> (int)$this->request->get("case"),
					"moderator"						=> count($moderators) > 0 ? $moderators: 0,
					"citizen"						=> count($citizens) > 0 ? $citizens: 0,
					"employee"						=> count($employees) > 0 ? $employees: 0,
					"lead"							=> count($lead) > 0 ? $lead: 0,
					"date"							=> $this->mymongo->getDate(strtotime($datetime)),
					"date_deadline"					=> $this->mymongo->getDate(strtotime($deadlinedatetime)),
					"status"						=> $status,
					"is_deleted"					=> 0,
					"created_at"					=> $this->mymongo->getDate()
				];

				Todo::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));


		$binds = [];
		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

		$binds["is_deleted"] = 0;

		$data =  Cases::find([
			$binds,
			"limit"     => 100,
			"skip"      => 0,
		]);
		$citizenIds = [];
		$employeeIds = [];
		$contact_persons = [];
		foreach($data as $value){
			$citizenIds[] 	= (int)$value->citizen[0];
			$employeeIds[] 	= (int)$value->employee[0];
			$contact_persons[] 	= (int)$value->contact_person[0];
			if((int)$value->contact_person[1] > 0)
				$contact_persons[] 	= (int)$value->contact_person[1];
		}


		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));
		$this->view->setVar("citizens", Users::find([["id" => ['$in' => $citizenIds], "type" => "citizen", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("employees", Users::find([["id" => ['$in' => $employeeIds], "type" => "employee", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("contactPersons", Users::find([["id" => ['$in' => $contact_persons], "type" => "partner", "is_deleted" => ['$ne' => 1]]]));
	}


	public function editAction($id)
	{
		$data 			= Todo::getById($id);
		$error 			= false;
		$success 		= false;

		$type 			= strtolower($this->request->get("type")) == "event" ? "event": "todo";
		$title 			= trim($this->request->get("title"));
		$lead 			= trim($this->request->get("lead"));
		$category		= (int)$this->request->get("category");
		$status			= (int)$this->request->get("status");

		$time_start 	= trim($this->request->get("time_start"));
		$time_end 		= trim($this->request->get("time_end"));

		$date 			= trim($this->request->get("date"));
		$dateDeadline 	= trim($this->request->get("date_deadline"));

		$datetime			= $this->lib->dateFomDanish($date)." ".(strlen($time_start) > 0 ? $time_start.":00": "00:00:00");
		$deadlinedatetime	= $this->lib->dateFomDanish($dateDeadline)." ".(strlen($time_end) > 0 ? $time_end.":00": "00:00:00");

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError");
			}
			elseif ((int)$data->creator_id !== $this->auth->getData()->id)
			{
				$error = $this->lang->get("YouDontHavePermission", "You dont have permission to edit this todo");
			}
			else
			{
				$moderators = [];
				foreach($this->request->get("moderator") as $value)
					$moderators[] = (int)$value;

				$employees = [];
				foreach($this->request->get("employee") as $value)
					$moderators[] = (int)$value;

				$citizens = [];
				foreach($this->request->get("citizen") as $value)
					$citizens[] = (int)$value;

				$lead = [];
				foreach($this->request->get("lead") as $value)
					$lead[] = (int)$value;

				$update = [
					"type"							=> $type,
					"title"							=> $title,
					"category"						=> $category,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"case"							=> (int)$this->request->get("case"),
					"moderator"						=> $moderators,
					//"citizen"						=> $citizens,
					//"employee"						=> $employees,
					"lead"							=> count($lead) > 0 ? $lead: 0,
					"date"							=> $this->mymongo->getDate(strtotime($datetime)),
					"date_deadline"					=> $this->mymongo->getDate(strtotime($deadlinedatetime)),
					"status"						=> $status,
					"updated_at"					=> $this->mymongo->getDate()
				];

				if($this->auth->getData()->type == "citizen")
				{
					$binds["employee"] = $employees;
				}
				else
				{
					$binds["citizen"] = $citizens;
				}

				Todo::update(["id" => (int)$id], $update);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));


		$binds = [];
		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

		$binds["is_deleted"] = 0;

		$data =  Cases::find([
			$binds,
			"limit"     => 100,
			"skip"      => 0,
		]);
		$citizenIds = [];
		$employeeIds = [];
		$contact_persons = [];
		foreach($data as $value){
			$citizenIds[] 	= (int)$value->citizen[0];
			$employeeIds[] 	= (int)$value->employee[0];
			$contact_persons[] 	= (int)$value->contact_person[0];
			if((int)$value->contact_person[1] > 0)
				$contact_persons[] 	= (int)$value->contact_person[1];
		}


		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));
		$this->view->setVar("citizens", Users::find([["id" => ['$in' => $citizenIds], "type" => "citizen", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("employees", Users::find([["id" => ['$in' => $employeeIds], "type" => "employee", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("contactPersons", Users::find([["id" => ['$in' => $contact_persons], "type" => "partner", "is_deleted" => ['$ne' => 1]]]));
	}





	public function deleteAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Todo::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif ($this->mymongo->toSeconds($data->date) < time())
		{
			$error = $this->lang->get("YouDontHavePermission", "You dont have permission");
		}
		elseif((int)$this->request->get("verify") == 1)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> 0,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Todo::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
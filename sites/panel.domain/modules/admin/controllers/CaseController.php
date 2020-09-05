<?php
namespace Controllers;

use Models\Activities;
use Models\Cache;
use Models\CaseLogs;
use Models\CasePlanQuestions;
use Models\CasePlans;
use Models\CaseQuestions;
use Models\CaseReports;
use Models\Cases;
use Models\Contacts;
use Models\DialogueUsers;
use Models\DocumentFolders;
use Models\Documents;
use Models\Employee;
use Models\NoteFolders;
use Models\Notes;
use Models\Partner;
use Models\TempFiles;
use Models\TimeRecords;
use Models\Todo;
use Models\Transactions;
use Models\Users;

class CaseController extends \Phalcon\Mvc\Controller
{
	public $data;

	public $user;

	public $employee;

	public $partner;

	public $contactPerson;

	public function initialize()
	{
		$id = $this->dispatcher->getParam("id");


		$data = Cases::findFirst([
			[
				"id"			=> (int)$id,
				//"is_deleted"	=> 0
			]
		]);
		if(!$data)
			header("location: "._PANEL_ROOT_);

		$user = Users::findFirst([
			[
				"id"			=> (int)$data->citizen[0],
				//"is_deleted"	=> 0
			]
		]);

		$employee = Users::findFirst([
			[
				"id"			=> (int)$data->employee[0],
				//"is_deleted"	=> 0
			]
		]);

		$partner = Partner::findFirst([
			[
				"id"			=> (int)$data->partner,
				//"is_deleted"	=> 0
			]
		]);

		$contactPerson = Users::findFirst([
			[
				"id"			=> (int)$data->contact_person[0],
				//"is_deleted"	=> 0
			]
		]);

		$this->employee 		= $employee;
		$this->user 			= $user;
		$this->data 			= $data;
		$this->partner 			= $partner;
		$this->contactPerson 	= $contactPerson;

		$this->view->setVar("user", $user);
		$this->view->setVar("employee", $employee);
		$this->view->setVar("data", $data);
		$this->view->setVar("partner", $partner);
		$this->view->setVar("contactPerson", $contactPerson);
		$this->view->setVar("id", $id);
		$this->view->setVar("reportTypes", Cases::getReportTypes($this->lang));

		//$this->view->setVar("urlTag", "source=case&citizen=".$id."&");
	}

	public function indexAction($id)
	{
		if(!$this->data->start_employee_date)
			$this->data->start_employee_date = $this->data->start_date;
		$durationSecs = $this->mymongo->getUnixtime() - $this->mymongo->toSeconds($this->data->start_date);
		if($durationSecs < 0)
			$durationSecs = 0;

		$duration = $this->lib->durationToStr($durationSecs, $this->lang);

		$startDate = $this->mymongo->dateFormat($this->data->start_date, "d.m.Y");

		$this->view->setVar("startDate", $startDate);
		$this->view->setVar("duration", $duration);



		$timerMonthly = $this->data->meeting_duration;
		if($this->data->meeting_duration_type == "week")
			$timerMonthly = round($this->data->meeting_duration * 52 / 12, 0);
		$spentHours = $this->data->timer_spent/60;

		$this->view->setVar("timerMonthly", $timerMonthly);
		$this->view->setVar("spentHours", $spentHours);


		$currentDateTime = $this->mymongo->getDate(time() - 30 * 24 * 3600);

		$bind = [
			"date" => [
				'$gte' => $currentDateTime,
				//'$lte' => $this->mymongo->getDate(time()+30*24*3600)
			],
			"is_deleted" => 0
		];

		$bind["case"] = (int)$id;

		$todoEvents = Todo::find([
			$bind,
			"sort"		=> [
				"_id"	=> 1
			],
			"limit"		=> 100,
		]);

		$employeesQuery = Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]);
		$employees = [];
		foreach($employeesQuery as $value)
			$employees[(int)$value->id] = $value;


		$calendarStatuses = $this->parameters->getList($this->lang, "calendar_statuses", [], true);

		$this->view->setVar("todoEvents", $todoEvents);
		$this->view->setVar("calendarStatuses", $calendarStatuses);
		$this->view->setVar("employees", $employees);
		$this->view->setVar("todoCats", $this->parameters->getList($this->lang, "todo_categories", [], true));
	}

	public function informationAction($id)
	{
		$title 					= trim($this->request->get("title"));
		$partner_title 			= trim($this->request->get("partner_title"));
		$report_type 			= (int)$this->request->get("report_type");
		$focus_area 			= (int)$this->request->get("focus_area");
		$focus_type 			= (int)$this->request->get("focus_type");
		$report_interval 		= trim($this->request->get("report_interval"));
		$activity_budget 		= (float)$this->lib->danishToFloat($this->request->get("activity_budget"));
		$activity_budget_max 	= (float)$this->lib->danishToFloat($this->request->get("activity_budget_max"));
		$activity_budget_type 	= trim($this->request->get("activity_budget_type"));
		$meeting_duration 		= (float)$this->request->get("meeting_duration");
		$meeting_duration_type 	= (string)$this->request->get("meeting_duration_type") == "month" ? "month": "week";
		$start_date 			= strtotime($this->lib->dateFomDanish(trim($this->request->get("start_date"))));
		$next_service_date 		= strtotime($this->lib->dateFomDanish(trim($this->request->get("next_service_date"))));
		$start_date_report 		= strtotime($this->lib->dateFomDanish(trim($this->request->get("start_date_report"))));
		$start_date_for_act 	= strtotime($this->lib->dateFomDanish(trim($this->request->get("start_date_for_act"))));
		$partner 				= (int)$this->request->get("partner");
		$contact_persons 		= [];
		foreach($this->request->get("contact_person")  as $value)
			$contact_persons[] = (int)$value;

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("obsdjAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif ($focus_area == 0)
			{
				$error = $this->lang->get("FocusAreaError", "Focus area is empty");
			}
			else
			{
				$update = [
					"title"					=> substr($title, 0, 1000),
					"partner_title"			=> substr($partner_title, 0, 1000),
					"report_type"			=> $report_type,
					"focus_area"			=> [$focus_area],
					"focus_type"			=> [$focus_type],
					"report_interval"		=> $report_interval,
					"activity_budget"		=> $activity_budget,
					"activity_budget_max"	=> $activity_budget_max,
					"activity_budget_type"	=> $activity_budget_type,
					"meeting_duration"		=> $meeting_duration,
					"meeting_duration_type"	=> $meeting_duration_type,
					"partner"				=> $partner,
					"start_date"			=> $this->mymongo->getDate($start_date),
					"next_service_date"		=> $this->mymongo->getDate($next_service_date),
					"start_date_for_act"	=> $this->mymongo->getDate($start_date_for_act),
					//"next_report_date"	=> $this->mymongo->getDate($start_date),
					"contact_person"		=> $contact_persons,
					"updated_at"			=> $this->mymongo->getDate()
				];

				if(!$this->data->start_employee_date)
					$update["start_employee_date"] = $this->mymongo->getDate($start_date);

				if(date("Y-m-d", $start_date) !== $this->mymongo->dateFormat($this->data->start_date, "Y-m-d"))
				{
					$lastReportDate = $this->mymongo->toSeconds($this->data->last_report_date);
					if(!$lastReportDate)
						$lastReportDate = $start_date;
					$nextReportDate = Cases::getNextMeetingDate($report_interval, $lastReportDate);
					$update["next_report_date"] = $nextReportDate;
				}
				else if($this->data->report_interval !== $report_interval)
				{
					$lastReportDate = $this->mymongo->toSeconds($this->data->last_report_date);
					if(!$lastReportDate)
						$lastReportDate = $start_date;
					$nextReportDate = Cases::getNextMeetingDate($report_interval, $lastReportDate);
					$update["next_report_date"] = $nextReportDate;
				}


				Cases::update(
					[
						"_id" => $this->data->_id
					],
					$update
				);

				$data = Cases::findFirst([
					[
						"id"			=> (int)$id,
					]
				]);
				$this->data = $data;
				$this->view->setVar("data", $data);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("partners", Partner::find([["is_deleted" => 0]]));
		$this->view->setVar("contactPersons", Users::find([["partner" => (int)$this->data->partner, "type" => "partner", "is_deleted" => 0]]));
	}

	public function newemployeeAction($id)
	{
        $employees	= [];
        foreach ($this->request->get("employees") as $value)
            $employees[] = (int)$value;

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("obsdjAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (count($employees) == 0)
			{
				$error = $this->lang->get("EmployeeError", "You didn't choose employee");
			}
			else
			{
				$update = [
					"employee"					=> $employees,
					"updated_at"				=> $this->mymongo->getDate(),
					"start_employee_date"		=> $this->mymongo->getDate(),
				];

				Cases::update(
					[
						"_id" => $this->data->_id
					],
					$update
				);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$employeeQuery = Users::find([["type" => "employee", "is_deleted" => 0]]);
		$employees = [];
		foreach ($employeeQuery as $value)
            $employees[$value->id] = $value;
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("employees", $employees);
	}

	public function calendarAction($id)
	{
		$todoEvents = Todo::find([["is_deleted" => 0, "case" => (int)$id]]);

		$this->view->setVar("todoEvents", $todoEvents);
	}

	public function oldemployeesAction($id)
	{
		$ids = [];
		$query = CaseLogs::find([["case_id" => (string)$this->data->_id, "type" => "new_employee"]]);
		foreach($query as $value)
		{
			$ids[] = (int)$value->employee_id;
			$ids[] = (int)$value->new_employee_id;
		}

		$oldemployees = [];
		if(count($ids) > 0)
		{
			$oldemployeesQuery = Users::find([["id" => ['$in' => $ids]]]);
			foreach($oldemployeesQuery as $value)
				$oldemployees[$value->id] = $value;
		}

		$this->view->setVar("oldemployees", $oldemployees);
		$this->view->setVar("emplogs", $query);
	}

	public function notesAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function notesaddAction($id)
	{
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		$description 	= trim($this->request->get("description"));
		$folder 		= (int)$this->request->get("folder");
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError", "Title is empty");
			}
			elseif (strlen($description) < 1 || strlen($description) > 20000)
			{
				$error = $this->lang->get("DescriptionError", "Description is empty");
			}
			else
			{
				$userInsert = [
					"id"						=> Notes::getNewId(),
					"creator_id"				=> (int)$this->auth->getData()->id,
					"title"						=> $title,
					"description"				=> $description,
					"folder"					=> $folder,
					"case"						=> (int)$id,
					"citizen"					=> (int)$this->request->get("citizen"),
					"employee"					=> (int)$this->request->get("employee"),
					"active"					=> 1,
					"is_deleted"				=> 0,
					"created_at"				=> $this->mymongo->getDate()
				];

				Notes::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function noteseditAction($id)
	{
		$error 			= false;
		$success 		= false;

		$id 			= (int)$this->request->get("id");

		$data 			= Notes::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$title 			= trim($this->request->get("title"));
		$description 	= trim($this->request->get("description"));
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError", "Title is empty");
			}
			elseif (strlen($description) < 1 || strlen($description) > 20000)
			{
				$error = $this->lang->get("DescriptionError", "Description is empty");
			}
			else
			{
				$update = [
					"title"						=> $title,
					"description"				=> $description,
					"updated_at"				=> $this->mymongo->getDate()
				];
				Notes::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}
		$creator = Users::getById($data->creator_id);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("notedata", $data);
		$this->view->setVar("creator", $creator);
	}

	public function noteslistAction($id)
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $nid){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					Notes::update(
						[
							"id" => (int)$nid
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);
				}else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$binds = [];

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("folder"))
			$binds["folder"] = (int)$this->request->get("folder");

		if ($this->request->get("title"))
			$binds["title"] = [
				'$regex' => trim(strtolower($this->request->get("title"))),
				'$options'  => 'i'
			];

		$binds["case"] 			= (int)$id;
		$binds["is_deleted"] 	= 0;

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["created_at" => $order_sort];
				break;
		}
		$data = Notes::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Notes::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					htmlspecialchars($value->title),
					@$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					'<a href="'._PANEL_ROOT_.'/case/notesedit/'.$id.'?id='.$value->id.'" class=""><i class="la la-edit"></i></a>'
					//'<a href="'._PANEL_ROOT_.'/noteitems/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
				);
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function todoAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function todolistAction($id)
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action")
		{
			foreach($this->request->get("id") as $id)
			{
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					Todo::update(
						[
							"id" => (int)$id
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);
				}else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order = $this->request->get("order");
		$order_column = $order[0]["column"];
		$order_sort = $order[0]["dir"];

		$binds = [];
		$search = true;

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		$binds["case"] 		 = (int)$id;
		$binds["is_deleted"] = 0;

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["_id" => $order_sort];
				break;
		}
		$data = Todo::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Todo::count([
			$binds,
		]);

		$categoryIds = [];
		foreach($data as $value)
			$categoryIds[] = (int)$value->category;
		//$categoryIds = array_unique($categoryIds);
		$categories = count($categoryIds) > 0 ? $this->parameters->getListByIds($this->lang, "todo_categories", $categoryIds, true): [];

		if ($data)
		{
			foreach($data as $value)
			{
				$todoCat = $categories[(int)$value->category];
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					'<div class="todo-color" style="background: '.htmlspecialchars(@$todoCat["html_code"]).';"></div><div class="todo-title">'.htmlspecialchars($value->title).'</div>',
					$this->lib->dateToDanish(@$this->mymongo->dateFormat($value->date, "Y-m-d")),
					@$todoCat["title"],
					'<a href="'._PANEL_ROOT_.'/index/edit/'.$id.'" class=""><i class="la la-edit"></i></a>',
				);
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function ___todoaddAction($id)
	{
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		$category		= (int)$this->request->get("category");

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
			else
			{
				$color = $this->parameters->getById($this->lang, "colors", (int)$this->request->get("color"));
				$date = trim($this->request->get("date"));
				$date = substr($date, 6, 4)."-".substr($date, 0, 2)."-".substr($date, 3, 2);
				$userInsert = [
					"id"							=> Todo::getNewId(),
					"title"							=> $title,
					"category"						=> $category,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"case"							=> (int)$id,
					"citizen"						=> (int)$this->request->get("citizen"),
					"employee"						=> (int)$this->request->get("employee"),
					"date"							=> $this->mymongo->getDate(strtotime($date)),
					"color"							=> (int)$this->request->get("color"),
					"html_code"						=> ($color) ? $color["html_code"]: "red",
					"status"						=> 0,
					"is_deleted"					=> 0,
					"created_at"					=> $this->mymongo->getDate()
				];

				Todo::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function ___todoeditAction($id)
	{
		$error 			= false;
		$success 		= false;
		$id				= (int)$this->request->get("id");

		$data 			= Todo::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$title 			= trim($this->request->get("title"));
		$category		= (int)$this->request->get("category");

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
			else
			{
				$color 	= $this->parameters->getById($this->lang, "colors", (int)$this->request->get("color"));
				$date 	= trim($this->request->get("date"));
				$date 	= substr($date, 6, 4)."-".substr($date, 0, 2)."-".substr($date, 3, 2);
				$update = [
					"title"							=> $title,
					"category"						=> $category,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"date"							=> $this->mymongo->getDate(strtotime($date)),
					"color"							=> (int)$this->request->get("color"),
					"html_code"						=> ($color) ? $color["html_code"]: "red",
					"updated_at"					=> $this->mymongo->getDate()
				];

				Todo::update(["id" => (int)$id], $update);
				$data 			= Todo::getById($id);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("tododata", $data);
	}

	public function documentsAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function documentslistAction($id)
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $did){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					DocumentFolders::update(
						[
							"id" => (int)$did
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);
				}else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$binds 	= [];
		$search = true;

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		//$binds["case_id"] 	 = (int)$id;
		$binds["citizen"] 	 = $this->data->citizen;
		$binds["is_deleted"] = 0;

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}

		switch($order_column)
		{
			default:
				$orderBy = ["_id" => $order_sort];
				break;
		}
		$data = DocumentFolders::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = DocumentFolders::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				//if($value->case_citizen) DocumentFolders::update(["_id" => $value->_id], ["citizen" => $value->citizen]);
				//if($value->employee) DocumentFolders::update(["_id" => $value->_id], ["case_employee" => $value->employee, "employee" => null]);
				$records["data"][] = [
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					'<div class="todo-color" style="background: '.htmlspecialchars($value->html_code).';"></div><div class="todo-title">'.htmlspecialchars($value->title).'</div>',
					@$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					'<a href="'._PANEL_ROOT_.'/case/documentsview/'.$id.'?id='.$value->id.'" class=""><i class="la la-file"></i></a>'
				];
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function documentsaddAction($id)
	{
		$error 		= false;
		$success 	= false;

		$docId 			= trim($this->request->get("id"));
		$title 			= trim($this->request->get("title"));
		$puid 			= trim($this->request->get("puid"));
		$category 		= (int)$this->request->get("category");

		if(strlen($puid) == 0)
			$puid = md5("casdoc-".microtime(true)."-".rand(1,9999999));

		if((int)$this->request->get("save") == 1)
		{
			if($docId > 0)
			{

			}
			else
			{
				$docId = DocumentFolders::getNewId();
				$docInsert = [
					"id"				=> (int)$docId,
					"case_id"      		=> (int)$id,
					"citizen"      		=> $this->data->citizen,
					"case_citizen"      => $this->data->citizen,
					"case_employee"     => $this->data->employee,
					"partner"   		=> (int)$this->data->partner,
					"title"				=> $title,
					"category"			=> $category,
					"is_deleted"    	=> 0,
					"created_at"		=> $this->mymongo->getDate(),
				];
				DocumentFolders::insert($docInsert);
			}

			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if($tempFiles)
			{
				$document = true;
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"title"      		=> (string)$title,
						"case_citizen"      => $this->data->citizen,
						"employee"      	=> $this->data->employee,
						"case_id"      		=> (int)$id,
						"folder"      		=> (int)$docId,
						"uuid"              => $value->uuid,
						"type"              => $value->type,
						"filename"          => $value->filename,
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Documents::insert($document);
				}

				TempFiles::update(["puid" => $puid], ["active"	=> 0]);
			}
			if($document)
			{
				$success = $this->lang->get("UploadedSuccessfully", "Uploaded successfully");
			}
			else
			{
				$success = $this->lang->get("FileNotFound", "File not found");
			}
		}

		$delete = $this->request->get("delete");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
	}

	public function documentsviewAction($id)
	{
		$error 		= false;
		$success 	= false;
		$id 		= (int)$this->request->get("id");
		$delete 	= $this->request->get("delete");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> (int)$this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];

			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$folder = DocumentFolders::getById($id);

		$documents = Documents::find([
			[
				"folder" 		=> (int)$id,
				"is_deleted"	=> 0,
			],
			"limit"	=> 100
		]);

		$citizen = false;
		if(count($folder->citizen))
			$citizen = Users::getById($folder->citizen[0]);

		$employee = false;
		if(count($folder->employee))
			$employee = Users::getById($folder->employee[0]);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("documents", $documents);
		$this->view->setVar("ccitizen", $citizen);
		$this->view->setVar("eemployee", $employee);
		$this->view->setVar("docdata", DocumentFolders::findFirst([["id" => (int)$id]]));
	}

	public function tradingplansAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function tradingplanslistAction($id)
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $did){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					CasePlans::update(
						[
							"_id" => $this->mymongo->objectId($did)
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);
				}else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$binds 	= [];
		$search = true;

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		$binds["case_id"] 	 = (int)$id;
		$binds["is_deleted"] = 0;

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["_id" => $order_sort];
				break;
		}
		$data = CasePlans::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = CasePlans::count([
			$binds,
		]);

		$goals = $this->parameters->getList($this->lang, "goals", [], true);

		if ($data)
		{
			$statueses = $this->parameters->getList($this->lang, "trading_plan_statuses", [], true);
			foreach($data as $value)
			{
				$records["data"][] = [
					'<input type="checkbox" name="id[]" value="'.$value->_id.'">',
					htmlspecialchars(@$goals[$value->goal]["title"]),
					'<i class="m-badge" style="background-color: '.htmlspecialchars(@$statueses[$value->status > 0 ? $value->status: 1]["html_code"]).'"></i> '.htmlspecialchars(@$statueses[$value->status > 0 ? $value->status: 1]["title"]),
					'<a href="'._PANEL_ROOT_.'/case/tradingplansedit/'.$id.'?id='.$value->_id.'" class="m-badge  m-badge--warning m-badge--wide" style="margin: 0 6px 4px 0;">'.strtolower($this->lang->get("edit")).'</a>'.
					'<a href="'._PANEL_ROOT_.'/case/tradingplansnotes/'.$id.'?id='.$value->_id.'" class="m-badge  m-badge--info m-badge--wide" style="margin: 0 0px 4px 0;">'.strtolower($this->lang->get("notes")).'</a>'
				];
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function tradingplansaddAction($id)
	{
		$error 				= false;
		$success 			= false;

		$goal 				= (int)$this->request->get("goal");
		$type 				= trim($this->request->get("type"));
		$action_plan 		= trim($this->request->get("action_plan"));
		$status 			= (int)$this->request->get("status");
		$responsible 		= (int)$this->request->get("responsible");
		$questions 			= [];
		foreach($this->request->get("questions") as $question)
			$questions[] = (int)$question;
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif ($goal == 0)
			{
				$error = $this->lang->get("GoalError");
			}
			else
			{

				$goalInsert = [
					"case_id"			=> (int)$id,
					"goal"				=> $goal,
					"type"				=> $type,
					"action_plan"		=> $action_plan,
					"questions"			=> $questions,
					"responsible"		=> $responsible,
					"status"			=> $status,
					"is_deleted"		=> 0,
					"created_at"		=> $this->mymongo->getDate()
				];

				$planId = CasePlans::insert($goalInsert);

				foreach($this->request->get("questions") as $questionId => $val)
				{
					$interval = $this->request->get("intervals")[$questionId];
					$qInsert = [
						"case_id"			=> (int)$id,
						"goal_id"			=> (int)$goal,
						"plan_id"			=> (string)$planId,
						"question_id"		=> (int)$questionId,
						"interval"			=> $interval,
						"moderator_id"		=> $this->auth->getData()->id,
						"is_deleted"		=> 0,
						"created_at"		=> $this->mymongo->getDate()
					];
					CasePlanQuestions::insert($qInsert);
				}

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0]]));
		$this->view->setVar("qIntervals", CasePlanQuestions::getIntervals($this->lang));
	}

	public function tradingplanseditAction($id)
	{
		$error 			= false;
		$success 		= false;

		$id 			= trim($this->request->get("id"));

		$data 			= CasePlans::findById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$goal 				= (int)$this->request->get("goal");
		$type 				= trim($this->request->get("type"));
		$action_plan 		= trim($this->request->get("action_plan"));
		$status 			= (int)$this->request->get("status");
		$questions 			= [];
		$responsible 		= (int)$this->request->get("responsible");
		foreach($this->request->get("questions") as $question)
			$questions[] = (int)$question;
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif ($goal == 0)
			{
				$error = $this->lang->get("GoalError");
			}
			else
			{
				$update = [
					"goal"				=> $goal,
					"type"				=> $type,
					"action_plan"		=> $action_plan,
					"questions"			=> $questions,
					"responsible"		=> $responsible,
					"status"			=> $status,
					"updated_at"		=> $this->mymongo->getDate()
				];

				CasePlans::update(["_id" => $this->mymongo->objectId($id)], $update);


				$qIds = [];
				foreach($this->request->get("questions") as $questionId => $val)
				{
					$qIds[] = (int)$questionId;
					$interval = $this->request->get("intervals")[$questionId];
					if($qData = CasePlanQuestions::findFirst([["plan_id" => $id, "question_id" => (int)$questionId]]))
					{
						CasePlanQuestions::update(
							[
								"_id" => $qData->_id
							],
							[
								"is_deleted" 		=> 0,
								"interval" 			=> $interval,
								"moderator_id"		=> $this->auth->getData()->id,
								"updated_at"		=> $this->mymongo->getDate()
							]
						);
					}
					else
					{
						$qInsert = [
							"case_id"			=> (int)$id,
							"goal_id"			=> (int)$goal,
							"plan_id"			=> (string)$id,
							"question_id"		=> (int)$questionId,
							"interval"			=> $interval,
							"moderator_id"		=> $this->auth->getData()->id,
							"is_deleted"		=> 0,
							"created_at"		=> $this->mymongo->getDate()
						];
						CasePlanQuestions::insert($qInsert);
					}
				}

				CasePlanQuestions::update(
					[
						"plan_id"		=> (string)($id),
						"question_id" 	=> [
							'$nin'	=> $qIds
						]
					],
					[
						"is_deleted" 		=> 1,
						"moderator_id"		=> $this->auth->getData()->id,
						"deleted_at"		=> $this->mymongo->getDate()
					]
				);


				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$selecltedQuestions = [];
		$selecltedQuery = CasePlanQuestions::find([
			[
				"plan_id"			=> (string)($id),
				"is_deleted"		=> 0,
			]
		]);
		foreach($selecltedQuery as $value)
			$selecltedQuestions[$value->question_id] = $value;

		$this->view->setVar("plandata", $data);
		$this->view->setVar("selecltedQuestions", $selecltedQuestions);
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0]]));
		$this->view->setVar("qIntervals", CasePlanQuestions::getIntervals($this->lang));
	}

	public function tradingplansnotesAction($id)
	{
		$error 		= false;
		$success 	= false;

		$id 		= trim($this->request->get("id"));


		$data 		= CasePlans::findById($id);

		$puid 				= trim($this->request->get("puid"));
		if(strlen($puid) == 0)
			$puid = md5("cv-".microtime(true)."-".rand(1,9999999));

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif((int)$this->request->get("save") == 1)
		{

			$title 			= trim($this->request->get("title"));
			$description 	= trim($this->request->get("description"));

			$userInsert = [
				"id"						=> Notes::getNewId(),
				"title"						=> $title,
				"description"				=> $description,
				"moderator_id"				=> $this->auth->getData()->id,
				"plan_id"					=> (string)$id,
				"active"					=> 1,
				"is_deleted"				=> 0,
				"created_at"				=> $this->mymongo->getDate()
			];

			$insId = Notes::insert($userInsert);




			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if($tempFiles)
			{
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"plan_id"			=> (string)$id,
						"note_id"           => (string)$insId,
						"uuid"              => $value->uuid,
						"type"              => $value->type,
						"filename"          => $value->filename,
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Documents::insert($document);
				}

				TempFiles::update(["puid" => $puid], ["active"	=> 0]);
			}




			$success = $this->lang->get("AddedSuccessfully", "Added successfully");
		}

		$delete = $this->request->get("delete");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Notes::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$notes = Notes::find([
			[
				"plan_id"			=> (string)$id,
				"is_deleted"		=> 0,
			],
			"limit"	=> 100,
			"sort"	=> [
				"_id"	=> -1
			]
		]);



		$delete = $this->request->get("deletedocument");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}


		$documents = [];
		$documentsQuery = Documents::find([
			[
				"plan_id"			=> (string)$id,
				"note_id"			=> [
					'$ne'	=> null
				],
				"is_deleted"		=> 0,
			],
			"limit"	=> 100
		]);
		foreach($documentsQuery as $value)
			$documents[(string)@$value->note_id][] = $value;

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
		$this->view->setVar("notes", $notes);
		$this->view->setVar("documents", $documents);
	}

    public function timerecordsAction($id)
    {
        $this->view->setVar("hasDatatable", true);
    }

    public function timerecordslistAction($id)
    {
        $records = [];
        if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
            foreach($this->request->get("id") as $did){
                $error 	= false;
                $status = trim($this->request->get("customActionName"));
                if($status == "delete")
                {
                    TimeRecords::update(
                        [
                            "_id" => TimeRecords::objectId($did)
                        ],
                        [
                            "is_deleted"	=> 1,
                            "deleted_by"	=> "moderator",
                            "moderator_id"	=> $this->auth->getData()->id,
                            "deleted_at"	=> $this->mymongo->getDate()
                        ]
                    );
                }else{
                    $error = "";
                }
            }
            if ($error){
                $records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
                $records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
            }else{
                $records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
                $records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
            }
        }
        $iDisplayLength = intval($this->request->get('length'));
        $iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
        $iDisplayStart = intval($this->request->get('start'));
        $sEcho = intval($this->request->get('draw'));

        $records["data"] = array();

        $order 			= $this->request->get("order");
        $order_column 	= $order[0]["column"];
        $order_sort 	= $order[0]["dir"];

        $binds 	= [];

        if ($this->request->get("item_id") > 0)
            $binds["id"] = (int)$this->request->get("item_id");

        if ($this->request->get("created_from"))
        {
            $iDisplayLength = 0;
            $binds["time_start"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];
        }

        if ($this->request->get("created_to"))
            $binds["time_start"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to"))+86400);

        if ($this->request->get("category") > 0)
            $binds["category"] = (int)$this->request->get("category");

        if ($this->request->get("type") > 0)
            $binds["type"] = (int)$this->request->get("type");

        $binds["case"] 	 		= (int)$id;
        $binds["is_deleted"] 	= 0;

        switch($order_sort)
        {
            default:
                $order_sort = -1;
                break;
            case "desc":
                $order_sort = 1;
                break;
        }
        switch($order_column)
        {
            default:
                $orderBy = ["_id" => $order_sort];
                break;
        }
        $data = TimeRecords::find([
            $binds,
            "sort"      => $orderBy,
            "limit"     => $iDisplayLength,
            "skip"      => $iDisplayStart,
        ]);

        $count = TimeRecords::count([
            $binds,
        ]);


        if ($data)
        {
            $types = $this->parameters->getList($this->lang, "session_types", [], true);

            $totalSecs = 0;
            foreach($data as $value)
            {
                $eSecs = $this->mymongo->toSeconds($value->time_end) - $this->mymongo->toSeconds($value->time_start);
                if($eSecs < 0) $eSecs = 0;
                $totalSecs += $eSecs;
                $eHours = (int)($eSecs/3600);
                $eMinutes = (int)(($eSecs - $eHours*3600)/60);
                $eSeconds = ($eSecs - $eHours*3600 - $eMinutes*60);
                $records["data"][] = [
                    '<input type="checkbox" name="id[]" value="'.$value->_id.'">',
                    @$this->mymongo->dateFormat($value->time_start, "d.m.y"),
                    @$this->mymongo->dateFormat($value->time_start, "H:i"),
                    htmlspecialchars($value->place_start->name),
                    @$this->mymongo->dateFormat($value->time_end, "H:i"),
                    htmlspecialchars($value->place_end->name),
                    ($eHours > 0 ? $eHours." hour(s) ": "").($eMinutes > 0 ? $eMinutes." minute(s) ": "").($eSeconds > 0 ? $eSeconds." seconds(s)": ""),
                    htmlspecialchars($types[$value->type]["title"]),
                    '<a href="'._PANEL_ROOT_.'/case/timerecordsedit/'.$id.'?id='.$value->_id.'" class=""><i class="la la-edit"></i></a>'
                ];
            }

            $eHours = (int)($totalSecs/3600);
            $eMinutes = (int)(($totalSecs - $eHours*3600)/60);
            if($iDisplayLength < 1)
                $records["data"][] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '<b>'.($eHours > 0 ? $eHours." hour(s) ": "").($eMinutes > 0 ? $eMinutes." minute(s)": "").'</b>',
                    '',
                    ''
                ];
        }

        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $count;
        $records["recordsFiltered"] = $count;

        echo json_encode($records);
        $this->view->disable();
        exit;
    }

    public function timerecordsaddAction($id)
    {
        $error 			= false;
        $success 		= false;

        $type 			= (int)$this->request->get("type");
        $need_calculate = (int)$this->request->get("need_calculate");
        $case 			= (int)$this->request->get("case");
        $citizen 		= (int)$this->request->get("citizen");
        $employee 		= (int)$this->request->get("employee");
        $date_start 	= $this->lib->dateFomDanish(trim($this->request->get("date_start")));
        $time_start 	= trim(str_replace(" ", "",$this->request->get("time_start")));
        $place_start 	= trim($this->request->get("place_start"));
        $date_end 		= $this->lib->dateFomDanish(trim($this->request->get("date_end")));
        $time_end 		= trim(str_replace(" ", "",$this->request->get("time_end")));
        $place_end		= trim($this->request->get("place_end"));

        $dateTimeStart  = $date_start." ".(strlen($time_start) < 7 ? $time_start.":00": $time_start);
        $dateTimeEnd 	= $date_end." ".(strlen($time_end) < 7 ? $time_end.":00": $time_end);
        //exit($dateTimeStart);
        if((int)$this->request->get("save") == 1)
        {
            if(Cache::is_brute_force("sds-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
            {
                $error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
            }
            elseif (!strtotime($dateTimeStart))
            {
                $error = $this->lang->get("StartTimeWrong", "Start date or time is wrong");
            }
            elseif (!strtotime($dateTimeEnd))
            {
                $error = $this->lang->get("EndTimeWrong", "End date or time is wrong");
            }
            else
            {
                $elapse = strtotime($dateTimeEnd) - strtotime($dateTimeStart);
                $elapseMinutes = ($elapse/60);
                $userInsert = [
                    "id"						=> TimeRecords::getNewId(),
                    "type"						=> $type,
                    "need_calculate"			=> (int)@$this->data->ask_transport_calculation,
                    "case"						=> (int)$id,
                    "contact_id"				=> (int)$this->request->get("contact"),
                    "citizen"					=> $this->data->citizen,
                    "employee"					=> $this->data->employee,
                    "time_start"				=> $this->mymongo->getDate(strtotime($dateTimeStart)),
                    "time_end"					=> $this->mymongo->getDate(strtotime($dateTimeEnd)),
                    "place_start"				=> [
                        "name"	=> $place_start
                    ],
                    "place_end"					=> [
                        "name"	=> $place_end
                    ],
                    "elapse"					=> $elapse,
                    "active"					=> 1,
                    "is_deleted"				=> 0,
                    "created_at"				=> $this->mymongo->getDate()
                ];

                TimeRecords::insert($userInsert);

                if($type == 2 && (int)@$this->data->ask_transport_calculation == 0){

                }else{
                    Cases::increment(["id" => (int)$id], ["timer_spent" => $elapseMinutes]);
                }


                $success = $this->lang->get("AddedSuccessfully", "Added successfully");
            }
        }

        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->setVar("contacts", Contacts::find([["user_id" => (int)$this->auth->getData()->id]]));
    }

    public function timerecordseditAction($id)
    {
        $error 			= false;
        $success 		= false;

        $id				= trim($this->request->get("id"));
        $data 			= TimeRecords::findById($id);
        if(!$data)
            header("Location: "._PANEL_ROOT_."/");

        $type 			= (int)$this->request->get("type");
        $need_calculate = (int)$this->request->get("need_calculate");
        $date_start 	= trim($this->request->get("date_start"));
        $time_start 	= trim(str_replace(" ", "",$this->request->get("time_start")));
        $place_start 	= trim($this->request->get("place_start"));
        $date_end 		= trim($this->request->get("date_end"));
        $time_end 		= trim(str_replace(" ", "",$this->request->get("time_end")));
        $place_end		= trim($this->request->get("place_end"));

        $dateTimeStart  = $date_start." ".(strlen($time_start) < 7 ? $time_start.":00": $time_start);
        $dateTimeEnd 	= $date_end." ".(strlen($time_end) < 7 ? $time_end.":00": $time_end);
        //exit($dateTimeStart);
        if((int)$this->request->get("save") == 1)
        {
            if(Cache::is_brute_force("sds-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
            {
                $error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
            }
            elseif (!strtotime($dateTimeStart))
            {
                $error = $this->lang->get("StartTimeWrong", "Start date or time is wrong");
            }
            elseif (!strtotime($dateTimeEnd))
            {
                $error = $this->lang->get("EndTimeWrong", "End date or time is wrong");
            }
            else
            {
                $update = [
                    "type"						=> $type,
                    "need_calculate"			=> $need_calculate,
                    "contact_id"				=> (int)$this->request->get("contact"),
                    "time_start"				=> $this->mymongo->getDate(strtotime($dateTimeStart)),
                    "time_end"					=> $this->mymongo->getDate(strtotime($dateTimeEnd)),
                    "place_start"				=> [
                        "name"	=> htmlspecialchars($place_start)
                    ],
                    "place_end"					=> [
                        "name"	=> htmlspecialchars($place_end)
                    ],
                    "update_at"					=> $this->mymongo->getDate()
                ];

                TimeRecords::update(["_id"	=> TimeRecords::objectId($id)], $update);

                $success = $this->lang->get("AddedSuccessfully", "Added successfully");
            }
        }

        $this->view->setVar("recorddata", $data);
        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->setVar("contacts", Contacts::find([["user_id" => (int)$this->auth->getData()->id]]));
    }

	public function evalreportsAction($id){
		$this->view->setVar("hasDatatable", true);
	}

	public function evalreportsviewAction($id)
	{
		$error 			= false;
		$success 		= false;
		$id				= trim($this->request->get("id"));
		$report 		= CaseReports::findById($id);
		$values 		= ($report) ? json_decode(json_encode($report->values), true): false;
		$data 			= CaseReports::getSettings($this->lang, $values);

		if(!$data)
			header("Location: "._PANEL_ROOT_."/");


		if($report)
		{
			$fileIds = [];
			$keyById = [];
			foreach($data as $value)
				if($value["children"])
					foreach($value["children"] as $form)
						if($form["type"] == "file" && $values[$form["key"]])
							foreach($values[$form["key"]] as $file)
							{
								$fileIds[] = CaseReports::objectId($file["file_id"]);
								$keyById[$file["file_id"]] = $form["key"];
							}

			if(count($fileIds) > 0)
			{
				$filesQuery = Documents::find([
					[
						"_id" => [
							'$in' => $fileIds
						]
					]
				]);
				$images = [];
				foreach($filesQuery as $value)
				{
					$images[$keyById[(string)@$value->_id]][] = [
						"file_id"	=> (string)$value->_id,
						"avatar" 	=> FILE_URL."/upload/".$value->uuid."/".(string)$value->_id."/small.jpg",
						"original"	=> FILE_URL."/upload/".$value->uuid."/".(string)$value->_id."/org.jpg",
					];
				}

				foreach($data as $value)
					if($value["children"])
						foreach($value["children"] as $form)
							if($form["type"] == "file" && $values[$form["key"]])
								$values[$form["key"]] = $images[$form["key"]];
				//exit(json_encode($values));
			}

		}


		if((int)$this->request->get("save") == 2)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{
				$update = [
					"status"		=> (int)$this->request->get("status"),
					"updated_at"	=> $this->mymongo->getDate(),
				];

				CaseReports::update(["_id" => $this->mymongo->objectId($id)], $update);
				$data 			= CaseReports::findById($id);


				/**
				foreach($this->request->get("answer") as $questionId => $answer)
				{
					//echo $questionId." - ".$answer."<br/>";

					$question = CaseQuestions::findById($questionId);
					if($question)
					{
						$update = [
							"answer"		=> $answer,
							"updated_at"	=> $this->mymongo->getDate()
						];

						CaseQuestions::update(["_id" => $this->mymongo->objectId($questionId)], $update);
					}
					else
					{
						exit("Quesiton not found");
					}
				}
				 */

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$casePlans = CasePlans::find([
			[
				"case_id"	=> (int)$this->data->id,
				"is_deleted"	=> [
					'$ne'	=> 1
				]
			]
		]);



		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("reportdata", $data);
		$this->view->setVar("values", $values);
		$this->view->setVar("reportid", $id);
		$this->view->setVar("casePlans", $casePlans);
	}

	public function evalreportslistAction($id)
	{
		$records = [];
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $did){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					CaseReports::update(
						[
							"_id" => $this->mymongo->objectId($did)
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);
				}elseif(substr($status, 0, 6) == 'status'){
                    $actStatus = (int)substr($status, 7);
                    CaseReports::update(
                        [
                            "_id" => $this->mymongo->objectId($did)
                        ],
                        [
                            "status"		=> $actStatus,
                            "updated_at"	=> $this->mymongo->getDate()
                        ]
                    );
                }else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$binds 	= [];

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		$binds["case"] 	 	= (int)$id;
		$binds["is_deleted"] 	= ['$ne' => 1];

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["_id" => $order_sort];
				break;
		}
		$data = CaseReports::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);

		$count = CaseReports::count([
			$binds,
		]);

		if ($data)
		{
			$statueses = $this->parameters->getList($this->lang, "report_statuses", [], true);
			foreach($data as $value)
			{
				$records["data"][] = [
					'<input type="checkbox" name="id[]" value="'.$value->_id.'">',
					$value->id,
					@$this->mymongo->dateFormat($value->created_at, "d.m.Y"),
					'<i class="m-badge" style="background-color: '.@$statueses[$value->status]["html_code"].'"></i> '.htmlspecialchars(@$statueses[$value->status]["title"]),
					'<a href="'._PANEL_ROOT_.'/case/evalreportsview/'.$id.'?id='.$value->_id.'" class=""><i class="la la-file"></i></a>'
				];
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function activitiesAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function activitieslistAction($id)
	{
		$records = [];
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $did){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					Activities::update(
						[
							"_id" => $this->mymongo->objectId($did)
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);
				}elseif(substr($status, 0, 6) == 'status'){
					$status = (int)substr($status, 7);
					Activities::update(
						[
							"_id" => $this->mymongo->objectId($did)
						],
						[
							"status"		=> $status,
							"updated_at"	=> $this->mymongo->getDate()
						]
					);
				}else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$binds 	= [];
		$search = true;

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		$binds["case"] 	 		= (int)$id;
		$binds["is_deleted"] 	= 0;

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["_id" => $order_sort];
				break;
		}

		$data = Activities::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);

		$count = Activities::count([
			$binds,
		]);

		if ($data)
		{
			$statueses = $this->parameters->getList($this->lang, "activity_statuses", [], true);
			foreach($data as $value)
			{
				$eSecs = $this->mymongo->toSeconds($value->time_end) - $this->mymongo->toSeconds($value->time_start);
				$eHours = (int)($eSecs/3600);
				$eMinutes = (int)(($eSecs - $eHours*3600)/60);
				$title = $value->title;
				if($value->budget_type == "1_month")
					$title = $this->lang->get("MonthlyUpdate", "Monthly update");
				if($value->budget_type == "1_year")
					$title = $this->lang->get("YearlyUpdate", "Yearly update");
				$records["data"][] = [
					'<input type="checkbox" name="id[]" value="'.$value->_id.'">',
					$title,
					@$this->mymongo->dateFormat($value->date, "d.m.Y"),
					'<span style="font-weight: bold;color: '.($value->action == "plus" ? "green": "red").';">'.($value->action == "plus" ? "+": "-").''.$this->lib->floatToDanish($value->amount, 2)." kr.</span>",
					strlen($value->title) > 0 ? '<i class="m-badge" style="background-color: '.htmlspecialchars(@$statueses[$value->status]["html_code"]).'"></i> '.htmlspecialchars(@$statueses[$value->status]["title"]): '',
					'<a href="'._PANEL_ROOT_.'/case/activitiesview/'.$id.'?id='.$value->_id.'" class=""><i class="la la-file"></i></a>'
				];
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function activitiesaddAction($id)
	{
		$error 			= false;
		$success 		= false;

		$title 		= trim($this->request->get("title"));
		$date 		= trim($this->lib->dateFomDanish($this->request->get("date")));
		$amount 	= (float)$this->lib->danishToFloat($this->request->get("amount"));
		$status 	= (int)$this->request->get("status");
		$puid 		= trim($this->request->get("puid"));
		if(strlen($puid) == 0)
			$puid = md5("act-".microtime(true)."-".rand(1,9999999));

		$unixtime 	= strtotime($date." 00:00:00");
		//exit($dateTimeStart);
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("sasdds-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 1000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 2000)
			{
				$error = $this->lang->get("TitleError");
			}
			elseif (!$unixtime)
			{
				$error = $this->lang->get("DateisWrong", "Date is wrong");
			}
			else
			{
				$userInsert = [
					//"id"						=> Activities::getNewId(),
					"title"						=> $title,
					"creator_id"				=> $this->auth->getData()->id,
					"date"						=> $this->mymongo->getDate($unixtime),
					"case"						=> (int)$id,
					"amount"					=> $amount,
					"status"					=> $status,
					"action"					=> "minus",
					"is_deleted"				=> 0,
					"created_at"				=> $this->mymongo->getDate()
				];

				$insertId = Activities::insert($userInsert);

				$tempFiles = TempFiles::find([
					[
						"puid" 		=> $puid,
						"active"	=> 1,
					]
				]);
				if($tempFiles)
				{
					$document = true;
					foreach($tempFiles as $value)
					{
						$document = [
							"_id"				=> $value->_id,
							"moderator_id"      => (int)$value->moderator_id,
							"title"      		=> (string)$title,
							//"case_id"      		=> (int)$id,
							"activity_id"      	=> $insertId,
							"uuid"              => $value->uuid,
							"type"              => $value->type,
							"filename"          => $value->filename,
							"is_deleted"        => 0,
							"created_at"        => $this->mymongo->getDate(),
						];
						Documents::insert($document);
					}

					TempFiles::update(["puid" => $puid], ["active"	=> 0]);
				}

				if($amount > 0)
				{
					$trInsert = [
						"moderator_id"      => (int)$value->moderator_id,
						"case_id"      		=> (int)$id,
						"employee"      	=> $this->data->employee,
						"citizen"      		=> $this->data->citizen,
						"activity_id"      	=> $insertId,
						"amount"			=> $amount,
						"type"				=> "spending",
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Transactions::insert($trInsert);

					Cases::increment(["id" => (int)$id], ["amount_spent" => $amount]);
				}

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
	}

	public function activitiesviewAction($id)
	{
		$error 		= false;
		$success 	= false;
		$id 		= $this->request->get("id");
		$delete 	= $this->request->get("delete");
		$status 		= (int)$this->request->get("status");
		$description 	= (string)$this->request->get("description");

		if((int)$this->request->get("save") == 1)
		{

			$update = [
				"status"					=> $status,
				"description"				=> $description,
				"upadted_at"				=> $this->mymongo->getDate()
			];

			Activities::update(["_id" => $this->mymongo->objectId($id)], $update);

			$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
		}


		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> (int)$this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];

			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$documents = Documents::find([
			[
				"activity_id" 	=> $this->mymongo->objectId($id),
				"is_deleted"	=> 0,
			],
			"limit"	=> 100
		]);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("documents", $documents);
		$this->view->setVar("docdata", Activities::findFirst([["_id" => $this->mymongo->objectId($id)]]));
	}
}
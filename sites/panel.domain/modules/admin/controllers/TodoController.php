<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Documents;
use Models\Notes;
use Models\TempFiles;
use Models\Todo;
use Models\Users;

class TodoController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("hasDatatable", true);
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]));
	}

	public function listAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $id){
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
							"deleted_at"	=> MyMongo::getDate()
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
			$binds["date"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["date"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("employee") > 0)
			$binds["employee"] = (int)$this->request->get("employee");

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		if (strlen($this->request->get("type")) > 0)
			$binds["type"] = (string)$this->request->get("type");

		if ($this->request->get("title"))
			$binds["title"] = [
				'$regex' => trim(($this->request->get("title"))),
				'$options'  => 'i'
			];

		//$binds["type"] = "todo";
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
		$categories = count($categoryIds) > 0 ? $this->parameters->getListByIds($this->lang, "todo_categories", $categoryIds, true): [];

		$employees 	= [];
		foreach($data as $value)
		{
			if(is_array($value->employee)){
				foreach($value->employee as $eid)
					$employees[] 	= (int)$eid;
			}else if($value->employee > 0)
				$employees[] 	= (int)$value->employee;
		}
		if(count($employees) > 0)
		{
			$query = Users::find(["id" => ['$in' => $employees]]);
			foreach($query as $value)
				$employees[$value->id] = $value;
		}

		if ($data)
		{
			$calendarStatuses = $this->parameters->getList($this->lang, "calendar_statuses", [], true);
			foreach($data as $value)
			{
				$employeesArr = [];
				if(is_array($value->employee)){
					foreach($value->employee as $eid)
						$employeesArr[] 	= htmlspecialchars(@$employees[$eid]->firstname." ".@$employees[$eid]->lastname);
				}else if($value->employee > 0)
					$employeesArr[] 	= htmlspecialchars(@$employees[$value->employee]->firstname." ".@$employees[$value->employee]->lastname);

				$todoCat = $categories[(int)$value->category];


				$color = @$calendarStatuses[(int)$value->status]["html_code"];
				if($value->type == "event"){
					if($this->mymongo->toSeconds($value->date_deadline) < time()){
						$color = "#1dc151";
					}else{
						$color = "#ffc400";
					}
				}


				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					'<i class="m-badge" style="background-color: '.htmlspecialchars($color).';margin-right: 5px;"></i>'.htmlspecialchars($value->title).'',
					//'<div class="todo-color" style="background: '.@$calendarStatuses[(int)$value->status]["html_code"].';"></div><div class="todo-title">'.htmlspecialchars($value->title).'</div>',
					//implode(", ", $employeesArr),
					@$this->mymongo->dateFormat($value->date, "d.m.y"),
					@$this->mymongo->dateFormat($value->date_deadline, "d.m.y"),
					$value->type == "todo" ? $this->lang->get("Todo"): $this->lang->get("Event"),
					//'<i class="m-badge" style="background-color: '.htmlspecialchars(@$todoCat["html_code"]).';margin-right: 5px;"></i>'.htmlspecialchars(@$todoCat["title"]).'',
					'<div class="todo-color" style="background: '.@$todoCat["html_code"].';"></div><div class="todo-title">'.htmlspecialchars(@$todoCat["title"]).'</div>',
					'<a href="'._PANEL_ROOT_.'/index/edit/'.$value->id.'" class="m-badge  m-badge--warning m-badge--wide" style="margin-bottom: 8px;margin-right: 10px;">'.mb_strtolower($this->lang->get("Edit")).'</a>'.
					'<a href="'._PANEL_ROOT_.'/todo/notes/'.$value->id.'" class="m-badge  m-badge--info m-badge--wide" style="">'.mb_strtolower($this->lang->get("Notes")).'</a>'
					//'<a href="'._PANEL_ROOT_.'/todo/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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

	public function __addAction()
	{
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		$lead 			= trim($this->request->get("lead"));
		$category		= (int)$this->request->get("category");
		$status			= (int)$this->request->get("status");

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
				$color 			= $this->parameters->getById($this->lang, "colors", (int)$this->request->get("color"));
				$date 			= trim($this->request->get("date"));
				$dateDeadline 	= trim($this->request->get("date_deadline"));
				$userInsert = [
					"id"							=> Todo::getNewId(),
					"creator_id"					=> (int)$this->auth->getData()->id,
					"type"							=> "todo",
					"title"							=> $title,
					"category"						=> $category,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"case"							=> (int)$this->request->get("case"),
					"moderator"						=> (int)$this->request->get("moderator"),
					"citizen"						=> (int)$this->request->get("citizen"),
					"employee"						=> (int)$this->request->get("employee"),
					"lead"							=> strlen($lead) > 0 ? (string)$lead: null,
					"date"							=> $this->mymongo->getDate(strtotime($date)),
					"date_deadline"					=> $this->mymongo->getDate(strtotime($dateDeadline)),
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
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => 0]]));
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0]]));
	}

	public function __editAction($id)
	{
		$error 			= false;
		$success 		= false;

		$data 			= Todo::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$title 			= trim($this->request->get("title"));
		$lead 			= trim($this->request->get("lead"));
		$category		= (int)$this->request->get("category");
		$status			= (int)$this->request->get("status");

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
				$date 			= trim($this->request->get("date"));
				$dateDeadline 	= trim($this->request->get("date_deadline"));
				$update = [
					"title"							=> $title,
					"category"						=> $category,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"case"							=> (int)$this->request->get("case"),
					"moderator"						=> (int)$this->request->get("moderator"),
					"citizen"						=> (int)$this->request->get("citizen"),
					"employee"						=> (int)$this->request->get("employee"),
					"lead"							=> strlen($lead) > 0 ? (string)$lead: null,
					"date"							=> $this->mymongo->getDate(strtotime($date)),
					"date_deadline"					=> $this->mymongo->getDate(strtotime($dateDeadline)),
					"status"						=> $status,
					"updated_at"					=> $this->mymongo->getDate()
				];

				Todo::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => 0]]));
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0]]));
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
		elseif((int)$this->request->get("verify") == 1)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> 0,
				"deleted_at"	=> MyMongo::getDate()
			];
			Todo::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function notesAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Todo::findFirst([
			[
				"id" 			=> (int)$id,
				//"is_deleted"	=> 0,
			]
		]);

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
				"creator_id"				=> $this->auth->getData()->id,
				"todo_id"					=> (int)$id,
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
						"creator_id"		=> $this->auth->getData()->id,
						"moderator_id"      => (int)$value->moderator_id,
						"todo_id"      		=> (int)$id,
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
				"todo_id" 			=> (int)$id,
				"is_deleted"		=> 0,
			],
			"limit"	=> 100,
			"sort"	=> [
				"_id"	=> -1
			]
		]);

		$documents = [];
		$documentsQuery = Documents::find([
			[
				"todo_id" 			=> (int)$id,
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

}
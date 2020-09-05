<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\TimeRecords;
use Models\Todo;
use Models\Users;

class TimerecordsController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("hasDatatable", true);

	}

	public function listAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $did){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					TimeRecords::update(
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
		$data = TimeRecords::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = TimeRecords::count([
			$binds,
		]);

		$categoryIds = [];
		foreach($data as $value)
			$categoryIds = array_merge(is_array($value->category) ? $value->category: [], $categoryIds);
		$categories = count($categoryIds) > 0 ? $this->parameters->getListByIds($this->lang, "todo_categories", $categoryIds, true): [];

		if($data)
		{
			foreach($data as $value)
			{
				$eSecs = $this->mymongo->toSeconds($value->time_end) - $this->mymongo->toSeconds($value->time_start);
				$eHours = (int)($eSecs/3600);
				$eMinutes = (int)(($eSecs - $eHours*3600)/60);
				$records["data"][] = [
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					@$this->mymongo->dateFormat($value->time_start, "d.m.y"),
					@$this->mymongo->dateFormat($value->time_start, "H:i"),
					htmlspecialchars($value->place_start->name),
					@$this->mymongo->dateFormat($value->time_end, "d.m.y"),
					@$this->mymongo->dateFormat($value->time_end, "H:i"),
					htmlspecialchars($value->place_end->name),
					($eHours > 0 ? $eHours." hour(s) ": "").($eMinutes > 0 ? $eMinutes." minute(s)": ""),
					'<a href="'._PANEL_ROOT_.'/case/'.$value->case.'" class=""><i class="la la-file"></i></a>'
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

	public function addAction()
	{
		$error 			= false;
		$success 		= false;

		$case 			= (int)$this->request->get("case");
		$citizen 		= (int)$this->request->get("citizen");
		$employee 		= (int)$this->request->get("employee");
		$date_start 	= trim($this->request->get("date_start"));
		$time_start 	= trim(str_replace(" ", "",$this->request->get("time_start")));
		$place_start 	= trim($this->request->get("place_start"));
		$date_end 		= trim($this->request->get("date_end"));
		$time_end 		= trim(str_replace(" ", "",$this->request->get("time_end")));
		$place_end		= trim($this->request->get("place_end"));

		$dateTimeStart  = $this->lib->dateFomDanish($date_start)." ".(strlen($time_start) < 7 ? $time_start.":00": $time_start);
		$dateTimeEnd 	= $this->lib->dateFomDanish($date_end)." ".(strlen($time_end) < 7 ? $time_end.":00": $time_end);

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
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
				$color = $this->parameters->getById($this->lang, "colors", (int)$this->request->get("color"));
				$date = trim($this->request->get("date"));
				$date = substr($date, 6, 4)."-".substr($date, 0, 2)."-".substr($date, 3, 2);
				$userInsert = [
					"id"							=> TimeRecords::getNewId(),
					"time_start"					=> $this->mymongo->getDate(strtotime($dateTimeStart)),
					"time_end"						=> $this->mymongo->getDate(strtotime($dateTimeEnd)),
					"place_start"					=> [
						"name"	=> $place_start
					],
					"place_end"						=> [
						"name"	=> $place_end
					],
					"case"							=> (int)$this->request->get("case"),
					"citizen"						=> (int)$this->request->get("citizen"),
					"employee"						=> (int)$this->request->get("employee"),
					"date"							=> $this->mymongo->getDate(strtotime($date)),
					"color"							=> (int)$this->request->get("color"),
					"html_code"						=> ($color) ? $color["html_code"]: "red",
					"status"						=> 0,
					"is_deleted"					=> 0,
					"created_at"					=> $this->mymongo->getDate()
				];

				TimeRecords::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => 0]]));
		$this->view->setVar("employees", Users::find([["type" => "citizen", "is_deleted" => 0]]));
	}

	public function editAction($id)
	{
		$error 			= false;
		$success 		= false;

		$data 			= TimeRecords::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$title 			= trim($this->request->get("title"));
		$categories 	= [];
		foreach($this->request->get("category") as $value)
			$categories[] = (int)$value;
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
					"category"						=> $categories,
					"description"					=> substr(trim($this->request->get("description")), 0, 10000),
					"case"							=> (int)$this->request->get("case"),
					"citizen"						=> (int)$this->request->get("citizen"),
					"employee"						=> (int)$this->request->get("employee"),
					"date"							=> $this->mymongo->getDate(strtotime($date)),
					"color"							=> (int)$this->request->get("color"),
					"html_code"						=> ($color) ? $color["html_code"]: "red",
					"updated_at"					=> $this->mymongo->getDate()
				];

				TimeRecords::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => 0]]));
		$this->view->setVar("employees", Users::find([["type" => "citizen", "is_deleted" => 0]]));
	}

	public function deleteAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= TimeRecords::findFirst([
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
			TimeRecords::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function testAction(){
		$data = $this->mymongo->getDate(time() - 5*24*3600);
		echo $data->toDateTime()->format("Y-m-d H:i:s");
		exit;
	}
}
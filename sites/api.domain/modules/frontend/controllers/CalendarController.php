<?php
namespace Controllers;

use Lib\MainDB;
use Lib\MyMongo;
use Models\Cache;
use Models\Cases;
use Models\Todo;
use Models\Users;

class CalendarController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		$caseId 	= (int)$this->request->get("case");
		if($limit > 100)
			$limit = 100;
		if($limit < 5)
			$limit = 5;

		$limit = 100;

		$currentDateTime = $this->mymongo->getDate(time() - 30 * 24 * 3600);

		$binds = [
			"date" => [
				'$gte' => $currentDateTime,
				//'$lte' => $this->mymongo->getDate(time()+30*24*3600)
			],
			"is_deleted" => 0
		];

		if($caseId > 0){
			$binds["case"] 	 = $caseId;
		}elseif($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}

		$data = Todo::find([
			$binds,
			"sort"		=> [
				"date_deadline"	=> -1
			],
			"limit"     => $limit,
			//"skip"      => $skip,
		]);

		$count = Todo::count([
			$binds,
		]);


		$categories = $this->parameters->getList($this->lang, "todo_categories", [], true);
		$statuses = $this->parameters->getList($this->lang, "calendar_statuses", [], true);

		$records = [];
		if ($count > 0)
		{
			$userIds = [];
			$caseUsers = [];
			$cases = [];
			$caseIds = [];
			foreach($data as $value)
			{
				$userIds[] = (int)$value->creator_id;
				foreach($value->moderator as $vv) if($vv > 0) $userIds[] = (int)$vv;
				foreach($value->citizen as $vv) if($vv > 0) $userIds[] = (int)$vv;
				foreach($value->employee as $vv) if($vv > 0) $userIds[] = (int)$vv;
				foreach($value->lead as $vv) if($vv > 0) $userIds[] = (int)$vv;

				$caseUsers[$value->id] = [];
				foreach($value->moderator as $vv) if($vv > 0) $caseUsers[$value->id][] = (int)$vv;
				foreach($value->citizen as $vv) if($vv > 0) if(!in_array($value->id, $caseUsers[$value->id])) $caseUsers[$value->id][] = (int)$vv;
				foreach($value->employee as $vv) if($vv > 0) if(!in_array($value->id, $caseUsers[$value->id])) $caseUsers[$value->id][] = (int)$vv;
				foreach($value->lead as $vv) if($vv > 0) if(!in_array($value->id, $caseUsers[$value->id])) $caseUsers[$value->id][] = (int)$vv;

				if($value->case > 0)
					$caseIds[] = (int)$value->case;
			}

			if(count($caseIds) > 0)
			{
				/**
				$caseQuery = Cases::find([["id" => ['$in' => $caseIds]]]);
				foreach($caseQuery as $case)
				{
				$cases[$case->id] = $case;
				} */
			}

			$users = [];
			if(count($userIds) > 0){
				$userQuery = Users::find([["id" => ['$in' => $userIds]]]);
				foreach($userQuery as $vv)
					$users[$vv->id] = $vv;
			}

			foreach($data as $value)
			{
				$todoCat = $categories[(int)$value->category];
				$statusData = $statuses[(int)$value->category];
				$creator = $users[$value->creator_id];

				$userList = [];
				$otherUserList = [];
				$userIds = [];
				$ui=0;
				foreach($caseUsers[$value->id] as $vv)
				{
					$user = $users[$vv];
					$uAvatar = $this->auth->getAvatar($user);
					if($user && $uAvatar){
						$vvv = [
							"id"	 => (int)$user->id,
							"avatar" => $uAvatar["small"],
						];
						$ui++;
						if($ui > 3){
							$otherUserList[] = $vvv;
						}else{
							$userList[] = $vvv;
						}
					}
					if(!in_array((int)$vv, $userIds))
						$userIds[] = (int)$vv;
				}

				$statusColor = @$statusData[$value->status]["html_code"];
				if($value->type == "event"){
					if(Todo::toSeconds($value->date_deadline) < time()){
						$statusColor = "#1dc151";
					}else{
						$statusColor = "#ffc400";
					}
				}elseif(strlen($statusColor) < 1)
					$statusColor = "#ffc400";

				$records[] = [
					"id"				=> $value->id,
					"type"				=> (string)$value->type,
					"category"			=> ($todoCat) ? [
						"id"		=> @$todoCat["id"],
						"title"		=> @$todoCat["title"],
						"color"		=> "#f9f9f9",
						"bg_color"	=> strlen(@$todoCat["html_code"]) > 2 ? @$todoCat["html_code"]: "#8F12FD",
					]: [
						"id"		=> 0,
						"title"		=> $this->lang->get("NoCat", "No category"),
						"color"		=> "#f9f9f9",
						"bg_color"	=> "#8F12FD",
					],
					"title"				=> $value->title,
					"description"		=> $value->description,
					"case"				=> (int)$value->case,
					"creator"			=> ($creator) ? ["fullname" => $creator->firstname." ".$creator->lastname]: false,
					"date"				=> @$this->mymongo->dateFormat($value->date, "d M Y"),
					"date_raw"			=> strtolower($this->request->get("timezone")) == "da" ? @$this->mymongo->dateFormat($value->date, "m-d-Y"): @$this->mymongo->dateFormat($value->date, "Y-m-d"),
					"date_time"			=> @$this->mymongo->dateFormat($value->date, "H:i"),
					"date_deadline"		=> @$this->mymongo->dateFormat($value->date_deadline, "Y-m-d"),
					"deadline_time"		=> @$this->mymongo->dateFormat($value->date_deadline, "H:i"),
					"users"				=> $userList,
					"user_ids"			=> $userIds,
					"other_users_count"	=> count($caseUsers[$value->id]) - $ui,
					"other_users"		=> $otherUserList,
					"status"			=> [
						"value"	=> (int)$value->status,
						"color"	=> (string)$statusColor
					],
				];
			}
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"skip"			=> $skip,
				"limit"			=> $limit,
				"count"			=> $count,
				"data"			=> $records,
			];
		}
		else
		{
			$response = [
				"status"		=> "error",
				"description"	=> $this->lang->get("noInformation"),
				"error_code"	=> 1201,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function createAction()
	{
		$error 			= false;
		$success 		= false;

		$type 			= strtolower($this->request->get("type")) == "event" ? "event": "todo";
		$title 			= trim($this->request->get("title"));
		$lead 			= trim($this->request->get("lead"));
		$category		= (int)$this->request->get("category");
		$status			= (int)$this->request->get("status");
		$case			= (int)$this->request->get("case");

		$time_start 	= trim($this->request->get("starttime"));
		$time_end 		= trim($this->request->get("endtime"));
		$date 			= trim($this->request->get("startdate"));
		$dateDeadline 	= trim($this->request->get("enddate"));


		if(strtolower($this->request->get("timezone")) == "da"){
			$datetime			= $this->lib->dateFomDanish($date)." ".(strlen($time_start) > 0 ? $time_start.":00": "00:00:00");
			$deadlinedatetime	= $this->lib->dateFomDanish($dateDeadline)." ".(strlen($time_end) > 0 ? $time_end.":00": "00:00:00");
		}else{
			$datetime			= $date." ".(strlen($time_start) > 0 ? $time_start.":00": "00:00:00");
			$deadlinedatetime	= $dateDeadline." ".(strlen($time_end) > 0 ? $time_end.":00": "00:00:00");
		}

		if(Cache::is_brute_force("todoAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
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
			if($case > 0)
				$caseData = Cases::getById($case);

			$moderators = [];
			foreach($this->request->get("moderator") as $value)
				$moderators[] = (int)$value;

			$employees = [];
			foreach($this->request->get("employee") as $value)
				$employees[] = (int)$value;

			$citizens = [];
			foreach($this->request->get("citizen") as $value)
				$citizens[] = (int)$value;

			$contactPersons = [];
			foreach($this->request->get("partner") as $value)
				$contactPersons[] = (int)$value;

			if($caseData)
				$citizens[] = (int)$caseData->citizen[0];

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

			$Insert = [
				"id"							=> Todo::getNewId(),
				"creator_id"					=> (int)$this->auth->getData()->id,
				"type"							=> $type,
				"title"							=> $title,
				"category"						=> $category,
				"description"					=> substr(trim($this->request->get("description")), 0, 10000),
				"case"							=> $case,
				"moderator"						=> count($moderators) > 0 ? $moderators: 0,
				"citizen"						=> count($citizens) > 0 ? $citizens: 0,
				"employee"						=> count($employees) > 0 ? $employees: 0,
				"contact_person"				=> count($contactPersons) > 0 ? $contactPersons: 0,
				"lead"							=> count($lead) > 0 ? $lead: 0,
				"date"							=> $this->mymongo->getDate(strtotime($datetime)),
				"date_deadline"					=> $this->mymongo->getDate(strtotime($deadlinedatetime)),
				"status"						=> (int)$this->request->get("status"),
				//"status"						=> $status,
				"is_deleted"					=> 0,
				"created_at"					=> $this->mymongo->getDate()
			];

			Todo::insert($Insert);

			$success = $this->lang->get("AddedSuccessfully", "Added successfully");

			$response = [
				"status"		=> "success",
				"description"	=> $success,
			];
		}
		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1202,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function editAction()
	{
		$error 			= false;

		$id 			= (int)$this->request->get("id");
		$title 			= trim($this->request->get("title"));
		$category		= (int)$this->request->get("category");
		$status			= (int)$this->request->get("status");

		$time_start 	= trim($this->request->get("starttime"));
		$time_end 		= trim($this->request->get("endtime"));
		$date 			= trim($this->request->get("startdate"));
		$dateDeadline 	= trim($this->request->get("enddate"));

		if(strtolower($this->request->get("timezone")) == "da"){
			$datetime			= $this->lib->dateFomDanish($date)." ".(strlen($time_start) > 0 ? $time_start.":00": "00:00:00");
			$deadlinedatetime	= $this->lib->dateFomDanish($dateDeadline)." ".(strlen($time_end) > 0 ? $time_end.":00": "00:00:00");
		}else{
			$datetime			= $date." ".(strlen($time_start) > 0 ? $time_start.":00": "00:00:00");
			$deadlinedatetime	= $dateDeadline." ".(strlen($time_end) > 0 ? $time_end.":00": "00:00:00");
		}

		$data 			= Todo::getById($id);

		if(Cache::is_brute_force("asdasd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 401, "hour" => 3001, "day" => 9001]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif (strlen($title) < 1 || strlen($title) > 400)
		{
			$error = $this->lang->get("TitleError");
		}
		else
		{
			$date 	= trim($this->request->get("date"));
			$date 	= substr($date, 6, 4)."-".substr($date, 0, 2)."-".substr($date, 3, 2);
			$update = [
				"title"							=> $title,
				"category"						=> $category,
				"description"					=> substr(trim($this->request->get("description")), 0, 10000),
				"case"							=> (int)$this->request->get("case"),
				"date"							=> $this->mymongo->getDate(strtotime($datetime)),
				"date_deadline"					=> $this->mymongo->getDate(strtotime($deadlinedatetime)),
				"status"						=> (int)$status,
				"updated_at"					=> $this->mymongo->getDate()
			];


			$moderators = [];
			foreach($this->request->get("moderator") as $value)
				$moderators[] = (int)$value;

			$employees = [];
			foreach($this->request->get("employee") as $value)
				$employees[] = (int)$value;

			$citizens = [];
			foreach($this->request->get("citizen") as $value)
				$citizens[] = (int)$value;

			$contactPersons = [];
			foreach($this->request->get("partner") as $value)
				$contactPersons[] = (int)$value;

			if(count($moderators) > 0)
				$update["moderator"] = $moderators;

			if(count($employees) > 0)
				$update["employee"] = $employees;

			if(count($citizens) > 0)
				$update["citizen"] = $citizens;

			if(count($contactPersons) > 0)
				$update["contact_person"] = $contactPersons;

			Todo::update(["id" => (int)$id], $update);

			$response = [
				"status"		=> "success",
				"description"	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully")
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1202,
			];
		}


		echo json_encode($response, true);
		exit();
	}

	public function deleteAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");
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
		else
		{
			$update = [
				"is_deleted"		=> 1,
				"deleted_by"		=> "user",
				"deleter_id"		=> $this->auth->getData()->id,
				"deleted_at"		=> $this->mymongo->getDate()
			];
			Todo::update(["id"	=> (int)$id], $update);

			$response = [
				"status"		=> "success",
				"description"	=> $this->lang->get("DeletedSuccessfully", "Deleted successfully")
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1202,
			];
		}
		echo json_encode($response, true);
		exit();
	}

	public function testAction()
	{

	}
}
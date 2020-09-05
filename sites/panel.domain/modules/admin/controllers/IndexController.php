<?php
namespace Controllers;

use Models\Cache;
use Models\Cases;
use Models\Documents;
use Models\Notes;
use Models\Parameters;
use Models\TimeRecords;
use Models\Todo;
use Models\Users;

class IndexController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0], "sort" => ["firstname" => 1]]));
		$this->view->setVar("contactPersons", Users::find([["type" => "partner", "is_deleted" => 0], "sort" => ["firstname" => 1]]));
		$this->view->setVar("cases", Cases::find([["is_deleted" => 0], "sort" => ["_id" => 1]]));

		$employeesQuery = Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]);
		$employees = [];
		foreach($employeesQuery as $value)
			$employees[(int)$value->id] = $value;

		$citizensQuery = Users::find([["type" => "user", "is_deleted" => 0], "sort" => ["firstname" => 1]]);
		$citizens = [];
		foreach($citizensQuery as $value)
			$citizens[(int)$value->id] = $value;

		$this->view->setVar("employees", $employees);
		$this->view->setVar("citizens", $citizens);
	}

	public function indexAction()
	{
		$timeRecords = TimeRecords::find([
			[
				"time_start" => ['$gte' => $this->mymongo->getDate(time() - 30 * 24 * 3600)],
				"is_deleted" => 0
			],
			"sort" => ["time_start" => -1],
			"limit" => 100,
		]);


		$currentDateTime = $this->mymongo->getDate(time() - 30 * 24 * 3600);

		$bind = [
			"date" => [
				'$gte' => $currentDateTime,
				//'$lte' => $this->mymongo->getDate(time()+30*24*3600)
			],
			"is_deleted" => 0
		];
		if ($this->request->get("case_id") > 0)
			$bind["case"] = (int)$this->request->get("case_id");
		//if ((int)$this->request->get("employee") > 0) $bind["employee"] = (int)$this->request->get("employee");

		$target = $this->request->get("target");

		$userId = (int)$this->request->get("user");
		$type2  = (int)$this->request->get("type2");
		if ($userId > 0)
		{
			$userData = Users::getById((int)$userId);
			if($userData->type == "employee")
			{
				$bind["employee"] = (int)$userData->id;
			}elseif($userData->type == "citizen")
			{
				$bind["citizen"] = (int)$userData->id;
			}
			elseif($userData->type == "moderator")
			{
				$bind["moderator"] = (int)$userData->id;
			}
			elseif($userData->type == "partner")
			{
				$bind["contact_person"] = (int)$userData->id;
			}
			//var_dump($userData);exit;
		}
		elseif ($type2 > 0)
		{
			$empQuery = Users::find([['type2' => $type2]]);
			$empIds = [];
			foreach ($empQuery as $value)
			    $empIds[] = (int)$value->id;

            $bind["employee"] = [
              '$in' => $empIds
            ];
		}
		else
		{
			if(strlen($target) == 0)
				$target = 1;
			if((int)$target == 1)
				$bind["moderator"] = (int)$this->auth->getData()->id;
			if((int)$target == 2)
				$bind["employee"] = [
					'$gt' => 0
				];
		}

		//var_dump($bind);exit;

		$todoEvents = Todo::find([
			$bind,
			"sort"		=> [
				"date_deadline"	=> -1
			],
			"limit"		=> 2000,
		]);


        $userIds = [];
        $userIdsByTodo = [];
        foreach ($todoEvents as $value)
        {
            foreach($value->employee as $vv) if($vv > 0) {
                $userIds[] = (int)$vv;
                $userIdsByTodo[(string)$value->_id][] = (int)$vv;
            }
            foreach($value->moderator as $vv) if($vv > 0) {
                $userIds[] = (int)$vv;
                $userIdsByTodo[(string)$value->_id][] = (int)$vv;
            }
            foreach($value->citizen as $vv) if($vv > 0) {
                $userIds[] = (int)$vv;
                $userIdsByTodo[(string)$value->_id][] = (int)$vv;
            }
            foreach($value->lead as $vv) if($vv > 0) {
                $userIds[] = (int)$vv;
                $userIdsByTodo[(string)$value->_id][] = (int)$vv;
            }
        }

        $todoData = [];
        if(count($userIds) > 0)
        {
            $usersQuery = Users::find([
                [
                    'id' => ['$in' => $userIds]
                ]
            ]);
            foreach ($usersQuery as $value)
                $usersData[$value->id] = $value;
            foreach ($todoEvents as $value){
                $userId = @$userIdsByTodo[(string)$value->_id][0];
                $pretitle = mb_strtoupper(mb_substr($usersData[$userId]->firstname, 0, 1).mb_substr($usersData[$userId]->lastname, 0, 1));
                $todoData[(string)$value->_id] = ["pretitle" => $pretitle];

            }
        }



		$calendarStatuses = $this->parameters->getList($this->lang, "calendar_statuses", [], true);


		$this->view->setVar("todoEvents", $todoEvents);
		$this->view->setVar("todoData", $todoData);
		$this->view->setVar("calendarStatuses", $calendarStatuses);
		$this->view->setVar("timeRecords", $timeRecords);
		$this->view->setVar("target", $target);
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
			elseif($type == "event" && strlen($dateDeadline) == 0)
			{
				$error = $this->lang->get("DeadlineTimeError", "Deadline Date or Time is wrong");
			}
			elseif (strlen($dateDeadline) > 0 && !strtotime($deadlinedatetime))
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
				$modData = Users::find([["type" => "moderator", "is_deleted" => 0]]);
				foreach($this->request->get("moderator") as $value)
				{
					if($value == "all")
					{
						$moderators[] = "all";
						foreach($modData as $e)
							$moderators[] = (int)$e->id;
					}else{
						$moderators[] = (int)$value;
					}
				}


				$employees = [];

				$empData = Users::find([["type" => "employee", "is_deleted" => 0]]);
				foreach($this->request->get("employee") as $value)
				{
					if($value == "all")
					{
						$employees[] = "all";
						foreach($empData as $e)
							$employees[] = (int)$e->id;
					}else{
						$employees[] = (int)$value;
					}
				}

				$citizens = [];
				foreach($this->request->get("citizen") as $value)
					$citizens[] = (int)$value;

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
			else
			{
				$moderators = [];
				foreach($this->request->get("moderator") as $value)
				{
					if($value == "all")
					{
						$moderators[] = "all";
						$modData = Users::find([["type" => "moderator", "is_deleted" => 0]]);
						foreach($modData as $e)
							$moderators[] = (int)$e->id;
					}else{
						$moderators[] = (int)$value;
					}
				}


				foreach($this->request->get("employee") as $value)
				{
					if($value == "all")
					{
						$employees[] = "all";
						$empData = Users::find([["type" => "employee", "is_deleted" => 0]]);
						foreach($empData as $e)
							$employees[] = (int)$e->id;
					}else{
						$employees[] = (int)$value;
					}
				}

				$citizens = [];
				foreach($this->request->get("citizen") as $value)
					$citizens[] = (int)$value;

				$lead = [];
				foreach($this->request->get("lead") as $value)
					$lead[] = (int)$value;

				$userInsert = [
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
					"updated_at"					=> $this->mymongo->getDate()
				];

				Todo::update(["id" => (int)$id], $userInsert);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
		$this->view->setVar("creator", Users::getById($data->creator_id));





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

		$creatorIds = [];
		foreach($notes as $note)
			$creatorIds[] = (int)$note->creator_id;
		$creators = [];
		if(count($creatorIds) > 0)
		{
			$creatorsQuery = Users::find([["id" => ['$in' => $creatorIds]]]);
			foreach($creatorsQuery as $value)
				$creators[$value->id] = $value;
		}

		$this->view->setVar("notes", $notes);
		$this->view->setVar("documents", $documents);
		$this->view->setVar("creators", $creators);
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
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Todo::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
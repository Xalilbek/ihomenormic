<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Documents;
use Models\Notes;
use Models\Operations;
use Models\TempFiles;
use Models\Users;
use Models\Vacancy;

class LogsController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->view->setVar("vacancySwitches", Vacancy::getSwitches($this->lang));
	}

	public function indexAction()
	{
		$tableConf = [
			"listUrl"       => "/logs/list",
			"cmdUrl"       => "/logs/command",
			"columns"   => [
				[
					"field"         => "RecordID",
					"title"         => "#",
					"sortable"      => false,
					"width"         => "40px",
					"selector"      => ["class" => "m-checkbox--solid m-checkbox--brand"],
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "fullname",
					"title"         => "Fullname",
					"sortable"      => true,
					"width"         => "200px",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "section",
					"title"         => "Section",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					//"attr"          => ["nowrap" => "false"],
					"overflow"      => "visible",
				],
				[
					"field"         => "action_type",
					"title"         => "Action",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "url",
					"title"         => "Url",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "date",
					"title"         => "Date",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
			],
		];

		$this->view->setVar("hasAjaxtable", true);
		$this->view->setVar("tableConf", $tableConf);
		$this->view->setVar("users", Users::find([["is_deleted" => 0, "type" => ['$in' => ["citizen","employee","moderator"]]], "sort" => ["firstname" => 1]]));
	}

	public function listAction()
	{
		$records = array();

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$limit 	= (int)$this->request->get("pagination")["perpage"];
		$skip 	= (int)$this->request->get("pagination")["page"];
		$skip	= ($skip - 1) * $limit;
		$binds 	= [];



		$queries = $this->request->get("query");

		if (strlen($queries["date_from"]) > 0)
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->lib->dateFomDanish($queries["date_from"])))];


		if (strlen($queries["date_to"]) > 0)
			$binds["created_at"] = ['$lte' => $this->mymongo->getDate(strtotime($this->lib->dateFomDanish($queries["date_to"])))];

			if ($this->request->get("query")["user_id"] > 0)
				$binds["user_id"] =(int)($this->request->get("query")["user_id"]);


		$binds["is_deleted"] = [
			'$ne'	=> 1
		];

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

			case "date":
				$orderBy = ["_id" => $order_sort];
				break;
		}
		$data = Operations::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $limit,
			"skip"      => $skip,
		]);

		$count = Operations::count([
			$binds,
		]);

		$userIds = [];
		foreach($data as $value)
			$userIds[] = (int)$value->user_id;

		$users = [];
		if(count($userIds) > 0)
		{
			$userQuery = Users::find([['id' => ['$in' => $userIds]]]);
			foreach($userQuery as $user)
				$users[(int)$user->id] = $user;
		}


		if($data)
		{
			foreach($data as $value)
			{
				$user = $users[$value->user_id];

				$records["data"][] = array(
					"RecordID" 		=> (string)$value->_id,
					"fullname" 		=> "<a href='"._PANEL_ROOT_."/profile/".$user->id."'>".htmlspecialchars($user->firstname." ".$user->lastname)."</a>",
					"section" 		=> @$this->logger->getSections($this->lang)[$value->section]["title"]."</font>",
					"action_type" 	=> $value->action_type."</font>",
					"url" 			=> '<a target="_blank" href="'.htmlspecialchars($value->url).'">'.$this->lang->get("Page").'</font>',
					"date" 			=> Users::dateFormat($value->created_at, "Y-m-d H:i")."</font>",
				);
			}
		}

		$records["meta"] = [
			"page" 		=> (int)($skip/$limit)+1,
			"pages" 	=> (int)($count/$limit),
			"perpage" 	=> $limit,
			"total" 	=> (int)$count,
			"sort"		=> "desc",
			"field" 	=> "id"
		];

		echo json_encode($records, true);
		exit;
	}

	public function commandAction()
	{
		$command = $this->request->get("command");
		foreach($this->request->get("ids") as $value)
		{
			if($command == "block"){
				Operations::update(
					[
						"id" => (int)$value
					],
					[
						"is_blocked" => 1,
						"blocked_by" => $this->auth->getData()->id,
						"blocked_at" => Vacancy::getDate()
					]
				);
			}elseif($command == "unblock"){
				Operations::update(
					[
						"id" => (int)$value
					],
					[
						"is_blocked"	=> 0,
						"unblocked_by"	=> $this->auth->getData()->id,
						"unblocked_at"	=> Vacancy::getDate()
					]
				);
			}elseif($command == "delete"){
				Operations::update(
					[
						"id" => (int)$value
					],
					[
						"is_deleted"	=> 1,
						"deleted_by"	=> "moderator",
						"moderator_id"	=> $this->auth->getData()->id,
						"deleted_at"	=> Vacancy::getDate()
					]
				);
			}
		}

		exit(json_encode(["status" => "success", "description" => "Updated successfully"]));
	}
}


<?php
namespace Controllers;

use Models\Cache;
use Models\Cases;
use Models\Todo;
use Models\Users;

class UsersController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		$todo_id 	= (int)$this->request->get("todo_id");
		$type 		= (string)strtolower($this->request->get("type"));
		if($limit > 100)
			$limit = 100;
		if($limit < 5)
			$limit = 5;
		$limit = 500;



		if($this->auth->getData() !== "moderator"){
			$binds = [];
			if($this->auth->getData()->type == "user") {
				$binds["user"] = (int)$this->auth->getData()->id;
			} else if($this->auth->getData()->type == "employee") {
				$binds["employee"] = (int)$this->auth->getData()->id;
			} else {
				$binds["contact_person"] = (int)$this->auth->getData()->id;
			}
			$binds["is_deleted"] = 0;

			$cases =  Cases::find([
				$binds,
				"limit"     => 100,
				"skip"      => 0,
			]);
			$userIds = [];
			foreach($cases as $value){
				foreach($value->citizen as $uid)
					$userIds[] = (int)$uid;
				foreach($value->employee as $uid)
					$userIds[] = (int)$uid;
				foreach($value->contact_person as $uid)
					$userIds[] = (int)$uid;
			}
			if($todo_id > 0)
			{
				$todo = Todo::findFirst([
					[
						"id" => $todo_id
					],
				]);

				foreach($todo->moderator as $vv) if($vv > 0) $userIds[] = (int)$vv;
				foreach($todo->citizen as $vv) if($vv > 0) $userIds[] = (int)$vv;
				foreach($todo->employee as $vv) if($vv > 0) $userIds[] = (int)$vv;
				foreach($todo->lead as $vv) if($vv > 0) $userIds[] = (int)$vv;
			}



			$userQuery = Users::find([["type" => "moderator", "is_deleted" => 0]]);
			foreach($userQuery as $value)
				$userIds[] = (int)$value->id;
		}








		$binds 				 	= [];
		if($this->auth->getData() !== "moderator")
		{
			$binds["id"] = ['$in' => $userIds, '$ne' => (int)$this->auth->getData()->id];
		}

		if(in_array($type, ["moderator","employee","user","partner"]))
		{
			$binds["type"] 	= $type;
		}
		$binds["is_deleted"] 	= 0;


		$data = Users::find([
			$binds,
			"sort"      => [
				"firstname"	=> 1
			],
		]);


		$records = [];
		if (count($data) > 0)
		{
			$users = [];
			$usersByType = [];
			foreach($data as $value)
			{
				$users[(int)$value->id] = $value;
				$usersByType[$value->type][] = (int)$value->id;
			}

			foreach($usersByType as $type => $ids){
				if(in_array($type, ["moderator","employee","user","partner"]))
				{
					foreach($ids as $id){
						$value = $users[$id];
						$records[$type][] = [
							"id"			=> (int)$value->id,
							"firstname"		=> $value->firstname,
							"lastname"		=> $value->lastname,
							"avatar"		=> $this->auth->getAvatar($value)["small"],
						];
					}
				}
			}


		}

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $records,
		];

		echo json_encode($response, true);
		exit();
	}
}
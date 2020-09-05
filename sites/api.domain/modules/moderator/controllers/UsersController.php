<?php
namespace Controllers;

use Models\Cache;
use Models\Users;

class UsersController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit > 100)
			$limit = 100;
		if($limit < 5)
			$limit = 5;
		$limit = 500;

		$binds 				 	= [];
		//$binds["type"] 			= 'employee';
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
				if(in_array($type, ["moderator","employee","citizen","partner"]))
				{
					foreach($ids as $id){
						$value = $users[$id];
						$records[$type][] = [
							"id"			=> (int)$value->id,
							"firstname"		=> $value->firstname,
							"lastname"		=> $value->lastname,
							"avatar"		=> $this->auth->getAvatar($value),
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
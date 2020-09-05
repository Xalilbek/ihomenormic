<?php
namespace Controllers;

use Models\Cache;
use Models\Users;

class CitizensController extends \Phalcon\Mvc\Controller
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
		$binds["type"] 			= 'citizen';
		$binds["is_deleted"] 	= 0;

		$data = Users::find([
			$binds,
			"sort"      => [
				"firstname"	=> 1
			],
		]);

		$count = Users::count([
			$binds,
		]);

		$records = [];
		if ($count > 0)
		{
			foreach($data as $value)
			{
				$records[] = [
					"id"			=> (int)$value->id,
					"firstname"		=> $value->firstname,
					"lastname"		=> $value->lastname,
					"avatar"		=> $this->auth->getAvatar($value),
				];
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
<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Cases;
use Models\Todo;
use Models\Users;

class CasescitizenController extends \Phalcon\Mvc\Controller
{
	public function minlistAction()
	{
		$binds 				 = [];
		$binds["is_deleted"] = 0;
		if($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}

		$data = Cases::find([
			$binds,
			"sort"      => [
				"_id"	=> -1
			],
		]);
		$ids = [
			"users" 		=> [],
		];

		foreach($data as $value)
		{
			$ids["users"] 		= array_unique(array_merge($ids["users"], $value->citizen));
		}
		$users = [];
		if(count($ids["users"]) > 0)
		{
			$query = Users::find(["id" => ['$in' => $ids["users"]]]);
			foreach($query as $value)
				$users[$value->id] = $value;
		}

		$records = [];
		if (count($data) > 0)
		{
			foreach($data as $value) 
			{
				$citizen  	= @$users[(int)$value->citizen[0]];
				$records[] = [
					"id"			=> (int)$value->id,
					"value"			=> (int)$value->id,
					"title"			=> $value->id." - ".(($citizen) ? $citizen->firstname: ""),
					"label"			=> $value->id." - ".(($citizen) ? $citizen->firstname: ""),
					"date"			=> @$this->mymongo->dateFormat($value->created_at, "d.m.y"),
				];
			}
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $records,
			];
		}
		else
		{
			$response = [
				"status"		=> "error",
				"description"	=> $this->lang->get("noInformation"),
				"error_code"	=> 1301,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function listAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit > 100)
			$limit = 100;
		if($limit < 5)
			$limit = 20;
		$binds 				 = [];
		$binds["is_deleted"] = 0;
		if($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}

		$data = Cases::find([
			$binds,
			"sort"      => [
				"_id"	=> -1
			],
			"limit"     => $limit,
			"skip"      => $skip,
		]);

		$count = Cases::count([
			$binds,
		]);

		$ids = [
			"users" 		=> [],
			"focus_areas" 	=> [],
			"focus_types" 	=> [],
		];

		foreach($data as $value)
		{
			$ids["users"] 		= array_unique(array_merge($ids["users"], $value->citizen));
			$ids["users"] 		= array_unique(array_merge($ids["users"], $value->employee));
			$ids["focus_areas"] = array_unique(array_merge($ids["focus_areas"], $value->focus_area));
			$ids["focus_types"] = array_unique(array_merge($ids["focus_types"], $value->focus_type));
		}
		$users = [];
		if(count($ids["users"]) > 0)
		{
			$query = Users::find(["id" => ['$in' => $ids["users"]]]);
			foreach($query as $value)
				$users[$value->id] = $value;
		}
		$focus_areas 	= count($ids["focus_areas"]) > 0 ? $this->parameters->getListByIds($this->lang, "focusareas", $ids["focus_areas"], true): [];
		$focus_types 	= count($ids["focus_types"]) > 0 ? $this->parameters->getListByIds($this->lang, "focustypes", $ids["focus_types"], true): [];


		$records = [];
		if ($count > 0)
		{
			foreach($data as $value)
			{
				$citizen  	= @$users[(int)$value->citizen[0]];
				$employee  	= @$users[(int)$value->employee[0]];
				$records[] = [
					"id"			=> $value->id,
					"citizen"			=> ($citizen) ? [
						"id"			=> $citizen->id,
						"firstname" 	=> $citizen->firstname,
						"lastname"		=> $citizen->lastname,
					]: null,
					"employee"			=> ($employee) ? [
						"id"			=> $employee->id,
						"firstname" 	=> $employee->firstname,
						"lastname"		=> $employee->lastname,
					]: null,
					"focus_area"	=> @$focus_areas[(int)$value->focus_area[0]],
					"focus_type"	=> @$focus_types[(int)$value->focus_type[0]],

					"date"			=> @$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					"unixtime"		=> @$this->mymongo->toSeconds($value->created_at),
				];
			}
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $records,
			];
		}
		else
		{
			$response = [
				"status"		=> "error",
				"description"	=> $this->lang->get("noInformation"),
				"error_code"	=> 1301,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function caseAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");

		$binds 		= [
			"id"	=> (int)$id,
		];
		if($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}

		$data 		= Cases::findFirst([
			$binds
		]);

		if(!$data)
		{
			$error = $this->lang->get("noInformation");
		}
		else
		{
			$citizen 	= Users::getById((int)@$data->citizen[0]);
			$employee 	= Users::getById((int)@$data->employee[0]);
			$focusarea	= $this->parameters->getById($this->lang, "focusareas", (int)$data->focus_type[0], true);
			$focustype	= $this->parameters->getById($this->lang, "focustypes", (int)$data->focus_type[0], true);
			$caseData = [
				"id"				=> (int)$id,
				"citizen"			=> ($citizen) ? [
					"id"			=> $citizen->id,
					"firstname" 	=> $citizen->firstname,
					"lastname"		=> $citizen->lastname,
				]: null,
				"employee"			=> ($employee) ? [
					"id"			=> $employee->id,
					"firstname" 	=> $employee->firstname,
					"lastname"		=> $employee->lastname,
				]: null,
				"focus_area"	=> $focusarea,
				"focus_type"	=> $focustype,

				"date"			=> @$this->mymongo->dateFormat($data->created_at, "d.m.y"),
				"unixtime"		=> @$this->mymongo->toSeconds($data->created_at),
			];

			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $caseData,
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1301,
			];
		}

		echo json_encode($response, true);
		exit();
	}
}
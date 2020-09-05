<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Cases;
use Models\Todo;
use Models\Users;

class CasesController extends \Phalcon\Mvc\Controller
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
				$uAvatar	= $this->auth->getAvatar($citizen);
				$records[] = [
					"id"			=> $value->id,
					"title"			=> $value->id." - ".(($citizen) ? $citizen->firstname: ""),
					"date"			=> @$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					"avatar" 		=> $uAvatar["small"],
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

				$caseParams = Cases::getParams($value, $this->lang, $this->lib);

				$records[] = [
					"id"			=> $value->id,
					"citizen"			=> ($citizen) ? [
						"id"			=> $citizen->id,
						"firstname" 	=> $citizen->firstname,
						"lastname"		=> $citizen->lastname,
						"avatar"		=> $this->auth->getAvatar($citizen),
					]: [
						"id"			=> 0,
						"firstname" 	=> "",
						"lastname"		=> "",
						"avatar"		=> $this->auth->getAvatar($citizen),
					],
					//"focus_area"	=> @$focus_areas[(int)$value->focus_area[0]],
					//"focus_type"	=> @$focus_types[(int)$value->focus_type[0]],

					"column1"	=> [
						"title"	=> $this->lang->get("CustomerSince", "Kunde siden"),
						"value"	=> $caseParams["startDate"],
					],
					"column2"	=> [
						"title"	=> $this->lang->get("NextMeeting", "Næsteservice besøg"),
						"value"	=> $caseParams["startEmployeeDate"],
					],

					"amount"		=> [
						"left"		=> round((float)$value->amount_spent,0),
						"right"		=> round((float)$value->balance,0),
						"text"		=> "DKK",
					],
					"amount_circle"	=> [
						"from"			=> round((float)$value->amount_spent,0),
						"to"			=> round((float)$value->balance,0),
						"percent"		=> $caseParams["amountCirclePercent"],
						"under_text"	=> $this->lang->get("ActivityAndPayment"),
						"color"			=> $caseParams["amountColor"],
						//"upper_text"	=> $this->lib->getMonthList($this->lang)[(int)date("m")],
					],

					"timer"	=> [
						"left"	=> $caseParams["spentHours"],
						"right"	=> round($value->meeting_duration, 1),
						"text"	=> $this->lang->get("Hours", "Hour(s)"),
					],
					"timer_circle"	=>	[
						"from"			=> round((float)$caseParams["spentHours"],2),
						//"to"			=> round((float)$caseParams["timerMonthly"], 2),
						"percent"		=> $caseParams["timeCirclePercent"],
						"under_text"	=> $this->lang->get("TimeRecording"),
						"color"			=> $caseParams["timeColor"],
						//"upper_text"	=> $this->lib->getMonthList($this->lang)[(int)date("m")],
						//"week"			=> $this->lang->get("Week")." ".(int)date("W"),
					],

					"date"			=> @$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					"unixtime"		=> @$this->mymongo->toSeconds($value->created_at) * 1000,
				];
				//echo "<pre>";var_dump($records); exit("ok");

			}
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $records,
			];
			//echo "<pre>"; var_dump(($records));
		}
		else
		{
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> [],
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
				"unixtime"		=> @$this->mymongo->toSeconds($data->created_at) * 1000,
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
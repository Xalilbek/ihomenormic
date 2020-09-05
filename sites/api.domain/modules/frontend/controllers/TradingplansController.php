<?php
namespace Controllers;

use Models\CasePlans;
use Models\Cases;
use Models\Users;

class TradingplansController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error 		= false;
		$case		= (int)$this->request->get("case");
		$skip		= (int)$this->request->get("skip");
		$limit		= (int)$this->request->get("limit");
		if($limit < 5)
			$limit = 100;

		$binds				 = [];
		$binds["case_id"] 	 = (int)$case;
		$binds["is_deleted"] = 0;

		$data = CasePlans::find([
			$binds,
			"limit"     => $limit,
			"skip"      => $skip,
			"sort"      => [
				"_id" => -1
			],
		]);

		$count = CasePlans::count([
			$binds,
		]);

		$goals = $this->parameters->getList($this->lang, "goals", [], true);

		if ($data)
		{
			$statuses = $this->parameters->getList($this->lang, "trading_plan_statuses", [], true);
			$records = [];
			foreach($data as $value)
			{
				$records[] = [
					"id" 		=> (string)$value->_id,
					"goal"		=> [
						"id"		=> (int)$value->goal,
						"title"		=> htmlspecialchars(@$goals[$value->goal]["title"]),
					],
					"status"	=> [
						"value"		=> $value->status > 0 ? $value->status: 1,
						"title"		=> htmlspecialchars(@$statuses[$value->status > 0 ? $value->status: 1]["title"]),
						"color"		=> htmlspecialchars(@$statuses[$value->status > 0 ? $value->status: 1]["html_code"]),
					],
					"plan"		=> (string)$value->action_plan,
					"date"		=> CasePlans::dateFormat($value->created_at, "d-m-Y H:s"),
				];
			}

			$response = [
				"status"		=> "success",
				"description"	=> "",
				"count"			=> $count,
				"skip"			=> $skip,
				"limit"			=> $limit,
				"data"			=> $records,
			];
		}
		else
		{
			$error = $this->lang->get("noInformation", "No information");
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



	public function addAction()
	{
		$error 				= false;

		$case 				= (int)$this->request->get("case");
		$goal 				= (int)$this->request->get("goal");
		$action_plan 		= trim($this->request->get("plan"));
		$status 			= (int)$this->request->get("status");

		$caseData 			= Cases::getById($case);
		if (!$caseData)
		{
			$error = $this->lang->get("CaseNotFound", "Case not found");
		}
		elseif ($goal == 0)
		{
			$error = $this->lang->get("GoalError");
		}
		else
		{

			$Insert = [
				"case_id"			=> (int)$case,
				"goal"				=> $goal,
				"action_plan"		=> $action_plan,
				"status"			=> $status,
				"is_deleted"		=> 0,
				"created_at"		=> $this->mymongo->getDate()
			];

			CasePlans::insert($Insert);

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
				"error_code"	=> 1301,
			];
		}

		echo json_encode($response, true);
		exit();
	}



	public function editAction()
	{
		$error 				= false;

		$planId 			= (string)$this->request->get("id");
		$status 			= (int)$this->request->get("status");

		$plan 				= CasePlans::findById($planId);
		if (!$plan)
		{
			$error = $this->lang->get("CaseNotFound", "Case not found");
		}
		else
		{

			$update = [
				"status"			=> $status,
				"updated_at"		=> $this->mymongo->getDate()
			];

			CasePlans::update(["_id" => $plan->_id], $update);

			$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");

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
				"error_code"	=> 1301,
			];
		}

		echo json_encode($response, true);
		exit();
	}
}
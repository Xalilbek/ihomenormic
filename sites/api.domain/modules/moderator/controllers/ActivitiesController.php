<?php
namespace Controllers;

use Models\Activities;
use Models\Cache;
use Models\Cases;
use Models\Documents;
use Models\TempFiles;
use Models\Transactions;

class ActivitiesController extends \Phalcon\Mvc\Controller
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
		$binds["case"] 	 	= (int)$case;
		$binds["is_deleted"] = 0;

		$data = Activities::find([
			$binds,
			//"limit"     => $limit,
			//"skip"      => $skip,
			"sort"      => [
				"_id" => -1
			],
		]);

		$count = Activities::count([
			$binds,
		]);

		if ($data)
		{
			$statuses = $this->parameters->getList($this->lang, "activity_statuses", [], true);

			$records = [];
			$total = [];
			$filtered = [];
			foreach($data as $value)
			{
				$title = $value->title;
				if($value->budget_type == "1_month")
					$title = $this->lang->get("MonthlyUpdate", "Monthly update");
				if($value->budget_type == "1_year")
					$title = $this->lang->get("YearlyUpdate", "Yearly update");

				$month = @$this->mymongo->dateFormat($value->date, "Y-m-01");

				$records[$month][] = [
					"id" 			=> (string)$value->_id,
					"title" 		=> $title,
					"date" 			=> @$this->mymongo->dateFormat($value->date, "d.m.Y"),
					"amount"		=> [
						"value"	=> ($value->action == "plus" ? "+": "-").''.$this->lib->floatToDanish($value->amount, 2)." kr",
						"color"	=> $value->action == "plus" ? "green": "red",
					],
					"status"	=> [
						"value" => (int)$value->status,
						"text" 	=> @$statuses[$value->status]["title"],
						"color" => @$statuses[$value->status]["html_code"],
					],
				];

				$total[$month][$value->action] += $value->amount;
			}

			foreach($total as $month => $value)
			{
				$filtered[] = [
					"month"	=> date("M", strtotime($month)),
					"total"	=> [
						"plus"		=> $this->lib->floatToDanish((float)@$total[$month]["plus"], 2)." kr",
						"minus"		=> $this->lib->floatToDanish((float)@$total[$month]["minus"], 2)." kr",
						"total"		=> $this->lib->floatToDanish((float)@$total[$month]["plus"] - (float)@$total[$month]["minus"], 2)." kr",
					],
					"list"	=> $records[$month],
				];
			}

			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $filtered,
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
		$error 		= false;
		$response 	= false;

		$title 		= trim($this->request->get("title"));
		$date 		= trim(($this->request->get("date")));
		$amount 	= (float)$this->lib->danishToFloat($this->request->get("amount"));
		$status 	= (int)$this->request->get("status");
		$case 		= (int)$this->request->get("case");
		$file_ids 	= $this->request->get("file_ids");

		$unixtime 	= strtotime($date." 00:00:00");

		if(Cache::is_brute_force("sasdds-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 1000, "day" => 9000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($title) < 1 || strlen($title) > 2000)
		{
			$error = $this->lang->get("TitleError");
		}
		elseif (!$unixtime)
		{
			$error = $this->lang->get("DateisWrong", "Date is wrong");
		}
		else
		{
			$userInsert = [
				"title"						=> $title,
				"creator_id"				=> $this->auth->getData()->id,
				"date"						=> $this->mymongo->getDate($unixtime),
				"case"						=> (int)$case,
				"amount"					=> $amount,
				"status"					=> $status,
				"action"					=> "minus",
				"is_deleted"				=> 0,
				"created_at"				=> $this->mymongo->getDate()
			];

			$insertId = Activities::insert($userInsert);

			foreach($file_ids as $file_id)
			{
				if(strlen($file_id) > 0)
				{
					$tempFile = TempFiles::findFirst([
						[
							"_id" 		=> TempFiles::objectId($file_id),
							"active"	=> 1,
						]
					]);

					if($tempFile)
					{
						$document = [
							"_id"				=> $tempFile->_id,
							"user_id"      		=> (int)$this->auth->getData()->id,
							"title"      		=> (string)$title,
							"case_id"      		=> (int)$case,
							"activity_id"      	=> $insertId,
							"uuid"              => $tempFile->uuid,
							"type"              => $tempFile->type,
							"filename"          => $tempFile->filename,
							"is_deleted"        => 0,
							"created_at"        => $this->mymongo->getDate(),
						];

						Documents::insert($document);

						TempFiles::update(
							[
								"_id" => TempFiles::objectId($file_id)
							],
							[
								"active"	=> 0
							]
						);
					}
				}
			}

			if($amount > 0)
			{
				$trInsert = [
					"user_id"      		=> (int)$this->auth->getData()->id,
					"case_id"      		=> (int)$case,
					"employee"      	=> $this->data->employee,
					"citizen"      		=> $this->data->citizen,
					"activity_id"      	=> $insertId,
					"amount"			=> $amount,
					"type"				=> "spending",
					"is_deleted"        => 0,
					"created_at"        => $this->mymongo->getDate(),
				];

				Transactions::insert($trInsert);

				Cases::increment(["id" => (int)$case], ["amount_spent" => $amount]);
			}

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

}
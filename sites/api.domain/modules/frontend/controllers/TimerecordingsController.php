<?php
namespace Controllers;

use Models\CasePlans;
use Models\Cases;
use Models\TimeRecords;
use Models\Users;

class TimerecordingsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error 		= false;
		$case		= (int)$this->request->get("case");
		$skip		= (int)$this->request->get("skip");
		$limit		= (int)$this->request->get("limit");
		$startTime	= $this->request->get("starttime");
		$endTime	= $this->request->get("endtime");
		if($limit < 5)
			$limit = 100;

		if(strlen($startTime) > 0 && !strtotime($startTime)){
			$error = $this->lang->get("StarttimeWrong", "Start time is wrong");
		}elseif(strlen($endTime) > 0 && !strtotime($endTime)){
			$error = $this->lang->get("EndtimeWrong", "End time is wrong");
		}else{
			$binds				 	= [];
			$binds["case"] 	 		= (int)$case;
			$binds["is_deleted"] 	= 0;
			if(strtotime($startTime) > 0){
				$binds["time_start"]['$gte'] = TimeRecords::getDate(strtotime($startTime));
				$limit = 0;
			}
			if(strtotime($endTime) > 0){
				$binds["time_start"]['$lte'] = TimeRecords::getDate(strtotime($endTime));
				$limit = 0;
			}
			//exit(json_encode($binds));

			$data = TimeRecords::find([
				$binds,
				"limit"     => $limit,
				"skip"      => $skip,
				"sort"      => [
					"_id" => -1
				],
			]);

			$count = TimeRecords::count([
				$binds,
			]);

			if ($data)
			{
				$types = $this->parameters->getList($this->lang, "session_types", [], true);

				$records = [];
				$total = 0;
				foreach($data as $value)
				{
					$type 		= $types[$value->type];
					$eSecs 		= $this->mymongo->toSeconds($value->time_end) - $this->mymongo->toSeconds($value->time_start);
					$eHours 	= (int)($eSecs/3600);
					$eMinutes 	= (int)(($eSecs - $eHours*3600)/60);
					$eSeconds 	= (int)($eSecs - $eHours*3600 - $eMinutes*60);

					if($eSecs > 0)
					$total += $eSecs;

					$records[] = [
						"id" 			=> (string)$value->_id,
						"startdate" 	=> @$this->mymongo->dateFormat($value->time_start, "d.m.Y"),
						"starttime" 	=> @$this->mymongo->dateFormat($value->time_start, "H:i"),
						"start_unixtime"=> @$this->mymongo->toSeconds($value->time_start),
						"enddate" 		=> @$this->mymongo->dateFormat($value->time_end, "d.m.Y"),
						"endtime" 		=> @$this->mymongo->dateFormat($value->time_end, "H:i"),
						"end_unixtime" 	=> @$this->mymongo->toSeconds($value->time_end),
						"startplace" 	=> @$value->place_start->name,
						"endplace" 		=> @$value->place_end->name,
						"duration"		=> ($eHours > 0 ? $eHours." hour(s) ": "").($eMinutes > 0 ? $eMinutes." minute(s) ": "").($eSeconds > 0 ? $eSeconds." second(s)": ""),
						"type"			=> ($type) ?
							[
								"value"		=> $type["id"],
								"title"		=> $type["title"],
							]: false
					];
				}

				$eHours = (int)($total/3600);
				$eMinutes = (int)(($total - $eHours*3600)/60);

				$response = [
					"status"			=> "success",
					"description"		=> "",
					"count"				=> $count,
					"skip"				=> $skip,
					"limit"				=> $limit,
					"data"				=> $records,
					"total_duration" 	=> [
						"value"	=> $total,
						"text"	=> ($eHours > 0 ? $eHours." hour(s) ": "").($eMinutes > 0 ? $eMinutes." minute(s)": "")
					],
				];
			}
			else
			{
				$error = $this->lang->get("noInformation", "No information");
			}
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



	public function statusAction()
	{
		$error 				= false;

		$case 				= (int)$this->request->get("case");

		$caseData 			= Cases::getById($case);
		if (!$caseData)
		{
			$error = $this->lang->get("CaseNotFound", "Case not found");
		}
		else
		{
			$lastSession = TimeRecords::findFirst([
				[
					"case"		 => $case,
					"is_deleted" => [
						'$ne' => 1
					],
				],
				"sort"	=> [
					"_id"	=> -1
				]
			]);

			if($lastSession && !$lastSession->time_end)
			{
				$response = [
					"status"		=> "success",
					"session"		=> [
						"id"				=> (string)$lastSession->_id,
						"starttime"			=> TimeRecords::dateFormat($lastSession->time_start, "Y-m-d H:i:s"),
						"start_unixtime"	=> TimeRecords::toSeconds($lastSession->time_start) * 1000,
						"elapse_seconds"	=> time() - TimeRecords::toSeconds($lastSession->time_start),
					]
				];
			}
			else
			{
				$error = $this->lang->get("TimeRecordNotFound", "Time record not found");
			}
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





	public function toggleAction()
	{
		$error 				= false;

		$case 				= (int)$this->request->get("case");
		$latitude 			= (float)$this->request->get("latitude");
		$longitude 			= (float)$this->request->get("longitude");

		$caseData 			= Cases::getById($case);
		if (!$caseData)
		{
			$error = $this->lang->get("CaseNotFound", "Case not found");
		}
		elseif ($latitude == 0 || $longitude == 0)
		{
			$error = $this->lang->get("CoordinatesIncorrect", "Coordinates are incorrect");
		}
		else
		{
			$lastSession = TimeRecords::findFirst([
				[
					"case"		 => $case,
					"is_deleted" => [
						'$ne' => 1
					],
				],
				"sort"	=> [
					"_id"	=> -1
				]
			]);

			$address = $this->lib->getAddress($latitude, $longitude);
			if(!$lastSession || ($lastSession && $lastSession->time_end))
			{
				$Insert = [
					"case"				=> (int)$case,
					"time_start"		=> $this->mymongo->getDate(time()),
					//"time_end"			=> $this->mymongo->getDate(strtotime($dateTimeEnd)),
					"place_start"		=> [
						"name"			=> ($address) ? $address["name"]: $latitude."-".$longitude,
						"geometry"		=> [
							"type"			=> "Point",
							"coordinates"	=> [
								$longitude, $latitude
							]
						]
					],
					"employee"			=> $caseData->employee,
					"citizen"			=> $caseData->citizen,
					"customer"			=> $caseData->citizen,
					"contact_person"	=> $caseData->contact_person,
					"status"			=> 0,
					"is_deleted"		=> 0,
					"created_at"		=> $this->mymongo->getDate()
				];

				TimeRecords::insert($Insert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");

				$response = [
					"status"		=> "success",
					"description"	=> $success,
					"session"		=> [
						"id"				=> (string)$lastSession->_id,
						"starttime"			=> date("Y-m-d H:i:s", time()),
						"start_unixtime"	=> time() * 1000,
						"endtime"			=> false,
						"end_unixtime"		=> false,
						"elapse_seconds"	=> 0,
					]
				];
			}
			else
			{
				$elapse = time() - TimeRecords::toSeconds($lastSession->time_start);
				$elapseMinutes = ($elapse/60);

				$update = [
					"time_end"		=> $this->mymongo->getDate(time()),
					"place_end"		=> [
						"name"			=> ($address) ? $address["name"]: $latitude."-".$longitude,
						"geometry"		=> [
							"type"			=> "Point",
							"coordinates"	=> [
								$longitude, $latitude
							]
						]
					],
					"elapse"		=> $elapseMinutes,
					"update_at"		=> $this->mymongo->getDate()
				];

				TimeRecords::update(
					[
						"_id" => $lastSession->_id
					],
					$update
				);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");

				// #####################

				// #####################

				$response = [
					"status"		=> "success",
					"description"	=> $success,
					"session"		=> [
						"id"				=> (string)$lastSession->_id,
						"starttime"			=> TimeRecords::dateFormat($lastSession->time_start, "Y-m-d H:i:s"),
						"start_unixtime"	=> TimeRecords::toSeconds($lastSession->time_start, "Y-m-d H:i:s") * 1000,
						"endtime"			=> date("Y-m-d H:i:s"),
						"end_unixtime"		=> time() * 1000,
						"elapse_seconds"	=> time() - TimeRecords::toSeconds($lastSession->time_start),
					]
				];
			}
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


	public function updateAction()
	{
		$error 		= false;
		$session 	= false;
		$sessionId  = trim($this->request->get("id"));
		$type  		= (int)$this->request->get("type");
		$contact  	= (int)$this->request->get("contact");
		$user_id  	= (int)$this->request->get("user_id");

		if(strlen($sessionId) > 0)
			$session = TimeRecords::findFirst([
				[
					"_id"		 => TimeRecords::objectId($sessionId),
				],
				"sort"	=> [
					"_id"	=> -1
				]
			]);

		if(!$session)
		{
			$error = $this->lang->get("CaseNotFound", "Case not found");
		}
		else
		{
			$caseData 		= Cases::getById($session->case);
			$need_calculate = 1;
			if($caseData && $type == 2){
				$need_calculate = (int)$caseData->ask_transport_calculation == 1 ? 1: 0;
			}
			$update = [
				"type"					=> $type,
				"need_calculate"		=> $need_calculate,
				"contact"				=> $contact,
			];

			TimeRecords::update(
				[
					"_id" => $session->_id
				],
				$update
			);

			if($need_calculate)
				Cases::increment(["id" => (int)$session->case], ["timer_spent" => $session->elapse]);


			$response = [
				"status"		=> "success",
				"description"	=> $this->lang->get("updateSuccessfully", "Updated successfully"),
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
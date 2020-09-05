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
		if($limit < 5)
			$limit = 100;

		$binds				 = [];
		$binds["case"] 	 	= (int)$case;
		$binds["is_deleted"] = 0;

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
			$records = [];
			foreach($data as $value)
			{
				$eSecs = $this->mymongo->toSeconds($value->time_end) - $this->mymongo->toSeconds($value->time_start);
				$eHours = (int)($eSecs/3600);
				$eMinutes = (int)(($eSecs - $eHours*3600)/60);

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
					"duration"		=> ($eHours > 0 ? $eHours." hour(s) ": "").($eMinutes > 0 ? $eMinutes." minute(s)": ""),
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



	public function __startAction()
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

			if($lastSession && !$lastSession->time_end)
			{
				$response = [
					"status"		=> "error",
					"description"	=> $this->lang->get("AlreadyStartedSession", "You have already started session"),
					"error_code"	=> 1834,
					"session"		=> [
						"id"				=> (string)$lastSession->_id,
						"starttime"			=> TimeRecords::dateFormat($lastSession->time_start, "Y-m-d H:i:s"),
						"start_unixtime"	=> TimeRecords::toSeconds($lastSession->time_start, "Y-m-d H:i:s") * 1000,
					]
				];
			}
			else
			{
				$Insert = [
					"case"				=> (int)$case,
					"time_start"		=> $this->mymongo->getDate(time()),
					//"time_end"			=> $this->mymongo->getDate(strtotime($dateTimeEnd)),
					"place_start"		=> [
						"name"			=> $latitude."-".$longitude,
						"geometry"		=> [
							"type"			=> "Point",
							"coordinates"	=> [
								$longitude, $latitude
							]
						]
					],
					"status"			=> 0,
					"is_deleted"		=> 0,
					"created_at"		=> $this->mymongo->getDate()
				];

				TimeRecords::insert($Insert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");

				$response = [
					"status"		=> "success",
					"description"	=> $success,
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

			if(!$lastSession || ($lastSession && $lastSession->time_end))
			{
				$Insert = [
					"case"				=> (int)$case,
					"time_start"		=> $this->mymongo->getDate(time()),
					//"time_end"			=> $this->mymongo->getDate(strtotime($dateTimeEnd)),
					"place_start"		=> [
						"name"			=> $latitude."-".$longitude,
						"geometry"		=> [
							"type"			=> "Point",
							"coordinates"	=> [
								$longitude, $latitude
							]
						]
					],
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
				$elapseMinutes = (int)($elapse/60);

				$update = [
					"time_end"		=> $this->mymongo->getDate(time()),
					"place_end"		=> [
						"name"			=> $latitude."-".$longitude,
						"geometry"		=> [
							"type"			=> "Point",
							"coordinates"	=> [
								$longitude, $latitude
							]
						]
					],
					"elapse"		=> $elapse,
					"update_at"		=> $this->mymongo->getDate()
				];

				TimeRecords::update(
					[
						"_id" => $lastSession->_id
					],
					$update
				);

				Cases::increment(["id" => (int)$case], ["timer_spent" => $elapseMinutes]);


				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");

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
}
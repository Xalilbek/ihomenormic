<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Cases;
use Models\TimeRecords;
use Models\Todo;
use Models\Users;

class CaseController extends \Phalcon\Mvc\Controller
{
	public function infoAction()
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
			$userIds = [];
			foreach($data->citizen as $uid)
				$userIds[] = (int)$uid;
			foreach($data->employee as $uid)
				$userIds[] = (int)$uid;
			foreach($data->contact_person as $uid)
				$userIds[] = (int)$uid;

			$usersQuery = Users::find([['id' => ['$in' => $userIds]]]);
			$users = [];
			foreach($usersQuery as $value)
				$users[$value->id] = $value;

			$citizen 		= $users[(int)@$data->citizen[0]];
			$employee 		= $users[(int)@$data->employee[0]];
			$contact_persons = [];
			foreach($data->contact_person as $uid){
				$value = $users[$uid];
				$contact_persons[] = [
					"id"			=> (int)@$value->id,
					"firstname" 	=> (string)@$value->firstname,
					"lastname"		=> (string)@$value->lastname,
					"phone" 		=> (string)@$value->phone,
					"avatar"		=> $this->auth->getAvatar($value),
				];
			}

			$focusarea		= $this->parameters->getById($this->lang, "focusareas", (int)$data->focus_area[0], true);
			$focustype		= $this->parameters->getById($this->lang, "focustypes", (int)$data->focus_type[0], true);

			$caseParams = Cases::getParams($data, $this->lang, $this->lib);

			$session = false;
			$lastSession = TimeRecords::findFirst([
				[
					"case"		 => $id,
					"is_deleted" => [
						'$ne' => 1
					],
				],
				"sort"	=> [
					"_id"	=> -1
				]
			]);
			if($lastSession && $lastSession->time_end)
			{
				$session = [
					"id"		=> (string)$lastSession->_id,
					"starttime"	=> TimeRecords::dateFormat($lastSession->time_start, "Y-m-d H:i:s"),
					"unixtime"	=> TimeRecords::toSeconds($lastSession->time_start, "Y-m-d H:i:s") * 1000,
				];
			}

            if($citizen)
                $city = $this->parameters->getById($this->lang, "cities", $citizen->city);

			$caseData = [
				"id"				=> (int)$id,
				/**
				"employee"			=> ($employee) ? [
					"id"			=> $employee->id,
					"firstname" 	=> $employee->firstname,
					"lastname"		=> $employee->lastname,
				]: null, */
				"focus_area"	=> $focusarea,
				"focus_type"	=> $focustype,

                "citizen"			=> ($citizen) ? [
                    "id"			=> $citizen->id,
                    "firstname" 	=> $citizen->firstname,
                    "lastname"		=> $citizen->lastname,
                    "phone" 		=> $citizen->phone,
                    "avatar"		=> $this->auth->getAvatar($citizen),
                    "address"		=> (strlen($city["title"]) > 0 ? $city["title"].", ": "").(strlen($citizen->zipcode) > 0 ? $citizen->zipcode.", ": "").$citizen->address,
                ]: false,
				"contact_persons" => $contact_persons,
				//"focus_area"	=> @$focus_areas[(int)$data->focus_area[0]],
				//"focus_type"	=> @$focus_types[(int)$data->focus_type[0]],

				"column1"	=> [
					"title"	=> $this->lang->get("CustomerSince", "Kunde siden"),
					"value"	=> $caseParams["startDate"],
				],
				"column2"	=> [
					"title"	=> $this->lang->get("NextMeeting", "Næsteservice besøg"),
					"value"	=> $caseParams["startEmployeeDate"],
				],

				"amount"		=> [
					"left"		=> round((float)$data->amount_spent,0),
					"right"		=> round((float)$data->balance,0),
					"text"		=> "DKK",
				],
				"amount_circle"	=> [
					"from"			=> round((float)$data->amount_spent,0),
					"to"			=> round((float)$data->balance,0),
					"percent"		=> $caseParams["amountCirclePercent"],
					"upper_text"	=> $this->lib->getMonthList($this->lang)[(int)date("m")],
					"under_text"	=> $this->lang->get("ActivityAndPayment"),
					"color"			=> $caseParams["amountColor"],
				],

				"timer"	=> [
					"left"	=> $caseParams["spentHours"],
					"right"	=> round($data->meeting_duration, 1),
					"text"	=> $this->lang->get("Hours", "Hour(s)"),
				],
				"timer_circle"	=>	[
					"from"			=> round((float)$caseParams["spentHours"],2),
					"to"			=> round((float)$caseParams["timerMonthly"], 2),
					"percent"		=> $caseParams["timeCirclePercent"],
					"under_text"	=> $this->lang->get("TimeRecording"),
					"color"			=> $caseParams["timeColor"],
					"upper_text"	=> $this->lib->getMonthList($this->lang)[(int)date("m")],
					"week"			=> $this->lang->get("Week")." ".(int)date("W"),
				],


				"date"			=> @$this->mymongo->dateFormat($data->created_at, "d.m.y"),
				"unixtime"		=> @$this->mymongo->toSeconds($data->created_at) * 1000,

				"session"		=> $session,
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
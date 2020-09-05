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
			$citizen 	= Users::getById((int)@$data->citizen[0]);
			$employee 	= Users::getById((int)@$data->employee[0]);
			$focusarea	= $this->parameters->getById($this->lang, "focusareas", (int)$data->focus_area[0], true);
			$focustype	= $this->parameters->getById($this->lang, "focustypes", (int)$data->focus_type[0], true);



			if(!$data->start_employee_date)
				$data->start_employee_date = $data->start_date;
			$durationSecs = $this->mymongo->getUnixtime() - $this->mymongo->toSeconds($data->start_employee_date);

			$duration = $this->lib->durationToStr($durationSecs, $this->lang);

			$startDate = $this->mymongo->dateFormat($data->start_date, "d.m.Y");



			$timerMonthly = $data->meeting_duration;
			if($data->meeting_duration_type == "week")
				$timerMonthly = round($data->meeting_duration * 52 / 12, 0);
			$spentHours = $data->timer_spent/60;



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


			$amountCirclePercent = round(abs($data->amount_spent/$data->balance) * 100);
			if($amountCirclePercent > 0){}else{$amountCirclePercent=0;};

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
					"avatar"		=> $this->auth->getAvatar($citizen),
				]: false,
				//"focus_area"	=> @$focus_areas[(int)$data->focus_area[0]],
				//"focus_type"	=> @$focus_types[(int)$data->focus_type[0]],

				"column1"	=> [
					"title"	=> $this->lang->get("Duration"),
					"value"	=> $duration,
				],
				"column2"	=> [
					"title"	=> $this->lang->get("StartDate"),
					"value"	=> $startDate,
				],

				"amount"		=> [
					"left"		=> round((float)$data->amount_spent,0),
					"right"		=> round((float)$data->balance,0),
					"text"		=> "DKK",
				],
				"amount_circle"	=> [
					"from"			=> round((float)$data->amount_spent,0),
					"to"			=> round((float)$data->balance,0),
					"percent"		=> $amountCirclePercent,
					"under_text"	=> $this->lang->get("ActivityAndPayment"),
				],

				"timer"	=> [
					"left"	=> $spentHours,
					"right"	=> round($data->meeting_duration, 1),
					"text"	=> $this->lang->get("Hours", "Hour(s)"),
				],
				"timer_circle"	=>	[
					"from"			=> round((float)$spentHours,2),
					"to"			=> round((float)$timerMonthly, 2),
					"percent"		=> round(abs($spentHours/$timerMonthly) * 100),
					"under_text"	=> $this->lang->get("TimeRecording"),
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
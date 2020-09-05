<?php
namespace Controllers;

use Lib\MyMongo;
use Models\CaseBookings;
use Models\CasePlans;
use Models\Cases;
use Models\Documents;
use Models\Cache;
use Models\Partner;
use Models\TempFiles;
use Models\Users;

class CasesController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("employees", Users::find([["type" => "employee","is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("hasDatatable", true);
	}

	public function listAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $id){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					Cases::update(
						[
							"id" => (int)$id
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> $this->mymongo->getDate()
						]
					);

					CaseBookings::update(
                        [
                            "case_id" => (int)$id
                        ],
                        [
                            "is_deleted"	=> 1,
                            "deleted_by"	=> "moderator",
                            "moderator_id"	=> $this->auth->getData()->id,
                            "deleted_at"	=> $this->mymongo->getDate()
                        ]
                    );
				}else{
					$error = "";
				}
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $this->lang->get("ExecutedSuccessfully", "Executed successfully"); // pass custom message(useful for getting status of group actions)
			}
		}
		$iDisplayLength = intval($this->request->get('length'));
		$iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
		$iDisplayStart = intval($this->request->get('start'));
		$sEcho = intval($this->request->get('draw'));

		$records["data"] = array();

		$order = $this->request->get("order");
		$order_column = $order[0]["column"];
		$order_sort = $order[0]["dir"];

		$binds = [];
		$search = true;

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if (in_array($this->request->get("active"), ["0","1","2"]))
			$binds["active"] = (int)$this->request->get("active");

		if ($this->request->get("employee") > 0)
			$binds["employee"] = (int)$this->request->get("employee");

		if ($this->request->get("citizen") > 0)
			$binds["citizen"] = (int)$this->request->get("citizen");

		if ($this->request->get("type") == "user")
			$binds["citizen"] = (int)$this->request->get("id");

		if ($this->request->get("type") == "employee")
			$binds["employee"] = (int)$this->request->get("id");

		if ($this->request->get("type") == "partner")
			$binds["contact_person"] = (int)$this->request->get("id");

		if ($this->request->get("focus_type") > 0)
			$binds["focus_type"] = (int)$this->request->get("focus_type");

		if ($this->request->get("focus_area") > 0)
			$binds["focus_area"] = (int)$this->request->get("focus_area");


		$binds["is_deleted"] = 0;

		switch($order_sort)
		{
			default:
				$order_sort = -1;
				break;
			case "desc":
				$order_sort = 1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["created_at" => $order_sort];
				break;
		}
		$data =  Cases::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);

		$count = Cases::count([
			$binds,
		]);

		$ids = [
			"citizens" 		=> [],
			"employees" 	=> [],
			"focus_areas" 	=> [],
			"focus_types" 	=> [],
		];

		foreach($data as $value)
		{
			$ids["citizens"] 	= array_unique(array_merge($ids["citizens"], $value->citizen));
			$ids["employees"] 	= array_unique(array_merge($ids["employees"], $value->employee));
			$ids["focus_areas"] = array_unique(array_merge($ids["focus_areas"], $value->focus_area));
			$ids["focus_types"] = array_unique(array_merge($ids["focus_types"], $value->focus_type));
		}
		$citizens = [];
		if(count($ids["citizens"]) > 0)
		{
			$query = Users::find(["id" => ['$in' => $ids["citizens"]]]);
			foreach($query as $value)
				$citizens[$value->id] = $value;
		}
		$employees = [];
		if(count($ids["employees"]) > 0)
		{
			$query = Users::find(["id" => ['$in' => $ids["employees"]]]);
			foreach($query as $value)
				$employees[$value->id] = $value;
		}
		$focus_areas 	= count($ids["focus_areas"]) > 0 ? $this->parameters->getListByIds($this->lang, "focusareas", $ids["focus_areas"], true): [];
		$focus_types 	= count($ids["focus_types"]) > 0 ? $this->parameters->getListByIds($this->lang, "focustypes", $ids["focus_types"], true): [];

		if ($data)
		{
			foreach($data as $value)
			{
				$list 	= [];
				$list[] = '<input type="checkbox" name="id[]" value="'.$value->id.'">';
				$list[] = $value->id;
				$list[] = htmlspecialchars(@$citizens[$value->citizen[0]]->firstname." ".@$citizens[$value->citizen[0]]->lastname);
				$list[] = htmlspecialchars(@$employees[$value->employee[0]]->firstname." ".@$employees[$value->employee[0]]->lastname);
				$list[] = htmlspecialchars(@$focus_areas[$value->focus_area[0]]["title"]);
				$list[] = htmlspecialchars(@$focus_types[$value->focus_type[0]]["title"]);
				$list[] = '<a href="'._PANEL_ROOT_.'/case/'.$value->id.'" class=""><i class="la la-user"></i></a>';
					//'<a href="'._PANEL_ROOT_.'/cases/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>';

				$records["data"][] = $list;
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function addAction()
	{
		$error 		= false;
		$success 	= false;

		$title 					= trim($this->request->get("title"));
		$report_type 			= (int)$this->request->get("report_type");
		$focus_area 			= (int)$this->request->get("focus_area");
		$focus_type 			= (int)$this->request->get("focus_type");
		$case_category 			= (int)$this->request->get("case_category");
		$partner 				= (int)$this->request->get("partner");
		$report_interval 		= trim($this->request->get("report_interval"));
		$meeting_duration 		= (float)$this->request->get("meeting_duration");
		$meeting_duration_type 	= (string)$this->request->get("meeting_duration_type") == "month" ? "month": "week";
		$activity_budget 		= (float)$this->lib->danishToFloat($this->request->get("activity_budget"));
		$activity_budget_max 	= (float)$this->lib->danishToFloat($this->request->get("activity_budget_max"));
		$activity_budget_type 	= trim($this->request->get("activity_budget_type"));
		$start_date 			= strtotime($this->lib->dateFomDanish(trim($this->request->get("start_date"))));
		$next_service_date 		= strtotime($this->lib->dateFomDanish(trim($this->request->get("next_service_date"))));

		$citizen 				= (int)$this->request->get("citizen");
		$employee 				= (int)$this->request->get("employee");
		$empArr                 = explode("_", $this->request->get("employee"));
		$date 				    = $empArr[1];
		$time 				    = (int)$empArr[2];
		$contact_persons 		= [];
		foreach($this->request->get("contact_person")  as $value)
			$contact_persons[] = (int)$value;

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("obsdjAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif ($focus_area == 0)
			{
				$error = $this->lang->get("FocusAreaError", "Focus area is empty");
			}
			else
			{
				$nextReportDate = Cases::getNextMeetingDate($report_interval, $start_date);

				$caseId 	= Cases::getNewId();
				$userInsert = [
					"id"					=> $caseId,
					"title"					=> substr($title, 0, 1000),
					"report_type"			=> $report_type,
					"focus_area"			=> [$focus_area],
					"focus_type"			=> [$focus_type],
					"case_category"			=> [$case_category],
					"partner"				=> $partner,
					"report_interval"		=> $report_interval,
					"activity_budget"		=> $activity_budget,
					"activity_budget_max"	=> $activity_budget_max,
					"activity_budget_type"	=> $activity_budget_type,
					"meeting_duration"		=> $meeting_duration,
					"meeting_duration_type"	=> $meeting_duration_type,
					"start_date"			=> $this->mymongo->getDate($start_date),
					"next_service_date"		=> $this->mymongo->getDate($next_service_date),
					"citizen"				=> [$citizen],
					"employee"				=> [$employee],
					"contact_person"		=> $contact_persons,
					"active"				=> 1,
					"is_deleted"			=> 0,
					"next_report_date"		=> $nextReportDate,
					//"next_report_date"		=> $this->mymongo->getDate($start_date),
					"created_at"			=> $this->mymongo->getDate()
				];

				$caseInsertId = (string)Cases::insert($userInsert);

				$timeArr        = explode("-", CaseBookings::$hours[$time]);
				$bookStartTime  = strtotime($date." ".$timeArr[0].":00");
				$bookEndTime    = strtotime($date." ".$timeArr[1].":00");

				$B                      = new CaseBookings();
				$B->case_id             = (int)$caseId;
				$B->employee_id         = (int)$employee;
				$B->date                = (int)$employee;
				$B->date_start          = Cases::getDate($bookStartTime);
				$B->date_end            = Cases::getDate($bookEndTime);
				$B->dateraw             = $date."_".CaseBookings::$hours[$time];
				$B->created_at          = CaseBookings::getDate();
				$B->save();

				$this->view->setVar("caseId", $caseId);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

        $bookings = CaseBookings::getNextDatesOfEmps();

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("reportTypes", Cases::getReportTypes($this->lang));
		$this->view->setVar("partners", Partner::find([["is_deleted" => 0]]));
		$this->view->setVar("customers", Users::find([["type" => "user", "is_deleted" => 0], "sort" => ["firstname" => 1]]));
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]));
		$this->view->setVar("bookings", $bookings);
	}

	public function employeesAction(){
        $date 					= trim($this->request->get("date"));
        if(strlen($date) < 3)
            exit(json_encode(["status" => "error"]));

        $timestamp = strtotime($date." 00:00:00");

        $empQuery = Users::find([["type" => "employee", "is_deleted" => ['$ne' => 1]]]);
        $employees = [];
        foreach ($empQuery as $value)
            $employees[$value->id] = $value;

        $bookingQuery = CaseBookings::find([[
            "date_start"    => ['$gt' => CaseBookings::getDate(time()-24*3600)],
            "is_deleted"    => ['$ne' => 1]
        ]]);

        $bookings = [];
        foreach ($bookingQuery as $value){
            $bookings[$value->employee_id][$value->dateraw] = 1;
        }


        $data = [];

        $employees = Users::find([["type" => "employee", "is_deleted" => 0], "sort" => ["firstname" => 1]]);

        foreach ($employees as $value)
        {
                if(!$data[$value->id] && in_array((int)date("w", $timestamp), $value->weekdays))
                {
                    foreach (CaseBookings::$hours as $key => $hour){
                        $dateRaw = $date."_".$hour;
                        if(!$bookings[$value->id][$dateRaw]){
                            $data[] = [
                                "value"     => (int)$value->id."_".$date,
                                "title"     => $hour." ".date("d/m/Y", $timestamp)." - ".htmlspecialchars($value->firstname)." ".htmlspecialchars($value->lastname),
                            ];
                        }
                    }
                }
        }

        exit(json_encode(["status" => "success","data"=>$data]));
    }

	public function deleteAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Cases::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif((int)$this->request->get("verify") == 1)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> 0,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Cases::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
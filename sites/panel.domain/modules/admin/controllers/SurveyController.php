<?php
namespace Controllers;

use Models\Cache;
use Models\Offers;
use Models\Survey;
use Models\SurveyLogs;
use Models\SurveyUsers;
use Models\Users;

class SurveyController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function usersAction($id)
	{
		$this->view->setVar("id", $id);
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
					Survey::update(
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

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("title"))
			$binds["title"] = [
				'$regex' => trim(strtolower($this->request->get("title"))),
				'$options'  => 'i'
			];

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
		$data = Survey::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Survey::count([
			$binds,
		]);

		$userIds = [];

		foreach($data as $value)
		{
			$userIds[] 	= (int)$value->citizen;
			$userIds[] 	= (int)$value->employee;
		}
		$userIds 		= array_unique($userIds);
		$usersData 		= [];
		if(count($userIds) > 0)
		{
			$query = Users::find(["id" => ['$in' => $userIds]]);
			foreach($query as $value)
				$usersData[$value->id] = $value;
		}

		if ($data)
		{
			foreach($data as $value)
			{
				$list = [];
				$list[] = '<input type="checkbox" name="id[]" value="'.$value->id.'">';
				$list[] = $value->id;
				$list[] = htmlspecialchars($value->title);
				$list[] = @$this->mymongo->dateFormat($value->created_at, "d.m.y");
				$list[] = '<a href="'._PANEL_ROOT_.'/survey/users/'.$value->id.'" class=""><i class="la la-users"></i></a>';
				$list[] = '<a href="'._PANEL_ROOT_.'/survey/send/'.$value->id.'" class=""><i class="la la-envelope"></i></a>';
				$list[] = '<a href="'._PANEL_ROOT_.'/survey/query/'.$value->id.'" class=""><i class="la la-edit"></i></a>';
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

	public function userslistAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $id){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					SurveyUsers::update(
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

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("title"))
			$binds["title"] = [
				'$regex' => trim(strtolower($this->request->get("title"))),
				'$options'  => 'i'
			];

		$survey 				= Survey::getById((int)$this->request->get("survey_id"));
		$binds["is_deleted"] 	= 0;
		$binds["survey_id"] 	= (string)$survey->_id;

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
		$data = SurveyUsers::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = SurveyUsers::count([
			$binds,
		]);

		$userIds = [];

		foreach($data as $value)
		{
			$userIds[] 	= (int)$value->citizen;
			$userIds[] 	= (int)$value->employee;
		}
		$userIds 		= array_unique($userIds);
		$usersData 		= [];
		if(count($userIds) > 0)
		{
			$query = Users::find(["id" => ['$in' => $userIds]]);
			foreach($query as $value)
				$usersData[$value->id] = $value;
		}

		if ($data)
		{
			foreach($data as $value)
			{
				$list = [];
				$list[] = '<input type="checkbox" name="id[]" value="'.$value->id.'">';
				$list[] = htmlspecialchars($value->subject);
				$list[] = htmlspecialchars($value->email);
				$list[] = @$this->mymongo->dateFormat($value->created_at, "d.m.y");
				$list[] = $value->is_filled == 1 ? '<span class="btn-success">filled</span>': '<span class="btn-danger">not filled</span>';
				$list[] = '<a href="'._PANEL_ROOT_.'/survey/response?id='.$value->id.'" class=""><i class="la la-file"></i></a>';
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

	public function viewAction($id)
	{
		$data = Offers::getById($id);
		if(!$data)
			header("location: "._PANEL_ROOT_."/");

		$this->view->setVar("data", $data);
	}

	public function queryAction($id)
	{
		$error 			= false;
		$success 		= false;

		$data			= false;
		if($id > 0)
			$data = Survey::getById($id);

		$title 			= trim($this->request->get("title"));
		$description 	= trim($this->request->get("description"));
		$json 			= json_decode($this->request->get("survey_json"), true);
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asdAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{
				if($data)
				{
					$update = [
						"title"						=> $title,
						"description"				=> $description,
						"queries"					=> count($json) > 0 ? json_encode($json, true): '[]',
						"updated_at"				=> $this->mymongo->getDate()
					];

					Survey::update(["id" => (int)$id], $update);


					$logIns = [
						"survey_id"					=> (int)$id,
						"title"						=> $title,
						"description"				=> $description,
						"queries"					=> $data->queries,
						"created_at"				=> $this->mymongo->getDate()
					];

					SurveyLogs::insert($logIns);
				}
				else
				{
					$id = Survey::getNewId();
					$userInsert = [
						"id"						=> (int)$id,
						"title"						=> $title,
						"description"				=> $description,
						"queries"					=> count($json) > 0 ? json_encode($json, true): '[]',
						"is_deleted"				=> 0,
						"created_at"				=> $this->mymongo->getDate()
					];

					Survey::insert($userInsert);
				}

				$data = Survey::getById($id);

				$success = $this->lang->get("SavedSuccessfully", "Saved successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
	}

	public function responseAction()
	{
		$error 			= false;
		$success 		= false;
		$id 			= trim($this->request->get("id"));


		$data			= false;
		if(strlen($id) > 0)
			$data = SurveyUsers::getById($id);

		$survey = Survey::findById($data->survey_id);

		$json 			= json_decode($this->request->get("survey_json"), true);
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asdAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{
				if($data)
				{
					$update = [
						"title"						=> $title,
						"description"				=> $description,
						"queries"					=> count($json) > 0 ? json_encode($json, true): '[]',
						"updated_at"				=> $this->mymongo->getDate()
					];

					Survey::update(["id" => (int)$id], $update);


					$logIns = [
						"survey_id"					=> (int)$id,
						"title"						=> $title,
						"description"				=> $description,
						"queries"					=> $data->queries,
						"created_at"				=> $this->mymongo->getDate()
					];

					SurveyLogs::insert($logIns);
				}

				$data = SurveyUsers::getById($id);

				$success = $this->lang->get("SavedSuccessfully", "Saved successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
		$this->view->setVar("survey", $survey);
	}

	public function sendAction($id)
	{
		$error 			= false;
		$success 		= false;

		$survey 		= Survey::getById($id);

		$subject 		= trim($this->request->get("subject"));
		$email 			= trim(strtolower($this->request->get("email")));
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asdAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (!$survey)
			{
				$error = $this->lang->get("NotFound");
			}
			elseif (strlen($subject) == 0)
			{
				$error = $this->lang->get("PleaseFillAllFields", "Please, fill all fields");
			}
			elseif (strlen($email) > 0 && !filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			else
			{
				$newId = SurveyUsers::getNewId();
				$code = (string)rand(111111, 999999);



				$userInsert = [
					"id"						=> $newId,
					"survey_id"					=> (string)$survey->_id,
					"email"						=> $email,
					"subject"					=> $subject,
					"code"						=> $code,
					"is_read"					=> 0,
					"is_filled"					=> 0,
					"queries"					=> $survey->queries,
					"is_deleted"				=> 0,
					"moderator_id"				=> $this->auth->getData()->id,
					"created_at"				=> $this->mymongo->getDate()
				];

				$insId = SurveyUsers::insert($userInsert);

				$layout = file_get_contents("mailtemplates/survey.html");
				$layout = str_replace('{LINK}', "https://denmarkcron.besfly.com/survey?hash=".$insId."&survey=".(string)$survey->_id, $layout);
				$layout = str_replace('{date}', date("d.m.Y"), $layout);

				$mailUrl = EMAIL_DOMAIN;
				$vars = [
					"key"			=> "q1w2e3r4t5aqswdefrgt",
					"from"			=> "info@shahmar.info",
					"to"			=> $email,
					"subject"		=> $subject,
					"content"		=> $layout,
				];

				$response = $this->lib->initCurl($mailUrl, $vars, "post");

				$success = $this->lang->get("SentSuccessfully", "Sent successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
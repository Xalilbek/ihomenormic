<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Contracts;
use Models\ContractTemplates;
use Models\NoteFolders;
use Models\Cache;
use Models\Offers;
use Models\Users;

class ContractsController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function usersAction()
	{
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
					ContractTemplates::update(
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
		$data = ContractTemplates::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = ContractTemplates::count([
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
				$list[] = htmlspecialchars(@$value->title);
				$list[] = @$this->mymongo->dateFormat($value->created_at, "d.m.y");
				//$list[] = '<a href="'._PANEL_ROOT_.'/contracts/users/'.$value->id.'" class=""><i class="la la-users"></i></a>';
				$list[] = '<a href="'._PANEL_ROOT_.'/contracts/view/'.$value->id.'" class=""><i class="la la-file"></i></a>';
				//$list[] = '<a href="'._PANEL_ROOT_.'/contracts/edit/'.$value->id.'" class=""><i class="la la-edit"></i></a>';
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
					Contracts::update(
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

		if ($this->request->get("template_id") > 0)
			$binds["created_at"] = $this->request->get("template_id");

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
		$data = Contracts::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Contracts::count([
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
				$list[] = htmlspecialchars($value->subject);
				$list[] = htmlspecialchars($value->email);
				$list[] = @$this->mymongo->dateFormat($value->created_at, "d.m.y");
				//$list[] = $value->is_filled == 1 ? '<span class="btn-success">filled</span>': '<span class="btn-danger">not filled</span>';
				$list[] = '<a href="'._PANEL_ROOT_.'/contracts/view/'.$value->id.'" class=""><i class="la la-file"></i></a>';
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
		$data = Contracts::getById($id);
		if(!$data)
			header("location: "._PANEL_ROOT_."/");

		$this->view->setVar("data", $data);
	}

	public function sendAction($id)
	{
		$data = ContractTemplates::getById($id);
		if(!$data)
			header("location: "._PANEL_ROOT_."/");

		$error 				= false;
		$success 			= false;
		$subject 			= trim($this->request->get("subject"));
		$email 				= trim($this->request->get("email"));
		$citizen 			= (int)$this->request->get("citizen");
		$employee 			= (int)$this->request->get("employee");
		$contactperson 		= (int)$this->request->get("contact_person");
		$custom1 			= trim($this->request->get("custom1"));
		$custom2 			= trim($this->request->get("custom2"));
		$custom3 			= trim($this->request->get("custom3"));

		$layout = $data->content;

		if($citizenData = Users::getById($citizen))
			$layout = str_replace("{citizen}", $citizenData->firstname." ".$citizenData->lastname, $layout);
		if($employeeData = Users::getById($employee))
			$layout = str_replace("{employee}", $employeeData->firstname." ".$employeeData->lastname, $layout);
		if($contactpersonData = Users::getById($contactperson))
			$layout = str_replace("{contactperson}", $contactpersonData->firstname." ".$contactpersonData->lastname, $layout);
		$layout = str_replace("{custom1}", $custom1, $layout);
		$layout = str_replace("{custom2}", $custom2, $layout);
		$layout = str_replace("{custom3}", $custom3, $layout);
		$layout = str_replace("{date}", date("d.m.Y"), $layout);
		if((int)$this->request->get("preview") == 1)
		{
			$data->content = $layout;
		}
		elseif((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("conadd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($subject) < 1)
			{
				$error = $this->lang->get("TitleError", "Title is wrong");
			}
			else
			{
				$data->content = $layout;

				$mailUrl = EMAIL_DOMAIN;
				$vars = [
					"key"			=> "q1w2e3r4t5aqswdefrgt",
					"from"			=> "info@shahmar.info",
					"to"			=> htmlspecialchars($email),
					"subject"		=> htmlspecialchars($subject),
					"content"		=> $layout,
				];

				$response = $this->lib->initCurl($mailUrl, $vars, "post");
				//exit($response);

				$userInsert = [
					"id"						=> Contracts::getNewId(),
					"template_id"				=> (int)$id,
					"subject"					=> $subject,
					"citizen"					=> $citizen,
					"employee"					=> $employee,
					"contact_person"			=> $contactperson,
					"moderator_id"				=> $this->auth->getData()->id,
					"email"						=> $email,
					"is_secure"					=> (int)0,
					"code"						=> 0,
					"content"					=> $layout,
					"is_read"					=> 0,
					"is_deleted"				=> 0,
					"response"					=> $response,
					"created_at"				=> $this->mymongo->getDate()
				];

				Contracts::insert($userInsert);

				$success = $this->lang->get("SentSuccessfully", "Sent successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => 0]]));
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0]]));
		$this->view->setVar("contactpersons", Users::find([["type" => "partner", "is_deleted" => 0]]));
		$this->view->setVar("data", $data);
	}

	public function addAction()
	{
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		$description 	= $this->request->get("description");
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("conadd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 0)
			{
				$error = $this->lang->get("TitleError", "Title is wrong");
			}
			else
			{
				$layout = file_get_contents("mailtemplates/layout.html");
				$layout = str_replace('{CONTENT}', $description, $layout);

				$userInsert = [
					"id"						=> ContractTemplates::getNewId(),
					"title"						=> $title,
					"content"					=> $layout,
					"is_deleted"				=> 0,
					"created_at"				=> $this->mymongo->getDate()
				];

				ContractTemplates::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function editAction($id)
	{
		$error 			= false;
		$success 		= false;

		$data = ContractTemplates::getById($id);
		if(!$data)
			header("location: "._PANEL_ROOT_."/");


		$title 			= trim($this->request->get("title"));
		$description 	= $this->request->get("description");
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("conedit-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 0)
			{
				$error = $this->lang->get("TitleError", "Title is wrong");
			}
			else
			{
				$layout = file_get_contents("mailtemplates/layout.html");
				$layout = str_replace('{CONTENT}', $description, $layout);

				$update = [
					"title"						=> $title,
					"content"					=> $layout,
					"updated_at"				=> $this->mymongo->getDate()
				];

				ContractTemplates::update(["_id" => $data->_id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
	}
}
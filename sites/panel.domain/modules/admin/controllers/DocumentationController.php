<?php
namespace Controllers;

use Models\Cache;
use Models\Documentation;
use Models\Users;

class DocumentationController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		if(!$this->auth->isPermitted($this->lang, "moderators", "view")){
			header("Location: "._PANEL_ROOT_."/");
		}
	}

	public function indexAction()
	{
		$this->view->setVar("hasDatatable", true);

	}

	public function listAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $id){
				$error = false;
				$item = Users::findById($id);
				$status = (int)$this->request->get("customActionName");
				$status = (in_array($status, [0,1,2])) ? $status: 2;
				$item->active = $status;
				//$item->save();
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = RequestDoneSuccessfully; // pass custom message(useful for getting status of group actions)
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
			$binds["id"] = $this->request->get("item_id");

		if ($this->request->get("date_from")){
			$binds["date_from"] = $this->request->get("date_from");
		}

		if ($this->request->get("date_to")){
			$binds["date_to"] = $this->request->get("date_to");
		}

		if ($this->request->get("fullname")){
			$binds["fullname"] = [
				'$regex' => trim(strtolower($this->request->get("fullname"))),
				'$options'  => 'i'
			];
		}

		if (in_array($this->request->get("active"), ["0","1","2"])){
			$binds["active"] = (int)$this->request->get("active");
		}

		if ($this->request->get("template_id") > 0){
			$binds["template_id"] = (int)$this->request->get("template_id");
		}

		$binds["type"] = "moderator";
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
		$data = Users::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Users::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = array(
					$value->id,
					htmlspecialchars($value->titles->en),
					'<a href="'._PANEL_ROOT_.'/documentation?parent='.$value->id.'" class=""><i class="la la-file"></i></a>',
					'<a href="'._PANEL_ROOT_.'/documentation/edit/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
				);
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function editAction($id)
	{
		$error 			= false;
		$success 		= false;

		$parent 		= (int)($this->request->get("parent"));
		$data			= Documentation::getById($id);
		if(!$data)
			$data = new Documentation();

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("moderatorAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{

				$methods = [];
				foreach($this->request->get("methods") as $value)
					$methods[] = $value;
				$data->titles 		= ["en"	=> $this->request->get("title")];
				$data->url			= trim($this->request->get("url"));
				$data->methods		= $methods;
				$data->created_at	= Documentation::getDate();
				$data->save();

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("data", $data);
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
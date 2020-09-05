<?php
namespace Controllers;

use Lib\MyMongo;
use Models\KnowledgebaseItems;
use Models\Notes;
use Models\Cache;
use Models\Users;

class KnowledgebaseitemsController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
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
					KnowledgebaseItems::update(
						[
							"id" => (int)$id
						],
						[
							"is_deleted"	=> 1,
							"deleted_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> MyMongo::getDate()
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

		if ($this->request->get("folder"))
			$binds["folder"] = (int)$this->request->get("folder");

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
		$data = KnowledgebaseItems::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = KnowledgebaseItems::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					htmlspecialchars($value->title),
					@$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					'<a href="'._PANEL_ROOT_.'/knowledgebaseitems/edit/'.$value->id.'" class=""><i class="la la-file"></i></a>'
					//'<a href="'._PANEL_ROOT_.'/knowledgebaseitems/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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

	public function addAction()
	{
		exit;
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		$description 	= $this->request->get("description");
		$folder 		= (int)$this->request->get("folder");
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError", "Title is empty");
			}
			elseif (strlen($description) < 1 || strlen($description) > 200000)
			{
				$error = $this->lang->get("DescriptionError", "Description is empty");
			}
			else
			{
				$userInsert = [
					"id"						=> KnowledgebaseItems::getNewId(),
					"title"						=> $title,
					"description"				=> $description,
					"folder"					=> $folder,
					"active"					=> 1,
					"is_deleted"				=> 0,
					"created_at"				=> MyMongo::getDate()
				];

				KnowledgebaseItems::insert($userInsert);

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

		$data 			= KnowledgebaseItems::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$title 			= trim($this->request->get("title"));
		$description 	= $this->request->get("description");
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError", "Title is empty");
			}
			elseif (strlen($description) < 1 || strlen($description) > 200000)
			{
				$error = $this->lang->get("DescriptionError", "Description is empty");
			}
			else
			{
				$update = [
					"title"						=> $title,
					"description"				=> $description,
					"updated_at"				=> MyMongo::getDate()
				];
				//KnowledgebaseItems::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
	}

	public function deleteAction($id)
	{
		exit;
		$error 		= false;
		$success 	= false;
		$data 		= KnowledgebaseItems::findFirst([
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
				"deleted_at"	=> MyMongo::getDate()
			];
			KnowledgebaseItems::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
	}
}
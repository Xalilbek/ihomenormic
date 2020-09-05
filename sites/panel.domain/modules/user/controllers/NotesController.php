<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cases;
use Models\NoteFolders;
use Models\Cache;
use Models\Users;

class NotesController extends \Phalcon\Mvc\Controller
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
					NoteFolders::update(
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
		$binds["employee"] = (int)$this->auth->getData()->id;
		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

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
		$data = NoteFolders::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = NoteFolders::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$list = [];
				$list[] = '<input type="checkbox" name="id[]" value="'.$value->id.'">';
				$list[] = htmlspecialchars($value->title);
				$list[] = @$this->mymongo->dateFormat($value->created_at, "d.m.y");
				$list[] = '<a href="'._PANEL_ROOT_.'/noteitems?folder='.$value->id.'" class=""><i class="la la-folder-open yellowcolor"></i></a>';
				$list[] = '<a href="'._PANEL_ROOT_.'/notes/edit/'.$value->id.'" class=""><i class="la la-edit"></i></a>';
				//'<a href="'._PANEL_ROOT_.'/notes/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asdAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($title) < 1 || strlen($title) > 400)
			{
				$error = $this->lang->get("TitleError", "Title is empty");
			}
			else
			{
				$userInsert = [
					"id"						=> NoteFolders::getNewId(),
					"creator_id"				=> (int)$this->auth->getData()->id,
					"title"						=> $title,
					"active"					=> 1,
					"is_deleted"				=> 0,
					"created_at"				=> MyMongo::getDate()
				];

				if($this->auth->getData()->type == "employee"){
					$userInsert["employee"] 	 = (int)$this->auth->getData()->id;
				}else{
					$userInsert["citizen"] 	 = (int)$this->auth->getData()->id;
				}

				NoteFolders::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function editAction($id)
	{
		$error = false;
		$success = false;

		$data = NoteFolders::getById($id);
		if (!$data)
			header("Location: " . _PANEL_ROOT_ . "/");

		$title = trim($this->request->get("title"));
		if ((int)$this->request->get("save") == 1) {
			if (Cache::is_brute_force("asdAdd-" . $this->request->getServer("REMOTE_ADDR"), ["minute" => 40, "hour" => 300, "day" => 900])) {
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			} elseif (strlen($title) < 1 || strlen($title) > 400) {
				$error = $this->lang->get("TitleError", "Title is empty");
			} else {
				$update = [
					"id" => NoteFolders::getNewId(),
					"title" => $title,
					"updated_at" => MyMongo::getDate()
				];
				NoteFolders::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
	}

	public function deleteAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= NoteFolders::findFirst([
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
			NoteFolders::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
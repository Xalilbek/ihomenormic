<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Users;

class ModeratorsController extends \Phalcon\Mvc\Controller
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
				$status = trim($this->request->get("customActionName"));
				if($status == "active")
				{
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"active"		=> 1,
							"active_by"		=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> Users::getDate()
						]
					);
				}
				elseif($status == "inactive")
				{
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"active"		=> 0,
							"inactive_by"	=> "moderator",
							"moderator_id"	=> $this->auth->getData()->id,
							"deleted_at"	=> Users::getDate()
						]
					);
				}
				elseif($status == "block")
				{
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"is_blocked" => 1,
							"blocked_by" => $this->auth->getData()->id,
							"blocked_at" => Users::getDate()
						]
					);
				}
				elseif($status == "delete")
				{
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"is_deleted" => 1,
							"deleted_by" => $this->auth->getData()->id,
							"deleted_at" => Users::getDate()
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
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					htmlspecialchars($value->username),
					htmlspecialchars($value->firstname." ".$value->lastname).((int)$value->active == 0 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Inactive"))."</font> ": "").((int)$value->is_blocked == 1 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Blocked"))."</font>": ""),
					htmlspecialchars($value->phone),
					'<a href="'._PANEL_ROOT_.'/profile/index/'.$value->id.'" class=""><i class="la la-edit"></i></a>'
					//'<a href="'._PANEL_ROOT_.'/moderators/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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
		if(!$this->auth->isPermitted($this->lang, "moderators", "add"))
			exit("NO_ACCESS");
		$error 			= false;
		$success 		= false;

		$username 		= trim(strtolower($this->request->get("mod_username")));
		$password 		= trim($this->request->get("password"));
		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
		$phone 			= trim($this->request->get("phone"));
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("moderatorAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($username) < 1 || strlen($username) > 400)
			{
				$error = $this->lang->get("FieldsAreEmpty", "Please, fill all fields");
			}
			elseif (strlen($password) < 6 || strlen($password) > 400)
			{
				$error = $this->lang->get("PasswordError", "Password is wrong. (minimum 6 characters)");
			}
			elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
			{
				$error = $this->lang->get("FirstnameError", "Firstname is empty");
			}
			elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
			{
				$error = $this->lang->get("LastnameError", "Lastname is empty");
			}
			elseif (Users::findFirst([["username" => $username, "is_deleted" => ['$ne' => 1]]]))
			{
				$error = $this->lang->get("UsernameExists", "Username exists");
			}
			else
			{
				$id = Users::getNewId();
				$userInsert = [
					"id"							=> $id,
					"username"						=> $username,
					"password" 						=> $this->lib->generatePassword($password),
					"firstname"						=> $firstname,
					"lastname"						=> $lastname,
					"phone"							=> $phone,
					"only_self"						=> 0,
					"permissions"					=> [],
					"is_deleted"					=> 0,
					"status"					    => 1,
					"active"					    => 1,
					"type"							=> "moderator",
					"created_at"					=> $this->mymongo->getDate()
				];

				Users::insert($userInsert);
				$this->view->setVar("id", $id);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function editAction($id)
	{
		if(!$this->auth->isPermitted($this->lang, "moderators", "edit") && (int)$id !== (int)$this->auth->getData()->id)
			exit("NO_ACCESS");
		$error 			= false;
		$success 		= false;

		$data 			= Users::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$username 		= trim(strtolower($this->request->get("mod_username")));
		$password 		= trim($this->request->get("password"));
		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
		$phone 			= trim($this->request->get("phone"));
		$only_self 		= (int)($this->request->get("only_self"));
		$permissions    = json_decode(trim($this->request->get("permissions")), true);

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("moderatorEdit-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($username) < 1 || strlen($username) > 400)
			{
				$error = $this->lang->get("FieldsAreEmpty", "Please, fill all fields");
			}
			elseif (strlen($password) > 0 && strlen($password) < 6)
			{
				$error = $this->lang->get("PasswordError", "Password is wrong. (minimum 6 characters)");
			}
			elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
			{
				$error = $this->lang->get("FirstnameError", "Firstname is empty");
			}
			elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
			{
				$error = $this->lang->get("LastnameError", "Lastname is empty");
			}
			elseif (Users::findFirst([["id" => ['$ne' => (int)$id], "username" => $username], "is_deleted" => ['$ne' => 1]]))
			{
				$error = $this->lang->get("UsernameExists", "Username exists");
			}
			else
			{
				$update = [
					"username"						=> $username,
					"firstname"						=> $firstname,
					"lastname"						=> $lastname,
					"phone"							=> $phone,
					"only_self"						=> $only_self,
					//"permissions"					=> count($permissions) > 0 ? $permissions: [],
					"updated_at"					=> $this->mymongo->getDate()
				];
				if(strlen($password) > 0)
					$update["password"] = $this->lib->generatePassword($password);

				Users::update(["id" => (int)$id], $update);

				$data 			= Users::getById($id);

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
		$data 		= Users::findFirst([
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
			Users::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
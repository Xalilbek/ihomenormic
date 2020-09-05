<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Documents;
use Models\Products;
use Models\TempFiles;
use Models\Users;

class ProductsController extends \Phalcon\Mvc\Controller
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
					Products::update(
						[
							"id" => (int)$id
						],
						[
							"active"		=> 1,
							"activator_id"	=> $this->auth->getData()->id,
							"activated_at"	=> Products::getDate()
						]
					);
				}
				elseif($status == "inactive")
				{
					Products::update(
						[
							"id" => (int)$id
						],
						[
							"active"			=> 0,
							"inactivator_id"	=> $this->auth->getData()->id,
							"inactivated_at"	=> Products::getDate()
						]
					);
				}
				elseif($status == "delete")
				{
					Products::update(
						[
							"id" => (int)$id
						],
						[
							"is_deleted" => 1,
							"deleter_id" => $this->auth->getData()->id,
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

		if ($this->request->get("title")){
			$binds["title"] = [
				'$regex' => trim(strtolower($this->request->get("title"))),
				'$options'  => 'i'
			];
		}

		if (in_array($this->request->get("active"), ["0","1","2"])){
			$binds["active"] = (int)$this->request->get("active");
		}

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
		$data = Products::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Products::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					htmlspecialchars($value->title),
					htmlspecialchars($value->amount),
					//htmlspecialchars($value->firstname." ".$value->lastname).((int)$value->active == 0 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Inactive"))."</font> ": "").((int)$value->is_blocked == 1 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Blocked"))."</font>": ""),
					'<a href="'._PANEL_ROOT_.'/products/edit/'.$value->id.'" class=""><i class="la la-edit"></i></a>'
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

	public function editAction($id)
	{
		$error 			= false;
		$success 		= false;
		$data 			= false;

		if($id > 0)
			$data	= Products::getById($id);
		if(!$data)
		{
			$data 					= new Products();
			$data->id				= Products::getNewId();
			$data->active			= 1;
			$data->is_deleted		= 0;
			$data->created_at		= Products::getDate();
			$data->uuid 			= strlen($this->request->get("puid")) > 0 ? trim($this->request->get("puid")): md5(microtime()."-".rand(1,1000000)."-".$this->auth->getData()->id);
		}
		else
		{
			$data->updated_at  		= Products::getDate();
		}



		if((int)$this->request->get("save") == 1)
		{
			$data->title			= trim($this->request->get("title"));
			$data->description 		= trim($this->request->get("description"));
			$data->amount 			= (float)($this->request->get("amount"));
			$data->quantity 		= (float)($this->request->get("quantity"));

			if(Cache::is_brute_force("proAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($data->title) < 1 || strlen($data->title) > 400)
			{
				$error = $this->lang->get("TitleIsWrong", "Title is wrong");
			}
			elseif ($data->amount <= 0)
			{
				$error = $this->lang->get("AmountIsWrong", "Amount is wrong");
			}
			else
			{
				$data->save();

				$success = $id > 0 ? $this->lang->get("UpdatedSuccessfully", "Updated successfully"): $this->lang->get("AddedSuccessfully", "Added successfully");

				$id = $data->id;

				$tempFiles = TempFiles::find([
					[
						"puid" 		=> $data->uuid,
						"active"	=> 1,
					]
				]);
				if($tempFiles)
				{
					foreach($tempFiles as $value)
					{
						$document = [
							"_id"				=> $value->_id,
							"moderator_id"      => (int)$value->moderator_id,
							"title"      		=> (string)$value->title,
							"product_id"      	=> $id,
							"uuid"              => $value->uuid,
							"type"              => $value->type,
							"filename"          => $value->filename,
							"is_deleted"        => 0,
							"created_at"        => Products::getDate(),
						];
						Documents::insert($document);
					}

					TempFiles::update(["puid" => $data->uuid], ["active"	=> 0]);
				}

				if($id > 0)
					$data	= Products::getById($id);
			}
		}

		if(strlen($this->request->get("avatar")) > 0){
			Products::update(["id" => (int)$id], ["avatar_id" => trim($this->request->get("avatar"))]);
			$data->avatar_id = trim($this->request->get("avatar"));
		}

		if(strlen($this->request->get("delete")) > 0){
			if($data->avatar_id == trim($this->request->get("avatar")))
				Products::update(["id" => (int)$id], ["avatar_id" => null]);
			Documents::update(["_id" => Documents::objectId($this->request->get("delete"))], ["is_deleted" => 1]);
		}



		$photos = Documents::find([
			[
				"uuid" 			=> $data->uuid,
				"is_deleted"	=> ['$ne' => 1]
			]
		]);


		$this->view->setVar("id", $id);
		$this->view->setVar("data", $data);
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("photos", $photos);
	}
}
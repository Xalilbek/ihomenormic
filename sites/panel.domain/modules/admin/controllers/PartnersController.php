<?php
namespace Controllers;

use Models\Documents;
use Models\Cache;
use Models\Partner;
use Models\TempFiles;
use Models\Users;

class PartnersController extends \Phalcon\Mvc\Controller
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
				$error = false;
				$status = (string)$this->request->get("customActionName");
				if($status == "block") {
					Partner::update(
						[
							"id" => (int)$id
						],
						[
							"is_blocked" => 1,
							"blocked_by" => $this->auth->getData()->id,
							"blocked_at" => Partner::getDate()
						]
					);
				}
				elseif($status == "unblock")
				{
					Partner::update(
						[
							"id" => (int)$id
						],
						[
							"is_blocked"	=> 0,
							"unblocked_by"	=> $this->auth->getData()->id,
							"unblocked_at"	=> Partner::getDate()
						]
					);
				}elseif($status == "active") {
					Partner::update(
						[
							"id" => (int)$id
						],
						[
							"active" => 1,
						]
					);
				}elseif($status == "inactive") {
					Partner::update(
						[
							"id" => (int)$id
						],
						[
							"active" => 0,
						]
					);
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
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("date_from")){
			$binds["date_from"] = $this->request->get("date_from");
		}

		if ($this->request->get("date_to")){
			$binds["date_to"] = $this->request->get("date_to");
		}

		if ($this->request->get("commune")){
			$binds["commune"] = [
				'$regex' => trim(strtolower($this->request->get("commune"))),
				'$options'  => 'i'
			];
		}

		if (in_array($this->request->get("active"), ["0","1","2"])){
			$binds["active"] = (int)$this->request->get("active");
		}

		if ($this->request->get("phone") > 0){
			$binds["phone"] = (string)trim($this->request->get("phone"));
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
		$data = Partner::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Partner::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					htmlspecialchars($value->name).((int)$value->active == 0 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Inactive"))."</font> ": "").((int)$value->is_blocked == 1 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Blocked"))."</font>": ""),
					htmlspecialchars($value->address),
					'<a href="'._PANEL_ROOT_.'/partnerusers?partner='.$value->id.'" class=""><i class="la la-user "></i></a>',
					'<a href="'._PANEL_ROOT_.'/partners/terms/'.$value->id.'" class=""><i class="la la-file yellowcolor"></i></a>',
					'<a href="'._PANEL_ROOT_.'/partners/documents/'.$value->id.'" class=""><i class="la la-folder-open yellowcolor"></i></a>',
					'<a href="'._PANEL_ROOT_.'/partners/edit/'.$value->id.'" class=""><i class="la la-edit"></i></a>',
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
		$error 			= false;
		$success 		= false;

		$name 			= trim($this->request->get("name"));
		$address 		= trim($this->request->get("address"));

        $puid 				= trim($this->request->get("puid"));
        if(strlen($puid) == 0)
            $puid = md5("partner-".microtime(true)."-".rand(1,9999999));

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($name) < 1 || strlen($name) > 400)
			{
				$error = $this->lang->get("FieldsAreEmpty", "Please, fill all fields");
			}
			/**
			elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			} */
			else
			{
			    $partnerId = Partner::getNewId();
				$userInsert = [
					"id"							=> $partnerId,
					"name"							=> $name,
					"address"						=> $address,
					"active"						=> 1,
					"is_deleted"					=> 0,
					"created_at"					=> $this->mymongo->getDate()
				];

				Partner::insert($userInsert);


                $tempFiles = TempFiles::find([
                    [
                        "puid" 		=> $puid,
                        "active"	=> 1,
                    ]
                ]);
                if($tempFiles)
                {
                    $document = false;
                    foreach($tempFiles as $value)
                    {
                        $document = [
                            "_id"				=> $value->_id,
                            "moderator_id"      => (int)$value->moderator_id,
                            "partner_id"      	=> (int)$partnerId,
                            "uuid"              => $value->uuid,
                            "type"              => $value->type,
                            "doc_type"          => "terms",
                            "filename"          => $value->filename,
                            "is_deleted"        => 0,
                            "created_at"        => $this->mymongo->getDate(),
                        ];
                        Documents::insert($document);
                    }

                    TempFiles::update(["puid" => $puid], ["active"	=> 0]);
                }

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
        $this->view->setVar("puid", $puid);
    }

	public function editAction($id)
	{
		$error 			= false;
		$success 		= false;

		$data 			= Partner::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$name 			= trim($this->request->get("name"));
		$address 		= trim($this->request->get("address"));

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("partnerAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($name) < 1 || strlen($name) > 400)
			{
				$error = $this->lang->get("FieldsAreEmpty", "Please, fill all fields");
			}
			else
			{
				$update = [
					"name"							=> $name,
					"address"						=> $address,
					"updated_at"					=> $this->mymongo->getDate()
				];
				Partner::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
	}

	public function documentsAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Partner::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		$puid 				= trim($this->request->get("puid"));
		if(strlen($puid) == 0)
			$puid = md5("partner-".microtime(true)."-".rand(1,9999999));

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif((int)$this->request->get("save") == 1)
		{
			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if($tempFiles)
			{
				$document = false;
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"partner_id"      	=> (int)$id,
						"uuid"              => $value->uuid,
						"type"              => $value->type,
						"filename"          => $value->filename,
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Documents::insert($document);
				}

				TempFiles::update(["puid" => $puid], ["active"	=> 0]);
			}
			if($document)
			{
				$success = $this->lang->get("UploadedSuccessfully", "Uploaded successfully");
			}
			else
			{
				$success = $this->lang->get("FileNotFound", "File not found");
			}
		}

		$delete = $this->request->get("delete");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$documents = Documents::find([
			[
				"partner_id" 		=> (int)$id,
				"is_deleted"		=> 0,
			],
			"limit"	=> 100
		]);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
		$this->view->setVar("documents", $documents);
	}

	public function termsAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Partner::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

		$puid 				= trim($this->request->get("puid"));
		if(strlen($puid) == 0)
			$puid = md5("partner-".microtime(true)."-".rand(1,9999999));

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif((int)$this->request->get("save") == 1)
		{
			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if($tempFiles)
			{
				$document = false;
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"partner_id"      	=> (int)$id,
						"uuid"              => $value->uuid,
						"type"              => $value->type,
                        "doc_type"          => "terms",
                        "filename"          => $value->filename,
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Documents::insert($document);
				}

				TempFiles::update(["puid" => $puid], ["active"	=> 0]);
			}
			if($document)
			{
				$success = $this->lang->get("UploadedSuccessfully", "Uploaded successfully");
			}
			else
			{
				$success = $this->lang->get("FileNotFound", "File not found");
			}
		}

		$delete = $this->request->get("delete");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$documents = Documents::find([
			[
				"partner_id" 		=> (int)$id,
                "doc_type"          => "terms",
				"is_deleted"		=> 0,
			],
			"limit"	=> 100
		]);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
		$this->view->setVar("documents", $documents);
	}

	public function deleteAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Partner::findFirst([
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
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Partner::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
<?php
namespace Controllers;

use Models\Cache;
use Models\Contacts;
use Models\Contracts;
use Models\ContractTemplates;
use Models\Dialogues;
use Models\DialogueUsers;
use Models\DocumentFolders;
use Models\Documents;
use Models\Notes;
use Models\Partner;
use Models\TempFiles;
use Models\Users;

class ProfileController extends \Phalcon\Mvc\Controller
{
	public $data;

	public function initialize()
	{
		$id = $this->dispatcher->getParam("id");
		//if($this->auth->getData()->type == "employee" && (int)$this->auth->getData()->id !== (int)$id)
		if($this->auth->getData()->type == "employee")
			header("Location: "._PANEL_ROOT_."/");
		$data = Users::findFirst([
			[
				"id"			=> (int)$id,
				//"is_deleted"	=> 0
			]
		]);
		if(!$data)
			header("location: "._PANEL_ROOT_);

		$this->data = $data;
		$this->view->setVar("id", $id);
		$this->view->setVar("data", $data);
	}

	public function indexAction($id)
	{

		$error 		= false;
		$success 	= false;

		$firstname 	= trim($this->request->get("firstname"));
		$lastname 	= trim($this->request->get("lastname"));
		$leakbot_id = trim($this->request->get("leakbot_id"));
		$email 		= trim($this->request->get("email"));
        $type2 			= (int)trim($this->request->get("type2"));
        $ssn 		= trim($this->request->get("ssn"));
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 	= trim($this->request->get("password"));
		$place 		= trim($this->request->get("place"));
		$gender 	= (string)strtolower($this->request->get("gender"));

		$languages 		= [];
		foreach($this->request->get("languages") as $value)
			$languages[] = (int)$value;

		$end_date 	= strtotime($this->lib->dateFomDanish(trim($this->request->get("end_date"))));

		$address 	= trim($this->request->get("address"));
		$zipcode 	= trim($this->request->get("zipcode"));
		$city 		= (int)$this->request->get("city");

		$paymentRegNum  	= trim($this->request->get("payment_registration_number"));
		$paymentAccountNum  = trim($this->request->get("payment_account_number"));

        $partner 	= (int)$this->request->get("partner");

        if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("infoUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 500, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
			{
				$error = $this->lang->get("FirstnameError", "Firstname is empty");
			}
			elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
			{
				$error = $this->lang->get("LastnameError", "Lastname is empty");
			}
			elseif (strlen($email) > 1 && !filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}
			elseif (strlen($password) > 0 && (strlen($password) < 6 || strlen($password) > 100))
			{
				$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
			}
			elseif (strlen($address) > 0 && (strlen($address) < 1 || strlen($address) > 200))
			{
				$error = $this->lang->get("AddressError", "Address is empty");
			}
			elseif (strlen($zipcode) > 0 && (strlen($zipcode) < 1 || strlen($zipcode) > 50))
			{
				$error = $this->lang->get("ZipCodeError", "Zip Code is empty");
			}
			else
			{
				$update = [
					"firstname"		=> $firstname,
					"lastname"		=> $lastname,
					"leakbot_id"	=> $leakbot_id,
                    "partner"		=> $partner,
                    "phone"			=> $phone,
					"email"			=> $email,
					"gender"		=> $gender,

					"birthdate"		=> $this->lib->getBirthdateFromSSN($ssn),

					"languages"		=> $languages,

					"address"		=> $address,
					"zipcode"		=> $zipcode,
					"city"			=> $city,
					"type2"			=> $type2,

					"updated_at"	=> $this->mymongo->getDate()
				];
				if(strlen($password) > 0)
					$update["password"] = $this->lib->generatePassword($password);

				Users::update(["id" => (int)$id], $update);

				$data = Users::findFirst([
					[
						"id"			=> (int)$id,
						//"is_deleted"	=> 0
					]
				]);
				$this->view->setVar("data", $data);

				$success = $this->lang->get("UpdatedSuccessfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
        $this->view->setVar("partners", Partner::find([["is_deleted" => 0]]));
    }

	public function contactsAction($id)
	{
		$contacts = Contacts::find([['user_id' => (int)$this->auth->getData()->id]]);

		$this->view->setVar("contacts", $contacts);
	}

	public function contactsaddAction($id)
	{
		$error 			= false;
		$success 		= false;
		$contact 		= false;

		$contactId 		= trim($this->request->get("contact_id"));
		$title 			= trim($this->request->get("title"));
		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
		$phone 			= trim($this->request->get("phone"));
		$email 			= trim($this->request->get("email"));

		if(strlen($contactId) > 0)
			$contact        = Contacts::getById($contactId);

		if(!$contact)
		{
			$contact 				= new Contacts();
			$contact->created_at 	= Contacts::getDate();
		}
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asasrAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{
				$Insert = [
					"id"						=> Notes::getNewId(),
					"user_id"					=> (int)@$this->auth->getData()->id,
					"title"						=> $title,
					"firstname"					=> $firstname,
					"lastname"					=> $lastname,
					"phone"						=> $phone,
					"email"						=> $email,
					"active"					=> 1,
					"is_deleted"				=> 0,
					"updated_at"				=> $this->mymongo->getDate()
				];

				Contacts::insert($Insert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}


	public function parentsAction($id)
	{
		$error 		= false;
		$success 	= false;

		$firstname 	= trim($this->request->get("firstname"));
		$lastname 	= trim($this->request->get("lastname"));
		$email 		= trim($this->request->get("email"));
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));

		if($this->request->get("save"))
		{
			if(Cache::is_brute_force("infoUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 500, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
			{
				$error = $this->lang->get("FirstnameError", "Firstname is empty");
			}
			elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
			{
				$error = $this->lang->get("LastnameError", "Lastname is empty");
			}
			elseif (strlen($email) > 1 && !filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}
			elseif(Users::findFirst([
				[
					"phone"         => $phone,
					"is_deleted"    => 0,
					"type"			=> ['$ne' => "parent"]
				]
			]))
			{
				$error = $this->lang->get("PhoneExists", "This phone already used for another user");
			}
			else
			{
				if($this->request->get("save") == "mother")
				{
					if($this->data->mother_id > 0)
					{
						$update = [
							"firstname"		=> $firstname,
							"lastname"		=> $lastname,
							"phone"			=> $phone,
							"email"			=> $email,
							"updated_at"	=> $this->mymongo->getDate()
						];
						Users::update(["id" => (int)$this->data->mother_id], $update);
					}
					else
					{
						$userId = Users::getNewId();
						$insert = [
							"id"			=> $userId,
							"firstname"		=> $firstname,
							"lastname"		=> $lastname,
							"phone"			=> $phone,
							"type"			=> "parent",
							"email"			=> $email,
							"gender"		=> "female",
							"active"		=> 1,
							"is_deleted"	=> 0,
							"created_at"	=> $this->mymongo->getDate()
						];
						Users::insert($insert);

						Users::update(["id" => (int)$this->data->id], ["mother_id" => $userId]);
					}

				}
				else
				{
					if($this->data->father_id > 0)
					{
						$update = [
							"firstname"		=> $firstname,
							"lastname"		=> $lastname,
							"phone"			=> $phone,
							"email"			=> $email,
							"updated_at"	=> $this->mymongo->getDate()
						];
						Users::update(["id" => (int)$this->data->father_id], $update);
					}
					else
					{
						$userId = Users::getNewId();
						$insert = [
							"id"			=> $userId,
							"firstname"		=> $firstname,
							"lastname"		=> $lastname,
							"phone"			=> $phone,
							"type"			=> "parent",
							"email"			=> $email,
							"gender"		=> "male",
							"active"		=> 1,
							"is_deleted"	=> 0,
							"created_at"	=> $this->mymongo->getDate()
						];
						Users::insert($insert);

						Users::update(["id" => (int)$this->data->id], ["father_id" => $userId]);
					}
				}

				$data = Users::findFirst([
					[
						"id"			=> (int)$id,
						"is_deleted"	=> 0
					]
				]);
				$this->view->setVar("data", $data);

				$success = $this->lang->get("UpdatedSuccessfully");
			}
		}

		$father = Users::getById($this->data->father_id);
		$mother = Users::getById($this->data->mother_id);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("father", $father);
		$this->view->setVar("mother", $mother);
	}

	public function passwordAction($id)
	{
		$error 		= false;
		$success 	= false;

		$password 	= trim($this->request->get("password"));

		if((int)$this->request->get("save") == 1)
		{
			if (strlen($password) > 0 && (strlen($password) < 6 || strlen($password) > 100))
			{
				$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
			}
			else
			{
				$update = [
					"password"		=> $this->lib->generatePassword($password),
					"updated_at"	=> $this->mymongo->getDate()
				];

				Users::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function casesAction($id)
	{
		$this->view->setVar("hasDatatable", true);
		$this->view->setVar("employees", Users::find([["type" => "employee","is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("citizens", Users::find([["type" => "citizen","is_deleted" => ['$ne' => 1]]]));
	}

	public function photosAction($id)
	{
		$error 		= false;
		$success 	= false;

		$puid 		= trim($this->request->get("puid"));
		if(strlen($puid) == 0)
			$puid = md5("partner-".microtime(true)."-".rand(1,9999999));


		if((int)$this->request->get("save") == 1)
		{
			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if($tempFiles)
			{
				$document = true;
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"user_id"      		=> (int)$id,
						"uuid"              => $value->uuid,
						"type"              => $value->type,
						"for"      			=> "profile",
						"filename"          => $value->filename,
						"is_deleted"        => 0,
						"created_at"        => $this->mymongo->getDate(),
					];
					Documents::insert($document);

					$update = [
						"avatar_id"		=> $value->_id,
						"updated_at"	=> $this->mymongo->getDate()
					];

					Users::update(["id" => (int)$id], $update);
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
			if($this->data->avatar_id == $delete)
			{
				$update = [
					"avatar_id"		=> null,
					"updated_at"	=> $this->mymongo->getDate()
				];

				Users::update(["id" => (int)$id], $update);
			}

			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> $this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];
			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$data = Users::findFirst([
			[
				"id"			=> (int)$id,
				//"is_deleted"	=> 0
			]
		]);

		$this->view->setVar("data", $data);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
	}

	public function chatAction($id)
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= 20;

		$dialogues = DialogueUsers::find([
			[
				"user_id"	=> (int)$id,
				"status" => [
					'$ne'	=> 0
				]
			],
			"sort"	=> [
				"updated_at"	=> -1
			],
			"skip"	=> $skip,
			"limit"	=> $limit,
		]);

		$count = DialogueUsers::count([
			[
				"user_id"	=> (int)$id,
				"status" => [
					'$ne'	=> 0
				]
			],
		]);

		$dialogueIds = [];
		foreach($dialogues as $value)
			$dialogueIds[] = $this->mymongo->objectId($value->dialogue);

		$dialogueQuery = Dialogues::find([
			[
				"_id" => [
					'$in'	=> $dialogueIds
				]
			]
		]);

		$userIds = [];
		$usersArr = [];
		$dialoguesData = [];
		foreach($dialogueQuery as $value)
		{
			$usersArr = array_merge($usersArr, $value->users);
			$dialoguesData[(string)$value->_id] = $value;
		}
		$usersArr = array_unique($usersArr);
		foreach($usersArr as $value)
			$userIds[] = (int)$value;

		$users = [];
		$dialogueTitles = [];
		if(count($userIds) > 0)
		{
			$userQuery = Users::find([
				[
					"id"	=> [
						'$in'	=> $userIds
					]
				]
			]);

			//exit(count($userQuery)." asds");
			foreach($userQuery as $value)
			{
				$users[$value->id] = $value;
			}

			foreach($dialogueQuery as $dialogue)
			{
				foreach($dialogue->users as $userId)
				{
					if(count($dialogueTitles[(string)$dialogue->_id]) < 5 && (int)$id !== (int)$userId)
						$dialogueTitles[(string)$dialogue->_id][] = $users[$userId]->firstname." ".$users[$userId]->lastname;
				}
			}
		}

		$pagination = $this->lib->navigator($skip, $count, $limit, "?skip=");

		$this->view->setVar("dialogues", $dialogues);
		$this->view->setVar("dialoguesData", $dialoguesData);
		$this->view->setVar("dialogueTitles", $dialogueTitles);
		$this->view->setVar("pagination", $pagination);
	}

	public function contractsAction($id)
	{
		if($this->data->type == "employee"){
			$bind = ["employee" => (int)$id];
		}else{
			$bind = ["citizen" => (int)$id];
		}
		$bind["is_deleted"] = 0;
		$contracts = Contracts::find([
			$bind,
			"sort" => [
				"id" => -1
			]
		]);

		$this->view->setVar("contracts", $contracts);
	}


	public function contractsaddAction($id)
	{

		if($this->data->type == "employee"){
			$bind = ["for" => "employee"];
		}else{
			$bind = ["for" => "citizen"];
		}
		$bind["is_deleted"] = 0;
		$contracts = ContractTemplates::find([
			$bind,
			"sort" => [
				"id" => -1
			]
		]);

		$this->view->setVar("contracts", $contracts);
	}


	public function documentsAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}


	public function documentslistAction($id)
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $did){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					DocumentFolders::update(
						[
							"id" => (int)$did
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
		$iDisplayStart 	= intval($this->request->get('start'));
		$sEcho 			= intval($this->request->get('draw'));

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$binds 	= [];

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("category") > 0)
			$binds["category"] = (int)$this->request->get("category");

		if($this->data->type == "employee"){
			$binds["employee"] 	 = (int)$id;
		}else{
			$binds["citizen"] 	 = (int)$id;
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
				$orderBy = ["_id" => $order_sort];
				break;
		}
		$data = DocumentFolders::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = DocumentFolders::count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = [
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					'<div class="todo-color" style="background: '.htmlspecialchars($value->html_code).';"></div><div class="todo-title">'.htmlspecialchars($value->title).'</div>',
					@$this->mymongo->dateFormat($value->created_at, "d.m.y"),
					'<a href="'._PANEL_ROOT_.'/profile/documentsview/'.$id.'?id='.$value->id.'" class=""><i class="la la-file"></i></a>'
				];
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["recordsFiltered"] = $count;

		echo json_encode($records);
		$this->view->disable();
		exit;
	}

	public function documentsaddAction($id)
	{
		$error 		= false;
		$success 	= false;

		$title 			= trim($this->request->get("title"));
		$puid 			= trim($this->request->get("puid"));
		$category 		= (int)$this->request->get("category");
		if(strlen($puid) == 0)
			$puid = md5("partner-".microtime(true)."-".rand(1,9999999));

		if((int)$this->request->get("save") == 1)
		{
			$docId = DocumentFolders::getNewId();
			$docInsert = [
				"id"				=> (int)$docId,
				"moderator_id"      => (int)@$this->auth->getData()->id,
				//"partner"   		=> (int)$this->data->partner,
				"title"				=> $title,
				"category"			=> $category,
				"is_deleted"    	=> 0,
				"created_at"		=> $this->mymongo->getDate(),
			];
			if($this->data->type == "employee"){
				$docInsert["employee"] 	 = (int)$id;
			}else{
				$docInsert["citizen"] 	 = (int)$id;
			}
			DocumentFolders::insert($docInsert);

			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
					"active"	=> 1,
				]
			]);
			if($tempFiles)
			{
				$document = true;
				foreach($tempFiles as $value)
				{
					$document = [
						"_id"				=> $value->_id,
						"moderator_id"      => (int)$value->moderator_id,
						"title"      		=> (string)$title,
						"employee"      	=> (int)$id,
						"folder"      		=> (int)$docId,
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

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
	}

	public function documentsviewAction($id)
	{
		$error 		= false;
		$success 	= false;
		$id 		= (int)$this->request->get("id");
		$delete 	= $this->request->get("delete");
		if(strlen($delete) > 0)
		{
			$update = [
				"is_deleted"	=> 1,
				"deleted_by"	=> "moderator",
				"moderator_id"	=> (int)$this->auth->getData()->id,
				"deleted_at"	=> $this->mymongo->getDate()
			];

			Documents::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$folder = DocumentFolders::getById($id);

		$documents = Documents::find([
			[
				"folder" 		=> (int)$id,
				"is_deleted"	=> 0,
			],
			"limit"	=> 100
		]);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("documents", $documents);
		$this->view->setVar("docdata", DocumentFolders::findFirst([["id" => (int)$id]]));
	}







	public function scheduleAction($id)
	{
        $weekdays = [];
        foreach ($this->request->get("weekdays") as $value){
            $weekdays[] = (int)$value;
        }


        if((int)$this->request->get("save") == 1)
        {
            $update = [
                "weekdays"				=> $weekdays,
                "updated_at"			=> $this->mymongo->getDate()
            ];
            Users::update(["id" => (int)$id], $update);


            $data = Users::findFirst([
                [
                    "id"			=> (int)$id,
                    //"is_deleted"	=> 0
                ]
            ]);
            $this->view->setVar("data", $data);

            $success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
        }

        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
	}



	public function notesAction($id)
	{
		$this->view->setVar("hasDatatable", true);
	}

	public function notesaddAction($id)
	{
		$error 			= false;
		$success 		= false;

		$title 			= trim($this->request->get("title"));
		$description 	= trim($this->request->get("description"));
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
			elseif (strlen($description) < 1 || strlen($description) > 20000)
			{
				$error = $this->lang->get("DescriptionError", "Description is empty");
			}
			else
			{
				$userInsert = [
					"id"						=> Notes::getNewId(),
					"title"						=> $title,
					"description"				=> $description,
					"folder"					=> $folder,
					//"citizen"					=> (int)$this->request->get("citizen"),
					"employee"					=> (int)$this->data->id,
					"active"					=> 1,
					"is_deleted"				=> 0,
					"created_at"				=> $this->mymongo->getDate()
				];

				Notes::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function noteseditAction($id)
	{
		$error 			= false;
		$success 		= false;

		$id 			= (int)$this->request->get("id");

		$data 			= Notes::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$title 			= trim($this->request->get("title"));
		$description 	= trim($this->request->get("description"));
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
			elseif (strlen($description) < 1 || strlen($description) > 20000)
			{
				$error = $this->lang->get("DescriptionError", "Description is empty");
			}
			else
			{
				$update = [
					"title"						=> $title,
					"description"				=> $description,
					"updated_at"				=> $this->mymongo->getDate()
				];
				Notes::update(["id" => (int)$id], $update);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("notedata", $data);
	}

	public function noteslistAction($id)
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $nid){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					Notes::update(
						[
							"id" => (int)$nid
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

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

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

		$binds["employee"] 		= (int)$id;
		$binds["is_deleted"] 	= 0;

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
		$data = Notes::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Notes::count([
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
					'<a href="'._PANEL_ROOT_.'/profile/notesedit/'.$id.'?id='.$value->id.'" class=""><i class="la la-edit"></i></a>'
					//'<a href="'._PANEL_ROOT_.'/noteitems/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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

	public function permissionsAction($id)
	{
		$error 			= false;
		$success 		= false;

		$only_self 		= (int)($this->request->get("only_self"));
		$permissions    = json_decode(trim($this->request->get("permissions")), true);

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("moderatorsEdit-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{
				$update = [
					"only_self"						=> $only_self,
					"permissions"					=> count($permissions) > 0 ? $permissions: [],
					"updated_at"					=> $this->mymongo->getDate()
				];

				Users::update(["id" => (int)$id], $update);

				$data 			= Users::getById($id);
				$this->view->setVar("data", $data);

				$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

}
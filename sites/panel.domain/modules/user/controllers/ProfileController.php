<?php
namespace Controllers;

use Models\Cache;
use Models\Contracts;
use Models\ContractTemplates;
use Models\Dialogues;
use Models\DialogueUsers;
use Models\Documents;
use Models\Partner;
use Models\TempFiles;
use Models\Users;

class ProfileController extends \Phalcon\Mvc\Controller
{
	public $data;

	public function initialize()
	{
		$id = (int)$this->dispatcher->getParam("id");
		if($id == 0)
			$id = $this->auth->getData()->id;

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
		$email 		= trim($this->request->get("email"));
		$ssn 		= trim($this->request->get("ssn"));
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 	= trim($this->request->get("password"));
		$place 		= trim($this->request->get("place"));
		$gender 	= (string)strtolower($this->request->get("gender"));

		$languages 		= [];
		foreach($this->request->get("languages") as $value)
			$languages[] = (int)$value;

		$end_date 		= strtotime(trim($this->request->get("end_date")));

		$address 	= trim($this->request->get("address"));
		$zipcode 	= trim($this->request->get("zipcode"));
		$city 		= (int)$this->request->get("city");

		$paymentRegNum  	= trim($this->request->get("payment_registration_number"));
		$paymentAccountNum  = trim($this->request->get("payment_account_number"));

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("infoUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 500, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			/**
			elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
			{
				$error = $this->lang->get("FirstnameError", "Firstname is empty");
			}
			elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
			{
				$error = $this->lang->get("LastnameError", "Lastname is empty");
			}
			 */
			elseif (strlen($email) > 1 && !filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif (strlen($ssn) > 0 && (strlen($ssn) < 1 || strlen($ssn) > 100))
			{
				$error = $this->lang->get("SSNError", "Social social number is empty");
			}
			elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}
			elseif (strlen($password) > 0 && (strlen($password) < 6 || strlen($password) > 100))
			{
				$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
			}
			elseif (strlen($place) > 0 && (strlen($place) < 1 || strlen($place) > 100))
			{
				$error = $this->lang->get("PlaceError", "School / Kindergarden / Institution is empty");
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
					//"firstname"		=> $firstname,
					//"lastname"		=> $lastname,
					"phone"			=> $phone,
					"email"			=> $email,
					"gender"		=> $gender,

					"ssn"			=> $ssn,
					"place"			=> $place,
					"birthdate"		=> $this->lib->getBirthdateFromSSN($ssn),

					"languages"		=> $languages,
					//"end_date"		=> $this->mymongo->getDate($end_date),

					"address"		=> $address,
					"zipcode"		=> $zipcode,
					"city"			=> $city,

					"payment_registration_number"	=> $paymentRegNum,
					"payment_account_number"		=> $paymentAccountNum,

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

		$partner = false;
		if($this->data->type == "partner")
			$partner = Partner::getById($this->data->partner);

		$this->view->setVar("partner", $partner);
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
		$contracts = ContractTemplates::find([
			[
				"is_deleted"	=> 0
			],
			"sort" => [
				"id" => -1
			]
		]);

		$this->view->setVar("contracts", $contracts);
	}
}
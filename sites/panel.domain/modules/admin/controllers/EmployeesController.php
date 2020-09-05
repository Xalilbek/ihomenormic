<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Users;
use Models\Cache;

class EmployeesController extends \Phalcon\Mvc\Controller
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
					Users::update(
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
				}elseif($status == "active") {
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"active" => 1,
						]
					);
				}elseif($status == "inactive") {
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"active" => 0,
						]
					);
				}elseif($status == "former") {
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"is_former" => 1,
							"former_by" => $this->auth->getData()->id,
							"former_at" => MyMongo::getDate()
						]
					);
				}elseif($status == "unformer") {
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"is_former" => 0,
							"former_by" => $this->auth->getData()->id,
							"former_at" => MyMongo::getDate()
						]
					);
				}elseif($status == "block") {
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"is_blocked" => 1,
							"blocked_by" => $this->auth->getData()->id,
							"blocked_at" => MyMongo::getDate()
						]
					);
				}
				elseif($status == "unblock")
				{
					Users::update(
						[
							"id" => (int)$id
						],
						[
							"is_blocked"	=> 0,
							"unblocked_by"	=> $this->auth->getData()->id,
							"unblocked_at"	=> MyMongo::getDate()
						]
					);
				}
				else
				{
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

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("firstname"))
			$binds["firstname"] = [
				'$regex' => trim(strtolower($this->request->get("firstname"))),
				'$options'  => 'i'
			];

		if ($this->request->get("lastname"))
			$binds["lastname"] = [
				'$regex' => trim(strtolower($this->request->get("lastname"))),
				'$options'  => 'i'
			];

		if ($this->request->get("address"))
			$binds["address"] = [
				'$regex' => trim(strtolower($this->request->get("address"))),
				'$options'  => 'i'
			];

		if ($this->request->get("zipcode"))
			$binds["zipcode"] = [
				'$regex' => trim(strtolower($this->request->get("zipcode"))),
				'$options'  => 'i'
			];

		if ($this->request->get("phone"))
			$binds["phone"] = [
				'$regex' => trim(strtolower($this->request->get("phone"))),
				'$options'  => 'i'
			];

		if ($this->request->get("city") > 0)
			$binds["city"] = (int)$this->request->get("city");

		$binds["is_former"] = ['$ne' => 1];
		if ($this->request->get("former") > 0)
			$binds["is_former"] = 1;

		$binds["type"] = 'employee';
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

		$cityIds = [];
		foreach($data as $value)
		{
			$cityIds[] 	= (int)$value->city;
		}
		$cityData = [];
		if(count($cityIds) > 0)
		{
			$cityData = $this->parameters->getListByIds($this->lang, "cities", $cityIds, true);
		}

		if ($data)
		{
			foreach($data as $value)
			{
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$value->id,
					htmlspecialchars($value->firstname).((int)$value->active == 0 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Inactive"))."</font> ": "").((int)$value->is_blocked == 1 ? "<font style='font-weight: 500;color: red'> ".strtolower($this->lang->get("Blocked"))."</font>": ""),
					htmlspecialchars($value->lastname),
					htmlspecialchars($value->address),
					htmlspecialchars($value->zipcode),
					(@$cityData[$value->city]) ? htmlspecialchars(@$cityData[$value->city]["title"]): '',
					htmlspecialchars($value->phone),
					'<a href="'._PANEL_ROOT_.'/profile/'.$value->id.'" class=""><i class="la la-user"></i></a>'
					//'<a href="'._PANEL_ROOT_.'/employees/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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

		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
		$email 			= trim($this->request->get("email"));
		$type2 			= (int)trim($this->request->get("type2"));
		$ssn 			= trim($this->request->get("ssn"));
		$phone 			= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 		= trim($this->request->get("password"));
		$gender 		= (string)strtolower($this->request->get("gender"));

		$languages 		= [];
		foreach($this->request->get("languages") as $value)
			$languages[] = (int)$value;

		$start_date 	= strtotime($this->lib->dateFomDanish(trim($this->request->get("start_date"))));

		$address 		= trim($this->request->get("address"));
		$zipcode 		= trim($this->request->get("zipcode"));
		$city 			= (int)$this->request->get("city");

		$paymentRegNum  	= trim($this->request->get("payment_registration_number"));
		$paymentAccountNum  = trim($this->request->get("payment_account_number"));

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
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
			/**
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif (strlen($ssn) < 1 || strlen($ssn) > 100)
			{
				$error = $this->lang->get("SSNError", "Social social number is empty");
			}
			elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}
			elseif (strlen($password) < 6 || strlen($password) > 100)
			{
				$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
			}
			elseif (count($languages) < 1 || count($languages) > 100)
			{
				$error = $this->lang->get("LanguageNotChoosen", "Language is empty");
			}
			elseif (strlen($address) < 1 || strlen($address) > 200)
			{
				$error = $this->lang->get("AddressError", "Address is empty");
			}
			elseif (strlen($zipcode) < 1 || strlen($zipcode) > 50)
			{
				$error = $this->lang->get("ZipCodeError", "Zip Code is empty");
			}
			elseif ($city == 0)
			{
				$error = $this->lang->get("CityError", "City is empty");
			}
			elseif (strlen($paymentRegNum) < 1 || strlen($paymentRegNum) > 100)
			{
				$error = $this->lang->get("PaymentRegistrationError", "Registration number is empty");
			}
			elseif (strlen($paymentAccountNum) < 1 || strlen($paymentAccountNum) > 100)
			{
				$error = $this->lang->get("AccountNumberError", "Account number is empty");
			}
			 */
			else
			{
				$id 	= Users::getNewId();
				$userInsert = [
					"id"							=> $id,
					"firstname"						=> $firstname,
					"lastname"						=> $lastname,
					"email"							=> $email,
					"ssn"							=> $ssn,
					"phone"							=> $phone,
					"password"						=> $this->lib->generatePassword($password),
					"gender"						=> $gender,
					"address"						=> $address,
					"languages"						=> $languages,
					"start_date"					=> $this->mymongo->getDate($start_date),
					"zipcode"						=> $zipcode,
					"city"							=> (int)$city,
					"payment_registration_number"	=> $paymentRegNum,
					"payment_account_number"		=> $paymentAccountNum,
					"is_deleted"					=> 0,
					"active"						=> 1,
					"type"							=> 'employee',
					"type2"							=> $type2,
					"created_at"					=> MyMongo::getDate()
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
		$error 			= false;
		$success 		= false;

		$data 			= Users::getById($id);
		if(!$data)
			header("Location: "._PANEL_ROOT_."/");

		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
		$email 			= trim($this->request->get("email"));
		$ssn 			= trim($this->request->get("ssn"));
		$phone 			= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 		= trim($this->request->get("password"));
		$gender 		= (int)$this->request->get("int");

		$languages 		= [];
		foreach($this->request->get("languages") as $value)
			$languages[] = (int)$value;

		$address 		= trim($this->request->get("address"));
		$zipcode 		= trim($this->request->get("zipcode"));
		$city 			= (int)$this->request->get("city");

		$paymentRegNum  = trim($this->request->get("payment_registration_number"));
		$paymentAccountNum  = trim($this->request->get("payment_account_number"));

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
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
			/**
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif (strlen($ssn) < 1 || strlen($ssn) > 100)
			{
				$error = $this->lang->get("SSNError", "Social social number is empty");
			}
			elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}
			elseif (count($languages) < 1 || count($languages) > 100)
			{
				$error = $this->lang->get("LanguageNotChoosen", "Language is empty");
			}
			elseif (strlen($address) < 1 || strlen($address) > 200)
			{
				$error = $this->lang->get("AddressError", "Address is empty");
			}
			elseif (strlen($zipcode) < 1 || strlen($zipcode) > 50)
			{
				$error = $this->lang->get("ZipCodeError", "Zip Code is empty");
			}
			elseif ($city == 0)
			{
				$error = $this->lang->get("CityError", "City is empty");
			}
			elseif (strlen($paymentRegNum) < 1 || strlen($paymentRegNum) > 100)
			{
				$error = $this->lang->get("PaymentRegistrationError", "Registration number is empty");
			}
			elseif (strlen($paymentAccountNum) < 1 || strlen($paymentAccountNum) > 100)
			{
				$error = $this->lang->get("AccountNumberError", "Account number is empty");
			}
			elseif (strlen($password) > 0 && (strlen($password) < 6 || strlen($password) > 100))
			{
				$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
			} */
			else
			{
				$update = [
					"firstname"						=> $firstname,
					"lastname"						=> $lastname,
					"email"							=> $email,
					"ssn"							=> $ssn,
					"phone"							=> $phone,
					"gender"						=> $gender,
					"address"						=> $address,
					"languages"						=> $languages,
					"zipcode"						=> $zipcode,
					"city"							=> $city,
					"payment_registration_number"	=> $paymentRegNum,
					"payment_account_number"		=> $paymentAccountNum,
					"updated_at"					=> MyMongo::getDate()
				];
				if(strlen($password) > 0)
					$update["password"] = $this->lib->generatePassword($password);

				Users::update(["id" => (int)$id], $update);

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
				"deleted_at"	=> MyMongo::getDate()
			];
			Users::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
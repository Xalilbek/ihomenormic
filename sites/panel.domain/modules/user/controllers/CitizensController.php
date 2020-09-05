<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cases;
use Models\Cache;
use Models\Users;

class CitizensController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => ['$ne' => 1]]]));
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
		$search = true;

		if ($this->request->get("item_id") > 0)
			$binds["id"] = $this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("fullname")){
			$binds['$or'] = [
				[
					"firstname" =>
						[
							'$regex' => trim(strtolower($this->request->get("fullname"))),
							'$options'  => 'i'
						]
				],
				[
					"lastname" =>
						[
							'$regex' => trim(strtolower($this->request->get("fullname"))),
							'$options'  => 'i'
						]
				]
			];
		}

		if (in_array($this->request->get("active"), ["0","1","2"])){
			$binds["active"] = (int)$this->request->get("active");
		}

		if ($this->request->get("employee") > 0){
			$caseQuery = Cases::find([["employee" => (int)$this->request->get("employee"), "is_deleted" => 0]]);
			$citizenIds = [];
			foreach($caseQuery as $value)
			{
				$citizenIds[] 	= (int)@$value->citizen[0];
			}
			$binds["id"] = ['$in' => $citizenIds];
		}

		$binds["type"] = "citizen";
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

		$citizenIds = [];
		foreach($data as $value)
			$citizenIds[] = (int)$value->id;

		$caseQuery = Cases::find([
			[
				"citizen" => [
					'$in' => $citizenIds
				]
			]
		]);
		$cases 			= [];
		$employeeIds 	= [];
		foreach($caseQuery as $value)
		{
			$cases[(int)@$value->citizen[0]] = $value;
			$employeeIds[] 	= (int)@$value->employee[0];
		}

		$employees = [];
		if(count($employeeIds) > 0)
		{
			$employeeQuery = Users::find([
				[
					"id" => [
						'$in'	=> $employeeIds
					]
				]
			]);
			foreach($employeeQuery as $value)
				$employees[$value->id] = $value;;
		}


		$status_list = array(
			array("danger"  => "deactive"),
			array("success" => "active"),
			array("warning" => "pending"),
		);

		if ($data)
		{
			foreach($data as $value)
			{
				$case = @$cases[$value->id];
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					htmlspecialchars($value->firstname." ".$value->lastname),
					$this->lib->secToStr($this->mymongo->toSeconds($value->created_at), $this->lang),
					($case) ? round($case->activity_budget, 2)." kr.": "0 kr.",
					($case) ? @$this->mymongo->dateFormat($case->created_at, "d.m.y"): "",
					($case && $employees[(int)@$case->employee[0]]) ? htmlspecialchars($employees[(int)@$case->employee[0]]->firstname." ".$employees[(int)@$case->employee[0]]->lastname): "",
					($case) ? @$this->mymongo->dateFormat($case->created_at, "d.m.y"): "",
					'<a href="'._PANEL_ROOT_.'/profile/cases/'.$value->id.'" class=""><i class="la la-user"></i></a>'
					//'<a href="'._PANEL_ROOT_.'/citizens/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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
		$error 		= false;
		$success 	= false;

		$firstname 	= trim($this->request->get("firstname"));
		$lastname 	= trim($this->request->get("lastname"));
		$email 		= trim($this->request->get("email"));
		$ssn 		= trim($this->request->get("ssn"));
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 	= trim($this->request->get("password"));
		$place 		= trim($this->request->get("place"));

		$address 	= trim($this->request->get("address"));
		$zipcode 	= trim($this->request->get("zipcode"));
		$city 		= (int)$this->request->get("city");

		$birthDate  = $this->lib->getBirthdateFromSSN($ssn);

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
			elseif (strlen($email) > 0 && !filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif (strlen($ssn) < 1 || strlen($ssn) > 100)
			{
				$error = $this->lang->get("SSNError", "Social social number is empty");
			}
			elseif (strlen($phone) > 0 && (strlen($phone) > 20 || !is_numeric($phone)))
			{
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}
			elseif (strlen($password) < 6 || strlen($password) > 100)
			{
				$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
			}
			elseif (strlen($place) < 1 || strlen($place) > 100)
			{
				$error = $this->lang->get("PlaceError", "School / Kindergarden / Institution is empty");
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
			else
			{
				$userInsert = [
					"id"			=> Users::getNewId(),
					"firstname"		=> $firstname,
					"lastname"		=> $lastname,
					"email"			=> $email,
					"ssn"			=> $ssn,
					"phone"			=> $phone,
					"birthdate"		=> $birthDate,
					"password"		=> $this->lib->generatePassword($password),
					"place"			=> $place,
					"address"		=> $address,
					"zipcode"		=> $zipcode,
					"city"			=> $city,
					"type"			=> "citizen",
					"is_deleted"	=> 0,
					"created_at"	=> MyMongo::getDate()
				];

				Users::insert($userInsert);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
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
<?php
namespace Controllers;

use Lib\MyMongo;
use Models\NoteFolders;
use Models\Cache;
use Models\Offers;
use Models\Users;

class OffersController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("employees", Users::find([["type" => "employee","is_deleted" => ['$ne' => 1]]]));
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
					Offers::update(
						[
							"id" => (int)$id
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

		$order = $this->request->get("order");
		$order_column = $order[0]["column"];
		$order_sort = $order[0]["dir"];

		$binds = [];

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if ($this->request->get("created_from"))
			$binds["created_at"] = ['$gte' => $this->mymongo->getDate(strtotime($this->request->get("created_from")))];

		if ($this->request->get("created_to"))
			$binds["created_at"]['$lt'] = $this->mymongo->getDate(strtotime($this->request->get("created_to")));

		if ($this->request->get("employee") > 0)
			$binds["employee"] = (int)$this->request->get("employee");

		if ($this->request->get("citizen") > 0)
			$binds["citizen"] = (int)$this->request->get("citizen");

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
		$data = Offers::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Offers::count([
			$binds,
		]);

		$userIds = [];

		foreach($data as $value)
		{
			$userIds[] 	= (int)$value->citizen;
			$userIds[] 	= (int)$value->employee;
		}
		$userIds 		= array_unique($userIds);
		$usersData 		= [];
		if(count($userIds) > 0)
		{
			$query = Users::find(["id" => ['$in' => $userIds]]);
			foreach($query as $value)
				$usersData[$value->id] = $value;
		}

		$focus_areas 	= $this->parameters->getList($this->lang, "focusareas", [], true);

		if ($data)
		{
			foreach($data as $value)
			{
				$list = [];
				$list[] = '<input type="checkbox" name="id[]" value="'.$value->id.'">';
				$list[] = $value->id;
				$list[] = htmlspecialchars(@$focus_areas[$value->focusarea]["title"]);
				$list[] = htmlspecialchars(@$value->email);
				$list[] = @$this->mymongo->dateFormat($value->created_at, "d.m.y");
				$list[] = '<a href="'._PANEL_ROOT_.'/offers/view/'.$value->id.'" class=""><i class="la la-file"></i></a>';
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

	public function viewAction($id)
	{
		$data = Offers::getById($id);
		if(!$data)
			header("location: "._PANEL_ROOT_."/");

		$this->view->setVar("data", $data);
	}

	public function addAction()
	{
		$error 			= false;
		$success 		= false;

		$citizen 			= (int)$this->request->get("citizen");
		$employee 			= (int)$this->request->get("employee");
		$focusarea 			= (int)$this->request->get("focus_area");
		$offer_type 		= (int)$this->request->get("offer_type");
		$email 				= trim(strtolower($this->request->get("email")));
		$phone 				= trim(($this->request->get("phone")));
		$description 		= $this->request->get("description");
		$is_secure 			= (int)$this->request->get("is_secure");

		$phone				= str_replace(["+"," ", ".",",","+"],"", $phone);
		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asdAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			/**
			elseif ($citizen == 0 || $employee == 0 || $focusarea == 0)
			{
				$error = $this->lang->get("PleaseFillAllFields", "Please, fill all fields");
			} */
			elseif (strlen($email) > 0 && !filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$error = $this->lang->get("EmailError", "Email is wrong");
			}
			elseif ($is_secure > 0 && (!is_numeric($phone) || strlen($phone) < 10))
			{
				$error = $this->lang->get("PhoneErrorrr", "Phone number is wrong");
			}
			else
			{
				// https://www.privatewealth.institute/msend.php?from=info@cep.az&to=seniorshahmar@gmail.com&subject=Testinggg&content=1asdasdkkk&key=q1w2e3r4t5aqswdefrgt

				$subject = "Offer";

				$code = (string)rand(111111, 999999);

				$layout = file_get_contents("mailtemplates/layout.html");
				$layout = str_replace('{CONTENT}', $description, $layout);

				$userInsert = [
					"id"						=> Offers::getNewId(),
					"focusarea"					=> $focusarea,
					"citizen"					=> $citizen,
					"employee"					=> $employee,
					"email"						=> $email,
					"phone"						=> strlen($phone) > 1 ? $phone: false,
					"offer_type"				=> $offer_type,
					"content"					=> $layout,
					"is_secure"					=> (int)$is_secure,
					"code"						=> $code,
					"is_read"					=> 0,
					"is_deleted"				=> 0,
					"created_at"				=> $this->mymongo->getDate()
				];

				$mailInsId = Offers::insert($userInsert);

				$secureLayout = file_get_contents("mailtemplates/securecode.html");
				$secureLayout = str_replace('{LINK}', "http://crons.carecompagniet.dk/offers?hash=".$mailInsId, $secureLayout);
				//$secureLayout = str_replace('{CODE}', $code, $secureLayout);
				$secureLayout = str_replace('{date}', date("d.m.Y"), $secureLayout);

				$mailConent = $is_secure == 1 ? $secureLayout: $layout;

				$mailUrl = EMAIL_DOMAIN;
				$vars = [
					"key"			=> "q1w2e3r4t5aqswdefrgt",
					//"from"			=> "info@shahmar.info",
					"from"			=> "noreply@carecompagniet.dk",
					"to"			=> $email,
					"subject"		=> $subject,
					"content"		=> $mailConent,
				];

				$response = $this->lib->initCurl($mailUrl, $vars, "post");

				$smsRes = $this->lib->sendSMS("+".$phone, "Verification Code: ".$code);

				Offers::update(
					[
						"_id" => $mailInsId
					],
					[
						"mail_response"	=> $response,
						"sms_response"	=> $smsRes,
						"mail_content"	=> $is_secure == 1 ? $mailConent: ""
					]
				);

				$success = $this->lang->get("AddedSuccessfully", "Added successfully");
			}
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Documents;
use Models\Moderator;
use Models\Notes;
use Models\TempFiles;
use Models\Users;
use Models\Vacancy;

class VacanciesController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		$this->view->setVar("vacancySwitches", Vacancy::getSwitches($this->lang));
	}

	public function indexAction()
	{
		$tableConf = [
			"listUrl"       => "/vacancies/list",
			"cmdUrl"       => "/vacancies/command",
			"columns"   => [
				[
					"field"         => "RecordID",
					"title"         => "#",
					"sortable"      => false,
					"width"         => "40px",
					"selector"      => ["class" => "m-checkbox--solid m-checkbox--brand"],
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "info",
					"title"         => "Info",
					"sortable"      => true,
					"width"         => "200px",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "gender",
					"title"         => "Gender",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					//"attr"          => ["nowrap" => "false"],
					"overflow"      => "visible",
				],
				[
					"field"         => "age",
					"title"         => "Age",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "language",
					"title"         => "Language",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "education",
					"title"         => "Education / Occupation",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "experience",
					"title"         => "Work Experience",
					"sortable"      => true,
					//"width"         => "auto",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
				[
					"field"         => "actions",
					"title"         => "Actions",
					"sortable"      => false,
					"width"         => "240px",
					"textAlign"     => "left",
					"attr"          => ["nowrap" => "nowrap"],
					"overflow"      => "visible",
				],
			],
		];

		$this->view->setVar("hasAjaxtable", true);
		$this->view->setVar("tableConf", $tableConf);
	}

	public function listAction()
	{
		$records = array();

		$records["data"] = array();

		$order 			= $this->request->get("order");
		$order_column 	= $order[0]["column"];
		$order_sort 	= $order[0]["dir"];

		$limit 	= (int)$this->request->get("pagination")["perpage"];
		$skip 	= (int)$this->request->get("pagination")["page"];
		$skip	= ($skip - 1) * $limit;
		$binds 	= [];



			if (strlen($this->request->get("query")["title"]) > 0)
				$binds["fullname"] = [
					'$regex' => trim(strtolower($this->request->get("query")["title"])),
					'$options'  => 'i'
				];

			if ($this->request->get("query")["lastname"])
				$binds["lastname"] = [
					'$regex' => trim(mb_strtolower($this->request->get("query")["lastname"])),
					'$options'  => 'i'
				];

			if ($this->request->get("query")["address"])
				$binds["address"] = [
					'$regex' => trim(strtolower($this->request->get("query")["address"])),
					'$options'  => 'i'
				];

			if ($this->request->get("query")["email"])
				$binds["email"] = [
					'$regex' => trim(mb_strtolower($this->request->get("query")["email"])),
					'$options'  => 'i'
				];

			if ($this->request->get("query")["phone"])
				$binds["phone"] = trim(strtolower($this->request->get("query")["phone"]));

			if ($this->request->get("query")["post_number"])
				$binds["city"] =(int)($this->request->get("query")["post_number"]);

			if ($this->request->get("query")["age_from"])
				$binds["age"] = [
					'$gte' => (int)($this->request->get("query")["age_from"])
				];

			if ($this->request->get("query")["age_to"])
				$binds["age"] = [
					'$lte' => (int)($this->request->get("query")["age_to"])
				];


			if ($this->request->get("query")["work_experience"])
				$binds["work_experience"] = [
					'$regex' => trim(strtolower($this->request->get("query")["work_experience"])),
					'$options'  => 'i'
				];

			if ($this->request->get("query")["occupation"])
				$binds["occupation"] = [
					'$regex' => trim(strtolower($this->request->get("query")["occupation"])),
					'$options'  => 'i'
				];

			if ($this->request->get("query")["work_position"])
				$binds["work_position"] = [
					'$regex' => trim(strtolower($this->request->get("query")["work_position"])),
					'$options'  => 'i'
				];


			if (is_array($this->request->get("query")["language"]) && count($this->request->get("query")["language"]) > 0)
			{
				//var_dump($this->request->get("query")["language"]);exit;
				$languages = [];
				foreach($this->request->get("query")["language"] as $v)
					if($v > 0)
						$languages[] = (int)$v;
				if(count($languages) > 0)
					$binds["languages"] = [
					'$in' => $languages
				];
			}

			if (is_array($this->request->get("query")["city"]) && count($this->request->get("query")["city"]) > 0)
			{
				$cities = [];
				foreach($this->request->get("query")["city"] as $v)
					if($v > 0)
						$cities[] = (int)$v;
				if(count($cities) > 0)
				$binds["city"] = [
					'$in' => $cities
				];

				//echo "<pre>";var_dump($binds);exit;

			}



			if (strlen($this->request->get("query")["gender"]) > 0)
				$binds["gender"] = $this->request->get("query")["gender"];

			if ($this->request->get("query")["phone"])
				$binds["phone"] = [
					'$regex' => trim(strtolower($this->request->get("query")["phone"])),
					'$options'  => 'i'
				];

			foreach(Vacancy::getSwitches($this->lang) as $value)
			{
				if($this->request->get("query")[$value["name"]][0] > 0)
				{
					$binds[$value["name"]] = 1;
				}
			}


		//$binds["type"] = 'employee';
		$binds["is_deleted"] = [
			'$ne'	=> 1
		];

		switch($order_sort)
		{
			default:
				$order_sort = 1;
				break;
			case "desc":
				$order_sort = -1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["id" => $order_sort];
				break;
		}
		$data = Vacancy::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $limit,
			"skip"      => $skip,
		]);
		$count = Vacancy::count([
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

		$cities 	= $this->parameters->getList($this->lang, "vacancy_cities", [], true);
		$languages 	= $this->parameters->getList($this->lang, "vacancy_languages", [], true);

		if($data)
		{
			foreach($data as $value)
			{
				$langs = [];
				foreach($value->languages as $langId)
					$langs[] = $languages[$langId]["title"];

				$tag = "<font style='color: ".((int)$value->is_blocked == 1 ? 'red': 'black')."'>";
				$info = strlen($value->year_of_application) > 0 ? htmlspecialchars($value->year_of_application)."<br/>": "";
				$info .= $tag."<b>".htmlspecialchars($value->fullname)."</b><br/>";
				$info .= htmlspecialchars($value->address)."<br/>".htmlspecialchars($value->phone)."<br/>".htmlspecialchars($value->email);
				$records["data"][] = array(
					"RecordID" => $value->id,
					"info" => $tag.$info."</font>",
					"gender" => $tag.$value->gender."</font>",
					"age" => $tag.$value->age."</font>",
					"language" => $tag.implode("<br/>", $langs)."</font>",
					"education" => $tag.htmlspecialchars($value->education_1).(strlen($value->education_2) ? htmlspecialchars($value->education_2)."<br/>": "")." <br/> ".htmlspecialchars($value->occupation)."</font>",
					"experience" => $tag.htmlspecialchars(substr($value->work_experience, 0, 100))."</font>",
					"actions" => '<a style="margin-bottom: 10px;" href="'._PANEL_ROOT_.'/vacancies/edit/'.$value->id.'" class="btn btn-xs btn-primary"><i class="la la-edit"></i> '.strtolower($this->lang->get("Edit")).'</a><br/>'.
					'<a style="margin-bottom: 10px;" href="'._PANEL_ROOT_.'/vacancies/notes/'.$value->id.'" class="btn btn-xs btn-warning"><i class="la la-comment"></i> '.strtolower($this->lang->get("Notes")).'</a><br/>'.
					'<a href="'._PANEL_ROOT_.'/vacancies/documents/'.$value->id.'" class="btn btn-xs yellow"><i class="la la-file"></i> '.strtolower($this->lang->get("Documents")).'</a>'
				);
			}
		}

		$records["meta"] = [
			"page" 		=> (int)($skip/$limit)+1,
			"pages" 	=> (int)($count/$limit),
			"perpage" 	=> $limit,
			"total" 	=> (int)$count,
			"sort"		=> "desc",
			"field" 	=> "id"
		];

		echo json_encode($records, true);
		exit;
	}

	public function commandAction()
	{
		$command = $this->request->get("command");
		foreach($this->request->get("ids") as $value)
		{
			if($command == "block"){
				Vacancy::update(
					[
						"id" => (int)$value
					],
					[
						"is_blocked" => 1,
						"blocked_by" => $this->auth->getData()->id,
						"blocked_at" => Vacancy::getDate()
					]
				);
			}elseif($command == "unblock"){
				Vacancy::update(
					[
						"id" => (int)$value
					],
					[
						"is_blocked"	=> 0,
						"unblocked_by"	=> $this->auth->getData()->id,
						"unblocked_at"	=> Vacancy::getDate()
					]
				);
			}elseif($command == "delete"){
				Vacancy::update(
					[
						"id" => (int)$value
					],
					[
						"is_deleted"	=> 1,
						"deleted_by"	=> "moderator",
						"moderator_id"	=> $this->auth->getData()->id,
						"deleted_at"	=> Vacancy::getDate()
					]
				);
			}
		}

		exit(json_encode(["status" => "success", "description" => "Updated successfully"]));
	}


	public function listoldAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $id){
				$error 	= false;
				$status = trim($this->request->get("customActionName"));
				if($status == "delete")
				{
					Vacancy::update(
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
				}
				elseif($status == "block") {
					Vacancy::update(
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
					Vacancy::update(
						[
							"id" => (int)$id
						],
						[
							"is_blocked"	=> 0,
							"unblocked_by"	=> $this->auth->getData()->id,
							"unblocked_at"	=> MyMongo::getDate()
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

		if (strlen($this->request->get("title")) > 0)
			$binds["fullname"] = [
				'$regex' => trim(strtolower($this->request->get("title"))),
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

		if ($this->request->get("email"))
			$binds["email"] = trim(strtolower($this->request->get("email")));

		if ($this->request->get("phone"))
			$binds["phone"] = trim(strtolower($this->request->get("email")));

		if ($this->request->get("city"))
			$binds["city"] =(int)($this->request->get("city"));

		if ($this->request->get("post_number"))
			$binds["city"] =(int)($this->request->get("post_number"));

		if ($this->request->get("work_experience"))
			$binds["work_experience"] = [
				'$regex' => trim(strtolower($this->request->get("work_experience"))),
				'$options'  => 'i'
			];

		if ($this->request->get("occupation"))
			$binds["occupation"] = [
				'$regex' => trim(strtolower($this->request->get("occupation"))),
				'$options'  => 'i'
			];

		if ($this->request->get("work_position"))
			$binds["work_position"] = [
				'$regex' => trim(strtolower($this->request->get("work_position"))),
				'$options'  => 'i'
			];


		if ($this->request->get("language"))
			$binds["languages"] =(int)($this->request->get("language"));



		if (strlen($this->request->get("gender")) > 0)
			$binds["gender"] = $this->request->get("gender");

		if ($this->request->get("phone"))
			$binds["phone"] = [
				'$regex' => trim(strtolower($this->request->get("phone"))),
				'$options'  => 'i'
			];

		if ($this->request->get("city") > 0)
			$binds["city"] = (int)$this->request->get("city");

		foreach(Vacancy::getSwitches($this->lang) as $value)
		{
			if($this->request->get($value["name"])[0])
			{
				$binds[$value["name"]] = 1;
			}
		}

		//$binds["type"] = 'employee';
		$binds["is_deleted"] = [
			'$ne'	=> 1
		];

		switch($order_sort)
		{
			default:
				$order_sort = 1;
				break;
			case "desc":
				$order_sort = -1;
				break;
		}
		switch($order_column)
		{
			default:
				$orderBy = ["id" => $order_sort];
				break;
		}
		$data = Vacancy::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = Vacancy::count([
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

		$cities 	= $this->parameters->getList($this->lang, "vacancy_cities", [], true);
		$languages 	= $this->parameters->getList($this->lang, "vacancy_languages", [], true);

		if($data)
		{
			foreach($data as $value)
			{
				$langs = [];
				foreach($value->languages as $langId)
					$langs[] = $languages[$langId]["title"];

				$tag = "<font style='color: ".((int)$value->is_blocked == 1 ? 'red': 'black')."'>";
				$info = strlen($value->year_of_application) > 0 ? htmlspecialchars($value->year_of_application)."<br/>": "";
				$info .= $tag."<b>".htmlspecialchars($value->fullname)."</b><br/>";
				$info .= htmlspecialchars($value->address)."<br/>".htmlspecialchars($value->phone)."<br/>".htmlspecialchars($value->email);
				$records["data"][] = array(
					'<input type="checkbox" name="id[]" value="'.$value->id.'">',
					$tag.$info."</font>",
					$tag.$value->gender."</font>",
					$tag.$value->age."</font>",
					$tag.implode("<br/>", $langs)."</font>",
					$tag.htmlspecialchars($value->education_1).(strlen($value->education_2) ? htmlspecialchars($value->education_2)."<br/>": "")." <br/> ".htmlspecialchars($value->occupation)."</font>",
					$tag.htmlspecialchars($value->work_experience)."</font>",
					'<a style="margin-bottom: 10px;" href="'._PANEL_ROOT_.'/vacancies/edit/'.$value->id.'" class="btn btn-xs btn-primary"><i class="la la-edit"></i> '.strtolower($this->lang->get("Edit")).'</a>'.
					'<a style="margin-bottom: 10px;" href="'._PANEL_ROOT_.'/vacancies/notes/'.$value->id.'" class="btn btn-xs btn-warning"><i class="la la-comment"></i> '.strtolower($this->lang->get("Notes")).'</a>'.
					'<a href="'._PANEL_ROOT_.'/vacancies/documents/'.$value->id.'" class="btn btn-xs yellow"><i class="la la-file"></i> '.strtolower($this->lang->get("Documents")).'</a>'
				);
			}
		}

		$records["draw"] = $sEcho;
		$records["recordsTotal"] = $count;
		$records["binds"] = $binds;
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
		$ssn 			= trim($this->request->get("ssn"));
		$phone 			= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
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
			} */
			else
			{
				$userInsert = [
					"id"							=> Vacancy::getNewId(),
					"firstname"						=> $firstname,
					"lastname"						=> $lastname,
					"email"							=> $email,
					"ssn"							=> $ssn,
					"phone"							=> $phone,
					"gender"						=> $gender,
					"address"						=> $address,
					"languages"						=> $languages,
					"zipcode"						=> $zipcode,
					"city"							=> (int)$city,
					"payment_registration_number"	=> $paymentRegNum,
					"payment_account_number"		=> $paymentAccountNum,
					"is_deleted"					=> 0,
					"type"							=> 'employee',
					"created_at"					=> MyMongo::getDate()
				];

				Vacancy::insert($userInsert);

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

		$data 			= Vacancy::getById($id);

		$update = [];
		if(!$data)
		{
			$data 			= new Vacancy();
			$update["id"] 	= Vacancy::getNewId();
		}

		if((int)$this->request->get("save") == 1)
		{
			if(Cache::is_brute_force("asdsa-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 400, "hour" => 3000, "day" => 9000]))
			{
				$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
			}
			else
			{
				foreach($this->request->get() as $key => $value)
				{
					if(in_array($key, ["handicap","physically_vulnerable","drug_and_alcohol_abuse","criminals","OCD","PTSD","borderline","autism","ADHD","diabetes","age","year_of_application","city"]))
					{
						$update[$key] = (int)$value;
					}else{
						$update[$key] = $value;
					}
				}

				foreach(["handicap","physically_vulnerable","drug_and_alcohol_abuse","criminals","OCD","PTSD","borderline","autism","ADHD","diabetes","age","year_of_application","city"] as $value)
					$update[$value] = (int)$this->request->get($value);

				$languages = [];
				foreach($this->request->get("languages") as $langId)
					$languages[] = (int)$langId;
				$update["languages"] = $languages;


				if($id > 0)
				{
					$update["updated_at"] = Vacancy::getDate();
					Vacancy::update(["id" => (int)$id], $update);
					$success = $this->lang->get("UpdatedSuccessfully", "Updated successfully");

					$data 			= Vacancy::getById($id);
				}
				else
				{
					$update["created_at"] = Vacancy::getDate();
					Vacancy::insert($update);
					$success = $this->lang->get("AddedSuccessfully", "Added successfully");

					$data 			= Vacancy::getById($update["id"]);
				}
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
		$data 		= Vacancy::findFirst([
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
			Vacancy::update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function documentsAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Vacancy::findFirst([
			[
				"id" 			=> (int)$id,
				//"is_deleted"	=> 0,
			]
		]);

		$puid 				= trim($this->request->get("puid"));
		$folder 			= (int)($this->request->get("folder"));
		if(strlen($puid) == 0)
			$puid = md5("cv-".microtime(true)."-".rand(1,9999999));

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
						"vacancy_id"      	=> (int)$id,
						"uuid"              => $value->uuid,
						"folder"            => $folder,
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
				"vacancy_id" 		=> (int)$id,
				"is_deleted"		=> 0,
			],
			"limit"	=> 100
		]);

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
		$this->view->setVar("vacancyFolders", $this->parameters->getList($this->lang, "vacancy_folders", [], true));
		$this->view->setVar("documents", $documents);
	}

	public function notesAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= Vacancy::findFirst([
			[
				"id" 			=> (int)$id,
				//"is_deleted"	=> 0,
			]
		]);

		$puid 				= trim($this->request->get("puid"));
		if(strlen($puid) == 0)
			$puid = md5("cv-".microtime(true)."-".rand(1,9999999));

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif((int)$this->request->get("save") == 1)
		{

			$title 			= trim($this->request->get("title"));
			$description 	= trim($this->request->get("description"));

			$userInsert = [
				"id"						=> Notes::getNewId(),
				"title"						=> $title,
				"description"				=> $description,
				"moderator_id"				=> $this->auth->getData()->id,
				"vacancy"					=> (int)$id,
				"active"					=> 1,
				"is_deleted"				=> 0,
				"created_at"				=> $this->mymongo->getDate()
			];

			$insId = Notes::insert($userInsert);




			$tempFiles = TempFiles::find([
				[
					"puid" 		=> $puid,
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
						"vacancy_id"      	=> (int)$id,
						"note_id"           => (string)$insId,
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




			$success = $this->lang->get("AddedSuccessfully", "Added successfully");
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
			Notes::update(["_id"	=> $this->mymongo->objectId($delete)], $update);
		}

		$notes = Notes::find([
			[
				"vacancy" 			=> (int)$id,
				"is_deleted"		=> 0,
			],
			"limit"	=> 100,
			"sort"	=> [
				"_id"	=> -1
			]
		]);

		$documents = [];
		$documentsQuery = Documents::find([
			[
				"vacancy_id" 		=> (int)$id,
				"note_id"			=> [
					'$ne'	=> null
				],
				"is_deleted"		=> 0,
			],
			"limit"	=> 100
		]);
		foreach($documentsQuery as $value)
			$documents[(string)@$value->note_id][] = $value;

		$moderatorQuery = Users::find([["type" => "moderator"]]);
		$moderators = [];
		foreach($moderatorQuery as $moderator)
			$moderators[$moderator->id] = $moderator;

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("puid", $puid);
		$this->view->setVar("notes", $notes);
		$this->view->setVar("documents", $documents);
		$this->view->setVar("moderators", $moderators);
	}


	public function filterAction()
	{
		exit;
		$table = $this->parameters->setCollection("vacancy_cities", $this->lang);

		$list = Vacancy::find();

		$cities = [];
		foreach($list as $value)
		{
			$arr 		= explode(" ", trim($value->zipcode));
			$zipcode 	= (int)$arr[0];
			$name 		= trim($arr[1]);

			$cities[$zipcode][] = $name;

			$slug = md5($name);

			if(strlen($name) < 1)
			{
				$selfId = 0;
			}
			elseif($cityData = $table->findFirst([["slug" => $slug]]))
			{
				$selfId =(int)$cityData->id;;
			}
			else
			{
				$selfId = $table->getNewId();
				$insert = [
					"id"            => $selfId,
					"parent_id"     => 0,
					"titles"        => ["da" => $name],
					"active"        => 1,
					"is_deleted"    => 0,
					"index"         => (int)$selfId,
					"default_lang"  => "da",
					"slug"          => $slug,
					"created_at"    => Vacancy::getDate(),
				];

				$insert["post_number"] = $zipcode;

				$table->insert($insert);

			}
			Vacancy::update(["id" => (int)$value->id], ["city"	=> $selfId, "post_number" => $zipcode]);

			echo $zipcode." - " .$name."<br/>";
		}

		$realCitiesQuery = $this->parameters->getList($this->lang, "cities");

		$realCities = [];
		foreach($realCitiesQuery as $value)
			$realCities[(int)$value["post_number"]] = $value["title"];



		foreach ($cities as $zipcode => $names)
		{
			$realCity = $realCities[$zipcode];
			foreach($names as $name)
			{


			}
		}
		exit;
	}



	public function langfilterAction()
	{
		exit;
		$table = $this->parameters->setCollection("vacancy_languages", $this->lang);

		$list = Vacancy::find();

		$languages = [];
		foreach($list as $value)
		{
			$selfLanguages = [
				$value->language_1, $value->language_2, $value->language_3
			];
			$langIds = [];
			foreach($selfLanguages as $language)
			{
				$language = trim($language);
				if(strlen($language) > 1)
				{
					$languages[strtolower(trim($language))] = $language;


					$slug = md5(strtolower($language));

					if($cityData = $table->findFirst([["slug" => $slug]]))
					{
						$selfId =(int)$cityData->id;
					}
					else
					{
						$selfId = $table->getNewId();
						$insert = [
							"id"            => $selfId,
							"parent_id"     => 0,
							"titles"        => ["da" => $language],
							"active"        => 1,
							"is_deleted"    => 0,
							"index"         => (int)$selfId,
							"default_lang"  => "da",
							"slug"          => $slug,
							"created_at"    => Vacancy::getDate(),
						];

						$table->insert($insert);

					}
					$langIds[] = $selfId;
				}
			}
			Vacancy::update(["id" => (int)$value->id], ["languages" => count($langIds) > 0 ? $langIds: null]);


			//echo $zipcode." - " .$name."<br/>";
		}


		foreach ($languages as $slug => $name)
		{
			echo $slug." - " .$name."<br/>";
		}
		exit;
	}



	public function edufilterAction()
	{
		$table = $this->parameters->setCollection("vacancy_languages", $this->lang);

		$list = Vacancy::find();

		$languages = [];
		foreach($list as $value)
		{
			$selfLanguages = [
				$value->language_1, $value->language_2, $value->language_3
			];
			$langIds = [];
			foreach($selfLanguages as $language)
			{
				$language = trim($language);
				if(strlen($language) > 1)
				{
					$languages[strtolower(trim($language))] = $language;


					/**
					$slug = md5(strtolower($language));

					if($cityData = $table->findFirst([["slug" => $slug]]))
					{
						$selfId =(int)$cityData->id;
					}
					else
					{
						$selfId = $table->getNewId();
						$insert = [
							"id"            => $selfId,
							"parent_id"     => 0,
							"titles"        => ["da" => $language],
							"active"        => 1,
							"is_deleted"    => 0,
							"index"         => (int)$selfId,
							"default_lang"  => "da",
							"slug"          => $slug,
							"created_at"    => Vacancy::getDate(),
						];

						$table->insert($insert);

					}
					$langIds[] = $selfId;
					 */
				}
			}
			//Vacancy::update(["id" => (int)$value->id], ["languages" => count($langIds) > 0 ? $langIds: null]);


			//echo $zipcode." - " .$name."<br/>";
		}


		foreach ($languages as $slug => $name)
		{
			echo $slug." - " .$name."<br/>";
		}
		exit;
	}
}


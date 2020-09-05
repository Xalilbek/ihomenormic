<?php
namespace Controllers;

use Models\Cache;
use Models\Cases;
use Models\Contacts;
use Models\Users;

class ContactlistController extends \Phalcon\Mvc\Controller
{

	public function listAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit > 100)
			$limit = 100;
		if($limit < 5)
			$limit = 5;
		$limit = 500;

		$binds 				 = [];
		$binds["user_id"] 	 = (int)$this->auth->getData()->id;
		$binds["is_deleted"] = 0;

		$data = Contacts::find([
			$binds,
			"sort"      => [
				"_id"	=> -1
			],
			"limit"     => $limit,
			"skip"      => $skip,
		]);

		$count = Contacts::count([
			$binds,
		]);

		$records = [];

		$case = Cases::getById((int)$this->request->get("case"));
		if(!$case)
			$case = Cases::findFirst([["employee" => (int)$this->auth->getData()->id], "sort" => ["_id" => -1]]);

		if($case){
			$value = Users::getById((int)@$case->citizen[0]);
			if($value)
				$records[] = [
					"id"			=> 0,
                    "user_id"		=> (int)$value->id,
                    "title" 		=> (string)@$value->firstname,
					"firstname" 	=> (string)@$value->firstname,
					"lastname"		=> (string)@$value->lastname,
					"phone" 		=> (string)@$value->phone,
					"date"			=> Contacts::dateFormat($value->created_at, "Y-m-d H:i:s")
				];
			$value = Users::getById((int)@$case->employee[0]);
			if($value)
				$records[] = [
					"id"			=> 0,
					"user_id"		=> (int)$value->id,
					"title" 		=> (string)@$value->firstname,
					"firstname" 	=> (string)@$value->firstname,
					"lastname"		=> (string)@$value->lastname,
					"phone" 		=> (string)@$value->phone,
					"date"			=> Contacts::dateFormat($value->created_at, "Y-m-d H:i:s")
				];
		}

		if ($count > 0)
		{
			foreach($data as $value)
			{
				$records[] = [
					"id"			=> (int)$value->id,
					"title"			=> (string)$value->title,
					"firstname"		=> (string)$value->firstname,
					"lastname"		=> (string)$value->lastname,
					"phone"			=> (string)$value->phone,
					"email"			=> (string)$value->email,
					"date"			=> Contacts::dateFormat($value->created_at, "Y-m-d H:i:s")
				];
			}
		}


		if(count($records) > 0)
		{
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $records,
			];
		}
		else
		{
			$response = [
				"status"		=> "error",
				"description"	=> $this->lang->get("noInformation"),
				"error_code"	=> 1201,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function addAction()
	{
		$error 			= false;

		$title 			= trim($this->request->get("title"));
		$firstname 		= trim($this->request->get("lastname"));
		$lastname 		= trim($this->request->get("lastname"));
		$phone	 		= trim($this->request->get("phone"));
		$email 			= trim($this->request->get("email"));

		if(Cache::is_brute_force("asdoadd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 1010, "hour" => 6100, "day" => 20100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($title) < 1 || strlen($title) > 400)
		{
			$error = $this->lang->get("TitleError");
		}
		elseif (strlen($firstname) < 1 || strlen($firstname) > 400)
		{
			$error = $this->lang->get("FirstnameError");
		}
		elseif (strlen($phone) < 5 || strlen($phone) > 400)
		{
			$error = $this->lang->get("PhoneError");
		}
		else
		{
			$userInsert = [
				"id"						=> Contacts::getNewId(),
				"creator_id"				=> (int)$this->auth->getData()->id,
				"user_id"					=> (int)@$this->auth->getData()->id,
				"title"						=> $title,
				"firstname"					=> $firstname,
				"lastname"					=> $lastname,
				"phone"						=> $phone,
				"email"						=> $email,
				"active"					=> 1,
				"is_deleted"				=> 0,
				"created_at"				=> Contacts::getDate(),
			];

			Contacts::insert($userInsert);

			$success = $this->lang->get("AddedSuccessfully", "Added successfully");

			$response = [
				"status"		=> "success",
				"description"	=> $success,
			];
		}
		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1202,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function editAction()
	{
		$error 			= false;

		$id 			= (int)$this->request->get("id");
		$title 			= trim($this->request->get("title"));
		$firstname 		= trim($this->request->get("lastname"));
		$lastname 		= trim($this->request->get("lastname"));
		$phone	 		= trim($this->request->get("phone"));
		$email 			= trim($this->request->get("email"));

		$data 			= Contacts::getById($id);

		if(Cache::is_brute_force("aasedit-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 401, "hour" => 3001, "day" => 9001]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif (strlen($title) < 1 || strlen($title) > 400)
		{
			$error = $this->lang->get("TitleError");
		}
		else
		{
			$update = [
				"title"						=> $title,
				"firstname"					=> $firstname,
				"lastname"					=> $lastname,
				"phone"						=> $phone,
				"email"						=> $email,
				"updated_at"				=> $this->mymongo->getDate()
			];

			Contacts::update(["id" => $id], $update);

			$response = [
				"status"		=> "success",
				"description"	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully")
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1202,
			];
		}


		echo json_encode($response, true);
		exit();
	}

	public function deleteAction()
	{
		$error 		= false;
		$id 		= (string)$this->request->get("id");

		$data 		= Contacts::getById($id);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		else
		{
			$update = [
				"is_deleted"		=> 1,
				"deleter_id"		=> $this->auth->getData()->id,
				"deleted_at"		=> Contacts::getDate()
			];
			Contacts::update(["id" => (int)$id], $update);

			$response = [
				"status"		=> "success",
				"description"	=> $this->lang->get("DeletedSuccessfully", "Deleted successfully")
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1202,
			];
		}
		echo json_encode($response, true);
		exit();
	}
}
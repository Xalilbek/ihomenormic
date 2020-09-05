<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\NoteFolders;
use Models\Notes;
use Models\Todo;
use Models\Users;

class NotefoldersController extends \Phalcon\Mvc\Controller
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
		$binds["is_deleted"] = 0;
		if($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}

		$data = NoteFolders::find([
			$binds,
			"sort"      => [
				"_id"	=> -1
			],
			"limit"     => $limit,
			"skip"      => $skip,
		]);

		$count = NoteFolders::count([
			$binds,
		]);

		$records = [];
		if ($count > 0)
		{
			foreach($data as $value)
			{
				$records[] = [
					"id"			=> (string)$value->_id,
					"title"			=> $value->title,
					"count"			=> (int)@$value->count,
					"date"			=> NoteFolders::dateFormat($value->created_at, "Y-m-d H:i:s")
				];
			}
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

	public function createAction()
	{
		$error 			= false;

		$title 			= trim($this->request->get("title"));

		if(Cache::is_brute_force("asdoadd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 1010, "hour" => 6100, "day" => 20100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($title) < 1 || strlen($title) > 400)
		{
			$error = $this->lang->get("TitleError");
		}
		else
		{
			$userInsert = [
				"id"						=> NoteFolders::getNewId(),
				"creator_id"				=> (int)$this->auth->getData()->id,
				"title"						=> $title,
				"active"					=> 1,
				"is_deleted"				=> 0,
				"created_at"				=> NoteFolders::getDate(),
			];
			if($this->auth->getData()->type == "employee"){
				$userInsert["employee"] 	 = (int)$this->auth->getData()->id;
			}else{
				$userInsert["citizen"] 	 = (int)$this->auth->getData()->id;
			}

			NoteFolders::insert($userInsert);

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

		$id 			= (string)$this->request->get("id");
		$title 			= trim($this->request->get("title"));

		$binds			= ["_id" => NoteFolders::objectId($id)];
		if($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}
		$binds["is_deleted"] = ['$ne' => 1];
		$data 			= NoteFolders::findFirst([$binds]);

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
				"title"							=> $title,
				"updated_at"					=> $this->mymongo->getDate()
			];

			NoteFolders::update(["_id" => NoteFolders::objectId($id)], $update);

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

		$binds			= ["_id" => NoteFolders::objectId($id)];
		if($this->auth->getData()->type ==	"citizen"){
			$binds["citizen"] 	 = $this->auth->getData()->id;
		}else{
			$binds["employee"] 	 = $this->auth->getData()->id;
		}
		$binds["is_deleted"] = ['$ne' => 1];
		$data 			= NoteFolders::findFirst([$binds]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		else
		{
			$update = [
				"is_deleted"		=> 1,
				"deleter_id"		=> $this->auth->getData()->id,
				"deleted_at"		=> $this->mymongo->getDate()
			];
			NoteFolders::update(["_id" => NoteFolders::objectId($id)], $update);

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
<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\NoteFolders;
use Models\Notes;
use Models\Todo;
use Models\Users;

class NotesController extends \Phalcon\Mvc\Controller
{
	public function minlistAction()
	{

	}

	public function listAction()
	{
		$error 		= false;
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
		$folderId = (string)$this->request->get("folder_id");

		if(strlen($folderId) == 0)
		{
			$error = $this->lang->get("FoldertNotFound", "Folder doesn't exist");
		}
		else
		{
			$fbinds			= ["_id" => NoteFolders::objectId($folderId)];
			if($this->auth->getData()->type ==	"citizen"){
				$fbinds["citizen"] 	 = $this->auth->getData()->id;
			}else{
				$fbinds["employee"] 	 = $this->auth->getData()->id;
			}
			$fbinds["is_deleted"] = ['$ne' => 1];
			$folder 			= NoteFolders::findFirst([$fbinds]);

			if(!$folder)
			{
				$error = $this->lang->get("FoldertNotFound", "Folder doesn't exist");
			}
			else
			{
				$binds["folder"] 	 = (int)$folder->id;

				$data = Notes::find([
					$binds,
					"sort"      => [
						"_id"	=> -1
					],
					"limit"     => $limit,
					"skip"      => $skip,
				]);

				$count = Notes::count([
					$binds,
				]);

				$categories = $this->parameters->getList($this->lang, "todo_categories", [], true);

				$records = [];
				if ($count > 0)
				{
					foreach($data as $value)
					{
						$records[] = [
							"id"			=> $value->id,
							//"folder_id"		=> (int)$folderId,
							"case"			=> (int)$value->case,
							"title"			=> $value->title,
							"description"	=> $value->description,
							"date"			=> @$this->mymongo->dateFormat($value->created_at, "d.m.y"),
						];
					}
					$response = [
						"status"		=> "success",
						"description"	=> "",
						"data"			=> $records,
					];
				}else{
					$error	= $this->lang->get("noInformation");

				}
			}
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1321,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function createAction()
	{
		$error 			= false;

		$title 			= trim($this->request->get("title"));
		$description 	= trim($this->request->get("description"));
		$folder 		= (string)$this->request->get("folder_id");

		if(strlen($folder) > 0){
			$binds			= ["_id" => NoteFolders::objectId($folder)];
			if($this->auth->getData()->type ==	"citizen"){
				$binds["citizen"] 	 = $this->auth->getData()->id;
			}else{
				$binds["employee"] 	 = $this->auth->getData()->id;
			}
			$binds["is_deleted"] = ['$ne' => 1];
			$folderData 	= NoteFolders::findFirst([$binds]);
		}


		if(Cache::is_brute_force("todoadd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 1010, "hour" => 6100, "day" => 20100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($title) < 1 || strlen($title) > 400)
		{
			$error = $this->lang->get("TitleError");
		}
		elseif (!$folderData)
		{
			$error = $this->lang->get("FolderNotFound", "Folder not found");
		}
		else
		{
			$Insert = [
				"id"						=> Notes::getNewId(),
				"title"						=> $title,
				"description"				=> $description,
				"folder_oid"				=> (string)$folderData->_id,
				"folder"					=> $folderData->id,
				"case"						=> (int)$this->request->get("case"),
				"citizen"					=> (int)$this->request->get("citizen"),
				"employee"					=> (int)$this->request->get("employee"),
				"active"					=> 1,
				"is_deleted"				=> 0,
				"created_at"				=> $this->mymongo->getDate()
			];
			if($this->auth->getData()->type == "employee"){
				$Insert["employee"] 	 = (int)$this->auth->getData()->id;
			}elseif($this->auth->getData()->type == "citizen"){
				$Insert["employee"] 	 = (int)$this->auth->getData()->id;
			}else{
				$Insert["contact_person"] 	 = (int)$this->auth->getData()->id;
			}

			Notes::insert($Insert);

			NoteFolders::increment(["_id" => Notes::objectId($folder)], ["count" => 1]);

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
		$category		= (int)$this->request->get("category");

		$data 			= Notes::getById($id);

		if(Cache::is_brute_force("asdasd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 401, "hour" => 3001, "day" => 9001]))
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
			$date 	= trim($this->request->get("date"));
			$date 	= substr($date, 6, 4)."-".substr($date, 0, 2)."-".substr($date, 3, 2);
			$update = [
				"title"							=> $title,
				"category"						=> $category,
				"description"					=> substr(trim($this->request->get("description")), 0, 10000),
				//"case"							=> (int)$this->request->get("case"),
				//"date"							=> $this->mymongo->getDate(strtotime($date)),
				"updated_at"					=> $this->mymongo->getDate()
			];

			Notes::update(["id" => (int)$id], $update);

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
		$id 		= (int)$this->request->get("id");
		$data 		= Notes::findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> 0,
			]
		]);

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
			Notes::update(["id"	=> (int)$id], $update);

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

	public function testAction()
	{

	}
}
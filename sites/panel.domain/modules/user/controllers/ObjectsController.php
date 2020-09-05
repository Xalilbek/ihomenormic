<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Objects;
use Models\Cache;

class ObjectsController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$error      = false;
		$skip 		= (int)$this->request->get("skip");
		$limit 		= (int)$this->request->get("limit");
		if($limit == 0)
			$limit = 50;
		if($limit > 200)
			$limit = 200;

		$query		= Objects::find([
			[
				"user_id"		=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			],
			"skip"	=> $skip,
			"limit"	=> $limit,
			"sort"	=> [
				"_id"	=> 1
			]
		]);
		$data 		= [];
		if(count($query) > 0)
		{
			foreach($query as $value)
			{
				$data[] = [
					"id"			=> $value->id,
					"serial_id"		=> $value->serial_id,
					"title"			=> htmlspecialchars($value->title),
				];
			}

			$response = array(
				"status" 		=> "success",
				"data"			=> $data,
			);
		}
		else
		{
			$error = $this->lang->get("uDontHaveObj", "Object not found");
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1023,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}


	public function addAction()
	{
		$error 		= false;
		$serial_id 	= trim($this->request->get("serial_id"), " ");
		$title		= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("title"))));

		if(Cache::is_brute_force("objAdd-".$serial_id, ["minute"	=> 20, "hour" => 50, "day" => 100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		elseif (strlen($serial_id) < 6 || strlen($serial_id) > 40)
		{
			$error = $this->lang->get("SerialIdWrong", "IMEI is wrong");
		}
		else
		{
			$objExist =  Objects::findFirst([["serial_id" => $serial_id, "is_deleted"	=> 0]]);

			if($objExist)
			{
				$error = $this->lang->get("ObjectExists", "Object exists");
			}
			else
			{
				$id = (int)Objects::getNewId();
				$userInsert = [
					"id"			=> $id,
					"serial_id"		=> $serial_id,
					"user_id" 		=> (int)$this->auth->getData()->id,
					"title"			=> $title,
					"is_deleted"	=> 0,
					"created_at"	=> MyMongo::getDate()
				];

				Objects::insert($userInsert);

				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("AddedSuccessfully", "Added successfully"),
					"data"			=> [
						"id"			=> $id,
						"serial_id"		=> $serial_id,
						"title"			=> $title,
					]
				);
			}
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}

	public function editAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$title 		= trim(str_replace(["<",">"], "",trim($this->request->get("title"))));
		$data 		= Objects::findFirst([
			[
				"id" 			=> (int)$id,
				"user_id" 		=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			]
		]);

		if(Cache::is_brute_force("editObj-".$id, ["minute"	=> 30, "hour" => 100, "day" => 300]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("editObj-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 600, "day" => 1500]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		elseif (strlen($title) < 2 || strlen($title) > 50)
		{
			$error = $this->lang->get("TitleError", "Title is wrong. (minimum 2 and maximum 40 characters)");
		}
		else
		{
			$update = [
				"title"			=> $title,
				"updated_at"	=> MyMongo::getDate()
			];
			Objects::update(["id"	=> (int)$id], $update);

			$response = [
				"status" 		=> "success",
				"description" 	=> $this->lang->get("UpdatedSuccessfully", "Updated successfully")
			];
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}


	public function infoAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$data 		= Objects::findFirst([
			[
				"id" 			=> (int)$id,
				"user_id" 		=> (int)$this->auth->getData()->id,
				"is_deleted"	=> 0,
			]
		]);

		if (!$data)
		{
			$error = $this->lang->get("ObjectNotFound", "Object doesn't exist");
		}
		else
		{
			$response = [
				"status" 		=> "success",
				"data" 			=> [
					"id"			=> $id,
					"serial_id"		=> $data->serial_id,
					"title"			=> (string)$data->title,
				]
			];
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}

	public function deleteAction()
	{
		$error 		= false;
		$id 		= (int)$this->request->get("id");
		$data 		= Objects::findFirst([
			[
				"id" 			=> (int)$id,
				"user_id" 		=> (int)$this->auth->getData()->id,
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
				"is_deleted"	=> 1,
				"deleted_by"	=> "user",
				"deleted_at"	=> MyMongo::getDate()
			];
			Objects::update(["id"	=> (int)$id], $update);

			$response = [
				"status" 		=> "success",
				"description" 	=> $this->lang->get("DeletedSuccessfully", "Deleted successfully")
			];
		}

		if($error)
		{
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}
}
<?php

namespace Controllers;

use Lib\MyMongo;
use Models\Parameters;
use Models\Users;

class SettingsController extends \Phalcon\Mvc\Controller
{
	public static $table;

	public static $arr;

	public static $data;

	public static $title;

	public function initialize()
	{
		$type = $this->request->get("type");

		self::$table = $this->parameters->setCollection($type, $this->lang);
		self::$title = $this->parameters->getTitle();

		if(!self::$table){
			header("Location: "._PANEL_ROOT_."/");
		}
		$cat_query  = self::$table->find([["is_deleted" => ['$ne' => 1]], "sort" => ["id" => 1]]);

		$cat_arr    = [];
		$cat_data   = [];
		$lang_keys  = [];

		IF (count($cat_query) > 0)
		{
			foreach($cat_query as $value)
			{
				//if((int)$value->parent_id == 0)
				$cat_arr[(int)$value->parent_id][] = (int)$value->id;
				$cat_data[(int)$value->id] = [
					"id"        => (int)$value->id,
					"active"    => (int)@$value->active,
					"title"     => htmlspecialchars(strlen(trim(@$value->titles->{_LANG_})) > 0 ? $value->titles->{_LANG_}: $value->titles->{$value->default_lang}),
				];
			}
		}
		$this->view->setVar("type", $type);
		$this->view->setVar("title", self::$title);
		$this->view->setVar("cat_arr", $cat_arr);
		$this->view->setVar("cat_data", $cat_data);
	}

	public function listAction()
	{
		$records = array();
		if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
			foreach($this->request->get("id") as $id){
				$error = false;
				$item = Users::findById($id);
				$status = (int)$this->request->get("customActionName");
				$status = (in_array($status, [0,1,2])) ? $status: 2;
				$item->active = $status;
				//$item->save();
			}
			if ($error){
				$records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
			}else{
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = RequestDoneSuccessfully; // pass custom message(useful for getting status of group actions)
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

		$binds = ["is_deleted" => ['$ne' => 1]];
		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");
		if ($this->request->get("title"))
			$binds["titles.".$this->lang->getLang()] = [
				'$regex' => trim(($this->request->get("title"))),
				'$options'  => 'i'
			];

		$binds["is_deleted"] = ['$ne' => 1];

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
		$data = self::$table->find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);
		$count = self::$table->count([
			$binds,
		]);

		if ($data)
		{
			foreach($data as $value)
			{
				$title = strlen(trim(@$value->titles->{$this->lang->getLang()})) > 0 ? $value->titles->{$this->lang->getLang()}: $value->titles->{$value->default_lang};
				if(strlen(trim($title)) < 1)
					foreach(@$value->titles as $vTitle)
						if(strlen(trim($vTitle)) > 0)
							$title = trim($vTitle);
				$records["data"][] = array(
					$value->id,
					$title,
					'<a href="'._PANEL_ROOT_.'/settings/edit/'.$value->id.'?type='.$this->request->get("type").'" class=""><i class="la la-edit"></i></a>'.
					'<a href="'._PANEL_ROOT_.'/settings/delete/'.$value->id.'?type='.$this->request->get("type").'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>',
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


	public function addAction($id)
	{
		$error 		= false;
		$success 	= false;
		if ($this->request->get("save"))
		{
			$added = false;
			$titles = [];
			foreach($this->request->get("titles") as $lang => $value){
				$lang = strtolower($lang);
				$name = trim($value);
				if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
				{
					$titles[$lang] = $name;
					$added = true;
				}
			}
			if(!$added)
			{
				$error = AllFieldsAreEmpty;
			}
			else
			{
				$selfId = self::$table->getNewId();
				$insert = [
					"id"            => $selfId,
					"parent_id"     => (int)$id,
					"titles"        => $titles,
					"active"        => 1,
					"is_deleted"    => 0,
					"index"         => (int)$selfId,
					"default_lang"  => _LANG_,
					"slug"          => str_replace(" ", "_", strtolower(@$titles["en"])),
					"created_at"    => MyMongo::getDate(),
				];
				if($this->request->get("type") == "goals")
				{
					$focusAreas = [];
					foreach($this->request->get("focus_area") as $value)
						$focusAreas[] = (int)$value;
					$insert["focus_area"] = $focusAreas;
				}
				elseif($this->request->get("type") == "focustypes")
				{
					$insert["focus_area"] = (int)$this->request->get("focus_area");
				}
				elseif($this->request->get("type") == "cities")
				{
					$insert["post_number"] = trim($this->request->get("post_number"));
				}
				elseif($this->request->get("type") == "system_languages")
				{
					$insert["code"] = substr(trim(strtolower($this->request->get("code"))),0,5);
				}
				elseif(in_array($this->request->get("type"), ["colors","activity_statuses","trading_plan_statuses","todo_categories","report_statuses","calendar_statuses"]))
				{
					$insert["html_code"] = "#".trim($this->request->get("html_code"));
				}
				elseif($this->request->get("type") == "questions")
				{
					$caseCategories = [];
					foreach($this->request->get("category") as $value)
						$caseCategories[] = (int)$value;

					$insert["category"] = $caseCategories;
					$goals = [];
					foreach($this->request->get("goal") as $value)
						$goals[] = (int)$value;
					$insert["goal"] = $goals;
				}

				self::$table->insert($insert);
				$success = AddedSuccessfully;
			}
		}


		$this->view->setVar("id", $id);
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function editAction($id)
	{
		//ini_set("display_errors", 1);
		$error 		= false;
		$success 	= false;
		$data      	= self::$table->findFirst([["id" => (int)$id]]);
		if (!$data)
		{
			$error = NoCategory;
		}
		else if ($this->request->get("save"))
		{
			$added = false;
			$titles = [];
			foreach($this->request->get("titles") as $lang => $value)
			{
				$lang = strtolower($lang);
				$name = trim($value);
				if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
				{
					$titles[$lang] = $name;
					$added = true;
				}
			}
			if(!$added)
			{
				$error = AllFieldsAreEmpty;
			}
			else
			{
				$update = [
					"titles"        => $titles,
					"active"        => (int)$this->request->get("active"),
					"updated_at"    => MyMongo::getDate(),
				];
				if($this->request->get("type") == "goals")
				{
					$focusAreas = [];
					foreach($this->request->get("focus_area") as $value)
						$focusAreas[] = (int)$value;
					$update["focus_area"] = $focusAreas;
				}
				elseif($this->request->get("type") == "focustypes")
				{
					$update["focus_area"] = (int)$this->request->get("focus_area");
				}
				elseif($this->request->get("type") == "cities")
				{
					$update["post_number"] = trim($this->request->get("post_number"));
				}
				elseif($this->request->get("type") == "system_languages")
				{
					$insert["code"] = substr(trim(strtolower($this->request->get("code"))),0,5);
				}
				elseif(in_array($this->request->get("type"), ["colors","activity_statuses","trading_plan_statuses","todo_categories","report_statuses","calendar_statuses"]))
				{
					$update["html_code"] = '#'.trim($this->request->get("html_code"));
				}
				elseif(in_array($this->request->get("type"), ["questions"]))
				{
					$caseCategories = [];
					foreach($this->request->get("category") as $value)
						$caseCategories[] = (int)$value;

					$update["category"] = $caseCategories;
					$goals = [];
					foreach($this->request->get("goal") as $value)
						$goals[] = (int)$value;
					$update["goal"] = $goals;
				}
				self::$table->update(["id" => (int)$id], $update);
				$data = self::$table->findFirst([["id" => (int)$id]]);

				$success = $this->lang->get("UpdatedSuccessfully");
			}
		}
		$this->view->setVar("id", $id);
		$this->view->setVar("data", $data);
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}

	public function setindexAction($cat_id)
	{
		$data = self::$table->findFirst([["id" => (int)$cat_id]]);
		if($data){
			$update = [
				"index"         => (int)$this->request->get("index_id"),
				"updated_at"    => MyMongo::getDate(),
			];
			self::$table->update(["id" => (int)$cat_id], $update);
		}
		exit;
	}

	public function setvisibleAction($cat_id)
	{

		$data = self::$table->findFirst([["id" => (int)$cat_id]]);
		if($data){
			$update = [
				"active"        => (int)$this->request->get("status"),
				"updated_at"    => MyMongo::getDate(),
			];
			self::$table->update(["id" => (int)$cat_id], $update);
		}
		exit;
	}

	public function indexAction()
	{

		$this->view->setVar("hasDatatable", true);

	}


	public function deleteAction($id)
	{
		$error 		= false;
		$success 	= false;
		$data 		= self::$table->findFirst([
			[
				"id" 			=> (int)$id,
				"is_deleted"	=> ['$ne' => 1],
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
			self::$table->update(["id"	=> (int)$id], $update);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
	}
}
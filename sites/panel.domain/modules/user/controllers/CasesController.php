<?php
namespace Controllers;

use Lib\MyMongo;
use Models\CasePlans;
use Models\Cases;
use Models\Documents;
use Models\Cache;
use Models\Partner;
use Models\TempFiles;
use Models\Users;

class CasesController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$binds = [];
		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

		$binds["is_deleted"] = 0;

		$data =  Cases::find([
			$binds,
			"limit"     => 100,
			"skip"      => 0,
		]);
		$citizenIds = [];
		$employeeIds = [];
		foreach($data as $value){
			$citizenIds[] 	= (int)$value->citizen[0];
			$employeeIds[] 	= (int)$value->employee[0];
		}


		$this->view->setVar("citizens", Users::find([["id" => ['$in' => $citizenIds], "type" => "citizen", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("employees", Users::find([["id" => ['$in' => $employeeIds], "type" => "employee", "is_deleted" => ['$ne' => 1]]]));
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
					Cases::update(
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
		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

		if ($this->request->get("item_id") > 0)
			$binds["id"] = (int)$this->request->get("item_id");

		if (in_array($this->request->get("active"), ["0","1","2"]))
			$binds["active"] = (int)$this->request->get("active");

		if ($this->request->get("citizen") > 0)
			$binds["citizen"] = (int)$this->request->get("citizen");

		if ($this->request->get("type") == "citizen")
			$binds["citizen"] = (int)$this->request->get("id");

		if ($this->request->get("type") == "employee")
			$binds["employee"] = (int)$this->request->get("id");

		if ($this->request->get("focus_type") > 0)
			$binds["focus_type"] = (int)$this->request->get("focus_type");

		if ($this->request->get("focus_area") > 0)
			$binds["focus_area"] = (int)$this->request->get("focus_area");

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
		$data =  Cases::find([
			$binds,
			"sort"      => $orderBy,
			"limit"     => $iDisplayLength,
			"skip"      => $iDisplayStart,
		]);

		$count = Cases::count([
			$binds,
		]);

		$ids = [
			"citizens" 		=> [],
			"employees" 	=> [],
			"focus_areas" 	=> [],
			"focus_types" 	=> [],
		];

		foreach($data as $value)
		{
			$ids["citizens"] 	= array_unique(array_merge($ids["citizens"], $value->citizen));
			$ids["employees"] 	= array_unique(array_merge($ids["employees"], $value->employee));
			$ids["focus_areas"] = array_unique(array_merge($ids["focus_areas"], $value->focus_area));
			$ids["focus_types"] = array_unique(array_merge($ids["focus_types"], $value->focus_type));
		}
		$citizens = [];
		if(count($ids["citizens"]) > 0)
		{
			$query = Users::find(["id" => ['$in' => $ids["citizens"]]]);
			foreach($query as $value)
				$citizens[$value->id] = $value;
		}
		$employees = [];
		if(count($ids["employees"]) > 0)
		{
			$query = Users::find(["id" => ['$in' => $ids["employees"]]]);
			foreach($query as $value)
				$employees[$value->id] = $value;
		}
		$focus_areas 	= count($ids["focus_areas"]) > 0 ? $this->parameters->getListByIds($this->lang, "focusareas", $ids["focus_areas"], true): [];
		$focus_types 	= count($ids["focus_types"]) > 0 ? $this->parameters->getListByIds($this->lang, "focustypes", $ids["focus_types"], true): [];

		if ($data)
		{
			foreach($data as $value)
			{
				$list = [];
				$list[] = '<input type="checkbox" name="id[]" value="'.$value->id.'">';
				$list[] = $value->id;
				$list[] = htmlspecialchars(@$employees[$value->employee[0]]->firstname." ".@$employees[$value->employee[0]]->lastname);
				$list[] = htmlspecialchars(@$citizens[$value->citizen[0]]->firstname." ".@$citizens[$value->citizen[0]]->lastname);
				$list[] = htmlspecialchars(@$focus_areas[$value->focus_area[0]]["title"]);
				$list[] = htmlspecialchars(@$focus_types[$value->focus_type[0]]["title"]);
				$list[] = '<a href="'._PANEL_ROOT_.'/case/'.$value->id.'" class=""><i class="la la-user"></i></a>';
					//'<a href="'._PANEL_ROOT_.'/cases/delete/'.$value->id.'" class="margin-left-10"><i class="la la-trash redcolor"></i></a>';

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
}
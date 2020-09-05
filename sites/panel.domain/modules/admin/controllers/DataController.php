<?php
namespace Controllers;

use Models\Users;

class DataController extends \Phalcon\Mvc\Controller
{
	public function citiesAction()
	{
		$data = $this->parameters->getList($this->lang, "cities");

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}

	public function languagesAction()
	{
		$data = $this->parameters->getList($this->lang, "languages");

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}

	public function goalsAction()
	{
		$filter = [];
		if($this->request->get("focusarea") > 0)
			$filter["focus_area"] = (int)$this->request->get("focusarea");
		$data = $this->parameters->getList($this->lang, "goals", $filter);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}

	public function focustypesAction()
	{
		$filter = [];
		if($this->request->get("focusarea") > 0)
			$filter["focus_area"] = (int)$this->request->get("focusarea");
		$data = $this->parameters->getList($this->lang, "focustypes", $filter);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function questionsAction()
	{
		$filter = [];
		if($this->request->get("case_category") > 0)
			$filter["category"] = (int)$this->request->get("case_category");
		if($this->request->get("goal") > 0)
			$filter["goal"] = (int)$this->request->get("goal");
		$data = $this->parameters->getList($this->lang, "questions", $filter);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}

	public function contactsAction()
	{
		$filter = [];

		$filter["partner"] 		= (int)$this->request->get("partner_id");
		$filter["type"] 		= 'partner';
		$filter["is_deleted"] 	= ['$ne' => 1];

		$partners = Users::find([
			$filter,
			"limit"	=> 100,
			"skip"	=> 0,
			"sort"	=> [
				"firstname"	=> 1
			]
		]);

		$data = [];
		foreach($partners as $value)
			$data[] = [
				"id"		=> $value->id,
				"title"		=> htmlspecialchars($value->firstname." ".$value->lastname),
			];

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}

	public function multipleAction()
	{
		$data_types = $this->request->get("data_types");

		$data = [];
		foreach($data_types as $value)
			$data[$value] = $this->parameters->getList($this->lang, $value);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}

	public function offertemplatesAction()
	{
		$offer_type = (int)$this->request->get("offer_type");

		$layout = file_get_contents("mailtemplates/focustypes/".$offer_type.".html");
		$layout = str_replace("{date}", date("d.m.Y"), $layout);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $layout,
		];

		echo json_encode($response, true);
		exit();
	}
}
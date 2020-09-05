<?php
namespace Controllers;

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


	public function todocategoriesAction()
	{
		$data = $this->parameters->getList($this->lang, "todo_categories", ["for" => "employee"]);

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function tradingplanstatusesAction()
	{
		$data = $this->parameters->getList($this->lang, "trading_plan_statuses");

		$response = [
			"status"		=> "success",
			"description"	=> "",
			"data"			=> $data,
		];

		echo json_encode($response, true);
		exit();
	}


	public function activitystatusesAction()
	{
		$data = $this->parameters->getList($this->lang, "activity_statuses");

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
			$filter["focusarea"] = (int)$this->request->get("focusarea");
		$data = $this->parameters->getList($this->lang, "goals", $filter);

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
}
<?php
namespace Controllers;

use http\Client\Curl\User;
use Lib\MyMongo;
use Models\CaseReports;
use Models\Cases;
use Models\Countries;
use Models\Documents;
use Models\Objects;
use Models\TempFiles;
use Models\Tokens;
use Models\Users;
use Models\Cache;

class ReportController extends \Phalcon\Mvc\Controller
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

		$case = Cases::getById((int)$this->request->get("id"));
		if(!$case)
		{
			$error = $this->lang->get("caseNotFound", "Case not found");
		}
		else
		{
			$query = CaseReports::find([
				[
					"case"			=> (int)$case->id,
					"status"        => 2,
					"is_deleted"	=> 0,
				],
				"skip"	=> $skip,
				"limit"	=> $limit,
				"sort"	=> [
					"_id"	=> -1
				]
			]);
			$data 		= [];
			if(count($query) > 0)
			{
				foreach($query as $value)
				{
					$data[] = [
						"_id"			=> (string)$value->_id,
						"id"			=> $value->id,
						"title"		    => "ID: ".$value->id,
						"date"			=> CaseReports::dateFormat($value->created_at, "d/m/Y H:i"),
					];
				}

				$response = array(
					"status" 		=> "success",
					"data"			=> $data,
				);
			}
			else
			{
				$error = $this->lang->get("reportNotFound", "Report not found");
			}
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



	public function paramsAction()
	{
		$id 		= trim($this->request->get("id"));
		$report 	= CaseReports::findById($id);
		$values 	= ($report) ? json_decode(json_encode($report->values), true): false;

        $case 	    = Cases::getById((int)$this->request->get("case"));
        $userData   = Users::getById($case->citizen[0]);
        if(!$values){
            $values = [];
            $values["type_1_1"] = (string)$userData->leakbot_id;
        }

        $data 		= CaseReports::getSettings($this->lang, $values, $this->auth->getData(), $userData);

		if($report)
		{
			$fileIds = [];
			$keyById = [];
			foreach($data as $value)
				if($value["children"])
					foreach($value["children"] as $form)
						if($form["type"] == "file" && $values[$form["key"]])
							foreach($values[$form["key"]] as $file)
							{
								$fileIds[] = CaseReports::objectId($file["file_id"]);
								$keyById[$file["file_id"]] = $form["key"];
							}

			if(count($fileIds) > 0)
			{
				$filesQuery = Documents::find([
					[
						"_id" => [
							'$in' => $fileIds
						],
					],
				]);
				$images = [];
				foreach($filesQuery as $value)
				{
					$images[$keyById[(string)@$value->_id]][] = [
						"file_id"	=> (string)$value->_id,
						"avatar" 	=> FILE_URL."/upload/".$value->uuid."/".(string)$value->_id."/small.jpg",
						"original" 	=> FILE_URL."/upload/".$value->uuid."/".(string)$value->_id."/org.jpg",
					];
				}

				foreach($data as $value)
					if($value["children"])
						foreach($value["children"] as $form)
							if($form["type"] == "file" && $values[$form["key"]])
								$values[$form["key"]] = $images[$form["key"]];
				//exit(json_encode($values));
			}

		}

		if(!$values["type_2_1"])
			$values["type_2_1"] = (int)$this->auth->getData()->id;

		$response = [
			"status" 		=> "success",
			"data"			=> $data,
			"values"		=> count($values) > 0 ? (object)$values: [],
			"uuid"			=> md5($this->auth->getData()->id."-".microtime()),
            "editable"      => $id > 0 ? true: true,
            "show_finish"   => true,
		];

		echo json_encode($response, true);
		exit();
	}


	public function saveAction()
	{
		$values     = json_decode($this->request->get("values"), true);
		$action     = strtolower($this->request->get("action"));
		$case 	    = Cases::getById((int)$this->request->get("case"));
		if(!$case)
		{
			$error = $this->lang->get("caseNotFound", "Case not found");
		}
		else
		{
			$R = CaseReports::findById($this->request->get("id"));
			if(!$R){
				$R 				= new CaseReports();
				$R->id 			= CaseReports::getNewId();
				$R->case 		= (int)$case->id;
				$R->creator_id 	= (int)$this->auth->getData()->id;
				$R->status 		= 2;
				$R->is_deleted 	= 0;
				$R->created_at 	= CaseReports::getDate();
			}
			$R->values 		= $values;
			$insertId = (string)$R->save();


			// #################### starts HANDLING FILES ##################
			$settings 		= CaseReports::getSettings($this->lang, false, $this->auth->getData());
			$fileIds = [];
			$keyById = [];
			foreach($settings as $value)
				if($value["children"])
					foreach($value["children"] as $form){
                        if($form["type"] == "file" && $values[$form["key"]]){
                            foreach($values[$form["key"]] as $file)
                            {
                                $fileIds[] = CaseReports::objectId($file["file_id"]);
                                $keyById[$file["file_id"]] = $form["key"];
                            }
                        }elseif($form["type"] == "multiselect"){
                            $ids = [];
                            foreach ($values[$form["key"]] as $id)
                                $ids[] = (int)$id;
                            $values[$form["key"]] = $ids;
                        }
                    }

			if(count($fileIds) > 0)
			{
				$filesQuery = TempFiles::find([
					[
						"_id" => [
							'$in' => $fileIds
						]
					]
				]);
				$images = [];
				foreach($filesQuery as $value)
				{
					if(!Documents::findById($value->_id))
					{
						$document = [
							"_id"				=> $value->_id,
							"user_id"      		=> (int)$this->auth->getData()->id,
							"report_id"      	=> (string)$insertId,
							"uuid"              => $value->uuid,
							"type"              => $value->type,
							"filename"          => $value->filename,
							"is_deleted"        => 0,
							"created_at"        => $this->mymongo->getDate(),
						];
						Documents::insert($document);
					}

					$images[$keyById[(string)@$value->_id]][] = [
						"file_id"	=> (string)$value->_id,
						"avatar" 	=> FILE_URL."/upload/".$value->uuid."/".(string)$value->_id."/small.jpg",
					];
				}

				foreach($settings as $value)
					if($value["children"])
						foreach($value["children"] as $form)
							if($form->type == "file" && $values[$form["key"]])
								$values[$form["key"]] = $images[$form["key"]];

				CaseReports::update(
				    ["_id" => (CaseReports::objectId((string)$insertId))],
                    ["values" => $values, "status" => ($action == "finish"?1:2)]
                );
			}
			// #################### ends HANDLING FILES ##################


			$response = ["status" => "success", "description" => $this->lang->get("SavedSuccessfully", "Saved successfully")];
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

	public function editAction()
	{
		$error 		= false;
		$id 		= (string)$this->request->get("_id");
		$values 	= $this->request->get("values");
		$data 		= CaseReports::findById($id);
		if($data)
			$case 		= Cases::getById((int)$data->case);

		if(Cache::is_brute_force("editObj-".$id, ["minute"	=> 30, "hour" => 100, "day" => 300]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (!$data)
		{
			$error = $this->lang->get("caseNotFound", "Case not found");
		}
		else
		{
			$update = [
				"values"			=> $values,
				"updated_at"		=> MyMongo::getDate()
			];
			CaseReports::update(["_id"	=> $data->_id], $update);

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
}
<?php
namespace Controllers;

use Lib\Image;
use Models\Survey;
use Models\SurveyUsers;
use Models\TempFiles;

class SurveyController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$error 			= false;
		$success 		= false;
		$showForm 		= false;
		$surveyData 	= false;
		$hash 			= trim($this->request->get("hash"));
		$surveyId 		= trim($this->request->get("survey"));

		if(strlen($hash) < 5)
		{
			$error = "Link was corrupted";
		}
		else if(!$data=SurveyUsers::findById($hash))
		{
			$error = "Survey not found";
		}
		else if(!$data=SurveyUsers::findById($hash))
		{
			$error = "Survey not found";
		}
		else if($data->survey_id !== $surveyId)
		{
			$error = "Survey not found.";
		}
		else if(!$surveyData=Survey::findById($surveyId))
		{
			$error = "Survey not found.";
		}
		else if((int)$data->is_deleted == 1)
		{
			$error = "Link was expired";
		}
		else if((int)$data->is_filled == 1 && (int)$this->request->get("force") !== 1)
		{
			$error = "Form has been filled already";
		}
		else if($this->request->get("save"))
		{
			//exit($this->request->get("survey_json"));
			$json 			= json_decode($this->request->get("survey_json"), true);
			$update = [
				"queries"					=> count($json) > 0 ? json_encode($json, true): $data->queries,
				"is_filled"					=> 1,
				"answered_at"				=> $this->mymongo->getDate(),
			];

			SurveyUsers::update(["_id" => $data->_id], $update);

			$success = $this->lang->get("SavedSuccessfully", "Saved successfully");

			$data=SurveyUsers::findById($hash);

			//exit($this->request->get("survey_json"));
			/**
				Mailbox::update(
					[
						"_id" => $mail->_id,
					],
					[
						"is_read"	=> 1,
						"ip"		=> $this->lib->getIp(),
						"browser"	=> $this->request->getServer("HTTP_USER_AGENT"),
						"read_at"	=> $this->mymongo->getDate(),
					]
				);

				$log = [
					"mail_id"			=> 	(string)$mail->_id,
					"type"				=> 	"read",
					"ip"				=> 	$this->lib->getIp(),
					"browser"			=> 	$this->request->getServer("HTTP_USER_AGENT"),
					"created_at"		=> 	$this->mymongo->getDate(),
				];

				MailboxLogs::insert($log);

				exit($mail->content); */
		}else{
			$showForm = true;
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("data", $data);
		$this->view->setVar("surveyData", $surveyData);
		$this->view->setVar("showForm", $showForm);
		$this->view->partial("query/index");
		exit;
	}

	public function uploadAction()
	{
		define("UPLOAD_PHOTO_DIR", "/home/danimark/sites/panel.domain/public/upload/");
		$error      = false;
		$success    = false;
		$photo      = @$_FILES['file'];
		$filetype   = explode(".",$photo["name"]);
		$filetype   = $filetype[count($filetype)-1];
		$filename   = substr($photo["name"], 0, (strlen($photo["name"])-strlen($filetype)-1));
		$filename   = str_replace([' ', '-'], '_', trim($filename));
		$filename   = preg_replace('/[^A-Za-z0-9\_]/', '', $filename);
		if(strlen($filename) < 1)
			$filename = rand(1, 99999999);
		$puid       = $this->request->get("puid");

		$object = Survey::findById($this->request->get("survey_id"));
		if (!$object)
		{
			$error = $this->lang->get("notFound");
		}
		else if (count($photo) == 0)
		{
			$error = $this->lang->get("YouDidntChoosePhoto", "You didn`t choose photo");
		}
		else if (!in_array(strtolower($filetype), array("jpeg", "jpg", "png", "gif", "pdf", "doc","docx", "xls", "xlsx")))
		{
			$error = $this->lang->get("FileTypeNotAllowed", "This file type is not allowed");
		}
		else if ($photo['size'] > 30*1024*1024)
		{
			$error = $this->lang->get("FileSizeMaxLimit", "File size cann`t be larger than 30 MB");
		}
		else
		{
			$uuid   = (string)$object->_id;
			$dir    = UPLOAD_PHOTO_DIR.$uuid.'/';
			if (!is_dir($dir))
			{
				mkdir($dir,0777);
				chmod($dir, 0777);
			}
			$insert = [
				//"moderator_id"      => (int)$this->auth->getData()->id,
				"uuid"              => $uuid,
				"puid"              => $puid,
				"type"              => $filetype,
				"filename"          => $filename,
				"size"          	=> $photo['size'],
				"for"				=> "survey",
				"active"            => 1,
				"created_at"        => $this->mymongo->getDate(),
			];
			$insertId = (string)TempFiles::insert($insert);

			$dir2 = UPLOAD_PHOTO_DIR.$uuid.'/'.$insertId.'/';
			if (!is_dir($dir2))
			{
				mkdir($dir2,0777,true);
				chmod($dir2, 0777);
			}
			chmod($_FILES["photo"]["tmp_name"], 0777);

			if (in_array(strtolower($filetype), array("jpeg", "jpg", "png", "gif")))
			{
				list($width, $height) = getimagesize($photo["tmp_name"]);
				if($width > 0)
				{
					$proportion = $height / $width;
					/**
					$scale = 800;
					if ($width < $scale){
					$width = $scale;
					$height = $width * $proportion;
					}elseif($width >= $scale){
					$width = $scale;
					$height = $width * $proportion;
					}*/
					//copy($photo["tmp_name"], $dir2.'org.jpg');
					Image::resize($photo["tmp_name"], $width, $height, $dir2.'org.jpg', 100);
					Image::resize($photo["tmp_name"], 300, 300, $dir2.'medium.jpg', 90);
					Image::resize($photo["tmp_name"], 100, 100, $dir2.'small.jpg', 90);

					@chmod($dir2.'org.jpg', 0777);
					@chmod($dir2.'medium.jpg', 0777);
					@chmod($dir2.'small.jpg', 0777);

					$response = [
						"status"        => "success",
						"description"   => ".",
						"data"			=> [
							"id"		=> (string)$insertId,
							"type"		=> "image",
							"name"		=> (string)$filename,
							"avatar"	=> [
								"small"	=> FILE_URL."/upload/".$uuid."/".$insertId."/small.jpg",
							]
						]
					];
				}
				else
				{
					$error = $this->lang->get("FileTypeIsWrong", "Image type is wrong. Accepted types: JPEG, PNG, GIF");
				}
			}
			else
			{
				move_uploaded_file($photo["tmp_name"], $dir2.$filename.".".$filetype);
				$response = [
					"status"        => "success",
					"description"   => "..",
					"data"			=> [
						"id"		=> (string)$insertId,
						"type"		=> "doc",
						"name"		=> (string)$filename.".".$filetype,
						"avatar"	=> [
							"small"	=> FILE_URL."/resources/images/file.png",
						]
					]
				];
			}

		}

		if($error)
		{
			$response = [
				"status" 			=> "error",
				"err_code" 			=> 2501,
				"description" 		=> $error,
			];
		}
		echo json_encode($response, true);
		exit();
	}

}
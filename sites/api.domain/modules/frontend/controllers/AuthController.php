<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Countries;
use Models\Tokens;
use Models\Users;
use Models\Cache;

class AuthController extends \Phalcon\Mvc\Controller
{
	public function signinAction()
	{
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 	= trim($this->request->get("password"));
		$type 		= trim(strtolower($this->request->get("type"))) == "user" ? "user": "employee";

		if(Cache::is_brute_force("authIn-".$phone, ["minute"	=> 20, "hour" => 70, "day" => 110]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		else if(Cache::is_brute_force("authIn-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		else
		{
			$data = Users::findFirst([
				[
					"phone" 	=> $phone,
					"password" 	=> $this->lib->generatePassword($password),
					"type"		=> $type,
				]
			]);

			if ($data)
			{
				$token = $this->auth->createToken($this->request, $data);

				$response = array(
					"status" 		=> "success",
					"description" 	=> "",
					"token" 		=> $token,
					"data"			=> $this->auth->filterData($data, $this->lang),
				);
			}
			else
			{
				$error = $this->lang->get("LoginError", "Phone or password is wrong");
			}
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1001,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}


	public function signupAction()
	{
		$error = false;

		$dialcode 	= trim($this->request->get("dialcode"), " ");
		$number 	= trim($this->request->get("number"), " ");
		if(substr($number, 0, 1) == "0")
			$number = substr($number, 1);
		$msisdn 	= trim($dialcode.$number);
		$fullname	= str_replace(["<",">",'"',"'"], "", trim(urldecode($this->request->get("fullname"))));
		$password	= (string)$this->request->get("password");
		$username	= (string)strtolower($this->request->get("username"));

		$country = Countries::getByDialCode($dialcode);

		if(Cache::is_brute_force("authUp-".$msisdn, ["minute"	=> 20, "hour" => 50, "day" => 100]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("authUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}elseif (!$this->lib->checkUsername($username)){
			$error = $this->lang->get("UsernameError", "Username is wrong. (minimum 6 and maximum 20 characters");
		}elseif (in_array((int)$dialcode, ["380", "994"]) && strlen($msisdn) !== 12){
			$error = $this->lang->get("NumberError", "Number is wrong");
		}elseif (!in_array((int)$dialcode, ["380", "994"]) && strlen($msisdn) < 11){
			$error = $this->lang->get("NumberError", "Number is wrong");
		}elseif (!is_numeric($msisdn)){
			$error = $this->lang->get("NumberError", "Number is wrong");
		}elseif (Users::findFirst(["username" => $username])){
			$error = $this->lang->get("NumberExists", "Number exists");
		}elseif (strlen($fullname) < 2 || strlen($fullname) > 50){
			$error = $this->lang->get("FirstnameError", "Fullname is wrong. (minimum 2 and maximum 40 characters)");
		}elseif (strlen($password) < 6 || strlen($password) > 40){
			$error = $this->lang->get("PasswordError", "Password is wrong. (minimum 6 and maximum 40 characters)");
		}else{
			if(!$error)
			{
				$id = (int)Users::getNewId();
				$userInsert = [
					"id"			=> $id,
					"username"		=> $username,
					"password"		=> $this->lib->generatePassword($password),
					"msisdn"		=> $msisdn,
					"fullname"		=> $fullname,
					"created_at"	=> MyMongo::getDate()
				];

				Users::insert($userInsert);

				$token 			= md5($id."-".microtime()."-".rand(1,99999));
				$hash 			= md5($id."-".$this->request->get("REMOTE_ADDR")."-".microtime());

				$tokenInsert = [
					"user_id"		=> (float)$id,
					"token"			=> $token,
					"hash"			=> $hash,
					"ip"			=> $this->request->getServer("REMOTE_ADDR"),
					"device"		=> $this->request->getServer("HTTP_USER_AGENT"),
					"active"		=> 1,
					"created_at"	=> MyMongo::getDate()
				];
				Tokens::insert($tokenInsert);

				$response = array(
					"status" 		=> "success",
					"description" 	=> $this->lang->get("RegisteredSuccessfully", "You registered successfully"),
					"token" 		=> $token,
					"hash" 			=> $hash,
					"data"			=> [
						"id"			=> $id,
						"fullname"		=> $fullname,
						"phone"			=> $msisdn,
					]
				);
			}
		}

		if($error){
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}

	public function forgetstep1Action()
	{
		$error = false;

		$msisdn 	= trim(str_replace(["+"," "], "",trim($this->request->get("dialcode")).trim($this->request->get("number"))));

		if(Cache::is_brute_force("authIn-".$msisdn, ["minute"	=> 6, "hour" => 20, "day" => 40]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif(Cache::is_brute_force("authIn-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 15, "hour" => 250, "day" => 500]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		else
		{
			$data = MongoUsers::findFirst([["msisdn" => $msisdn]]);

			if(!$data)
			{
				$error = $this->lang->get("NumberDoesntExist", "Number doesn`t exists");
			}
			else
			{
				$operator = $data->operator;

				$L = MongoVerifyLog::findFirst([
					[
						"msisdn"	=> $msisdn,
						"created_at" => [
							'$gt'	=> time()-24*3600
						]
					],
					"sort"	=> [
						"created_at" => -1
					]
				]);

				if($L && @$L->created_at->sec < time()-24*3600){
					$L->delete();
					$L = false;
				}

				if(!$L)
				{
					$code = rand(111111, 999999);
					$hash = md5($msisdn . "-" . microtime(true));

					$L				= new MongoVerifyLog();
					$L->id 			= MongoVerifyLog::getNewId();
					$L->code 		= (string)$code;
					$L->hash 		= $hash;
					$L->status 		= 1;
					$L->check_limit = 0;
				}
				else
				{
					$L->check_limit = 0;
				}

				if(@$L->sms_count < 3){
					Lib::sendSMSua($msisdn, $this->lang->get("VerificationCode", "Verification Code").': ' . $L->code);
					@$L->sms_count += 1;
				}


				$L->operator 	= (int)$operator;
				$L->msisdn 		= $msisdn;
				$L->created_at 		= new \MongoDate(time());
				$L->save();

				$text = $this->lang->get("VerificationCodeSend", "Verification code was sent to your number");
				$response = [
					"status" => "success",
					"verify_hash" => (string)$hash,
					"description" => (string)$text
				];
			}
		}

		if($error){
			$response = [
				"status" 		=> "error",
				"error_code"	=> 1017,
				"description" 	=> $error,
			];
		}
		echo json_encode((object)$response);
		exit;
	}

	public function forgetdoneAction()
	{
		$key 		= trim($this->request->get("verify_hash"));
		$code   	= trim($this->request->get("code"));
		$password   = trim($this->request->get("password"));

		$temp = MongoVerifyLog::findFirst([
			[
				"hash" => $key,
				"status" => 1
			],
			"sort" => [
				"id"	=> -1
			]
		]);
		if (strlen($password) < 6 || strlen($password) > 30){
			$error = $this->lang->get("PasswordError", "Password is wrong. (minimum 6 and maximum 40 characters)");
		}elseif (!$temp){
			$error = $this->lang->get("VerificaitonCodeWrong", "Verification code is wrong");
		}elseif ($temp->check_limit > 8){
			$error = $this->lang->get("VerificaitonExpired", "Verification code has been expired");
		}else{
			$temp->check_limit 	+= 1;

			$data = MongoUsers::findFirst([["msisdn" => $temp->msisdn]]);
			if($temp->code == $code){
				$temp->delete();

				$data->password	= Lib::generatePassword($password);
				$data->save();


				$token 			= md5($data->id."-".microtime()."-".rand(1,99999));
				$hash 			= md5($data->id."-".$this->request->get("REMOTE_ADDR")."-".microtime());

				$T 				= new MongoTokens();
				$T->user_id 	= (float)$data->id;
				$T->token 		= $token;
				$T->hash 		= $hash;
				$T->ip			= $this->request->getServer("REMOTE_ADDR");
				$T->device		= $this->request->getServer("HTTP_USER_AGENT");
				$T->active		= 1;
				$T->created_at 	= new \MongoDate(time());
				$T->save();

				$response = array(
					"status" 	=> "success",
					"description" 	=> $this->lang->get("PasswordUpdated", "Password updated successfuly"),
					"token" 	=> $token,
					"hash" 		=> $hash,
					"data"		=> MongoUsers::filterDataForAuth($data)
				);

				$data->device_token_required = true;
				$data->save();
			}else{
				$error = $this->lang->get("VerificaitonCodeWrong", "Verification code is wrong");
				$temp->save();
			}
		}

		if($error){
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1401,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}
}
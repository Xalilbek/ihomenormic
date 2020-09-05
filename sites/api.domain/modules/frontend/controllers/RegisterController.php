<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Cache;
use Models\Users;

class RegisterController extends \Phalcon\Mvc\Controller
{
	public function citizenAction()
	{
		$error 		= false;

		$firstname 	= trim($this->request->get("firstname"));
		$lastname 	= trim($this->request->get("lastname"));
		$password 	= trim($this->request->get("password"));
		$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$email 		= trim($this->request->get("email"));
		$ssn 		= trim($this->request->get("ssn"));
		$place 		= trim($this->request->get("place"));

		$address 	= trim($this->request->get("address"));
		$zipcode 	= trim($this->request->get("zipcode"));
		$city 		= (int)$this->request->get("city");

		if(Cache::is_brute_force("citizenAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
		{
			$error = $this->lang->get("FirstnameError", "Firstname is empty");
		}
		elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
		{
			$error = $this->lang->get("LastnameError", "Lastname is empty");
		}
		elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$error = $this->lang->get("EmailError", "Email is wrong");
		}
		elseif (strlen($password) < 6 || strlen($password) > 100)
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		elseif (strlen($ssn) < 1 || strlen($ssn) > 100)
		{
			$error = $this->lang->get("SSNError", "Social social number is empty");
		}
		elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
		{
			$error = $this->lang->get("PhoneError", "Phone is wrong");
		}
		elseif (strlen($place) < 1 || strlen($place) > 100)
		{
			$error = $this->lang->get("PlaceError", "School / Kindergarden / Institution is empty");
		}
		elseif (strlen($address) < 1 || strlen($address) > 200)
		{
			$error = $this->lang->get("AddressError", "Address is empty");
		}
		elseif (strlen($zipcode) < 1 || strlen($zipcode) > 50)
		{
			$error = $this->lang->get("ZipCodeError", "Zip Code is empty");
		}
		elseif ($city == 0)
		{
			$error = $this->lang->get("CityError", "City is empty");
		}
		elseif (Users::findFirst([["phone" => $phone, "is_deleted" => 0]]))
		{
			$error = $this->lang->get("UserExists", "User exists");
		}
		else
		{
			$id = Users::getNewId();
			$userInsert = [
				"id"			=> $id,
				"firstname"		=> $firstname,
				"lastname"		=> $lastname,
				"phone"			=> $phone,
				"password"		=> $this->lib->generatePassword($password),
				"email"			=> $email,
				"ssn"			=> $ssn,
				"place"			=> $place,
				"address"		=> $address,
				"zipcode"		=> $zipcode,
				"city"			=> $city,
				"type"			=> "citizen",
				"is_deleted"	=> 0,
				"created_at"	=> $this->mymongo->getDate()
			];

			Users::insert($userInsert);

			$data = Users::getById($id);
			if($data)
			{
				list($token, $hash) = $this->auth->createToken($this->request, $data);
				$success = $this->lang->get("AddedSuccessfully", "Added successfully");

				$response = [
					"status"		=> "success",
					"description"	=> $success,
					"token"			=> $token,
					"hash"			=> $hash,
					"data"			=> $this->auth->filterData($data, $this->lang),
				];
			}
			else
			{
				$error = $this->lang->get("TechnicalError", "Technical error occurred. Please, try agian");
			}
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1101,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}

	public function employeeAction()
	{
		$error 			= false;

		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
		$phone 			= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$password 		= trim($this->request->get("password"));
		$email 			= trim($this->request->get("email"));
		$ssn 			= trim($this->request->get("ssn"));
		$gender 		= (int)$this->request->get("int");

		$languages 		= [];
		foreach($this->request->get("languages") as $value)
			$languages[] = (int)$value;

		$address 		= trim($this->request->get("address"));
		$zipcode 		= trim($this->request->get("zipcode"));
		$city 			= (int)$this->request->get("city");

		$paymentRegNum  = trim($this->request->get("payment_registration_number"));
		$paymentAccountNum  = trim($this->request->get("payment_account_number"));

		if(Cache::is_brute_force("objAdd-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 40, "hour" => 300, "day" => 900]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif (strlen($firstname) < 1 || strlen($firstname) > 100)
		{
			$error = $this->lang->get("FirstnameError", "Firstname is empty");
		}
		elseif (strlen($lastname) < 1 || strlen($lastname) > 100)
		{
			$error = $this->lang->get("LastnameError", "Lastname is empty");
		}
		elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$error = $this->lang->get("EmailError", "Email is wrong");
		}
		elseif (strlen($ssn) < 1 || strlen($ssn) > 100)
		{
			$error = $this->lang->get("SSNError", "Social social number is empty");
		}
		elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
		{
			$error = $this->lang->get("PhoneError", "Phone is wrong");
		}
		elseif (strlen($password) < 6 || strlen($password) > 100)
		{
			$error = $this->lang->get("PasswordError", "Password is wrong (min 6 characters)");
		}
		elseif (count($languages) < 1 || count($languages) > 100)
		{
			$error = $this->lang->get("LanguageNotChoosen", "Language is empty");
		}
		elseif (strlen($address) < 1 || strlen($address) > 200)
		{
			$error = $this->lang->get("AddressError", "Address is empty");
		}
		elseif (strlen($zipcode) < 1 || strlen($zipcode) > 50)
		{
			$error = $this->lang->get("ZipCodeError", "Zip Code is empty");
		}
		elseif ($city == 0)
		{
			$error = $this->lang->get("CityError", "City is empty");
		}
		elseif (strlen($paymentRegNum) < 1 || strlen($paymentRegNum) > 100)
		{
			$error = $this->lang->get("PaymentRegistrationError", "Registration number is empty");
		}
		elseif (strlen($paymentAccountNum) < 1 || strlen($paymentAccountNum) > 100)
		{
			$error = $this->lang->get("AccountNumberError", "Account number is empty");
		}
		elseif (Users::findFirst([["phone" => $phone, "is_deleted" => 0]]))
		{
			$error = $this->lang->get("UserExists", "User exists");
		}
		else
		{
			$id = Users::getNewId();
			$userInsert = [
				"id"							=> Users::getNewId(),
				"firstname"						=> $firstname,
				"lastname"						=> $lastname,
				"email"							=> $email,
				"ssn"							=> $ssn,
				"phone"							=> $phone,
				"password"						=> $this->lib->generatePassword($password),
				"gender"						=> $gender,
				"address"						=> $address,
				"languages"						=> $languages,
				"zipcode"						=> $zipcode,
				"city"							=> (int)$city,
				"payment_registration_number"	=> $paymentRegNum,
				"payment_account_number"		=> $paymentAccountNum,
				"is_deleted"					=> 0,
				"type"							=> 'employee',
				"created_at"					=> $this->mymongo->getDate()
			];
			Users::insert($userInsert);

			$data = Users::getById($id);
			if($data)
			{
				list($token, $hash) = $this->auth->createToken($this->request, $data);
				$success = $this->lang->get("AddedSuccessfully", "Added successfully");

				$response = [
					"status"		=> "success",
					"description"	=> $success,
					"token"			=> $token,
					"hash"			=> $hash,
					"data"			=> $this->auth->filterData($data, $this->lang),
				];
			}
			else
			{
				$error = $this->lang->get("TechnicalError", "Technical error occurred. Please, try agian");
			}
		}

		if($error)
		{
			$response = array(
				"status" 		=> "error",
				"error_code"	=> 1101,
				"description" 	=> $error,
			);
		}
		echo json_encode($response, true);
		exit();
	}

}
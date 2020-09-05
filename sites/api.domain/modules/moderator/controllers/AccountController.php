<?php
namespace Controllers;

use Lib\MyMongo;
use Models\Countries;
use Models\Tokens;
use Models\Users;
use Models\Cache;

class AccountController extends \Phalcon\Mvc\Controller
{
	public function infoAction()
	{
		$response = array(
			"status" 		=> "success",
			"description" 	=> "",
			"data"			=> $this->auth->filterData($this->auth->getData(), $this->lang),
		);
		echo json_encode($response);
		exit();
	}

	public function updatecitizenAction()
	{
		$error 		= false;

		$firstname 	= trim($this->request->get("firstname"));
		$lastname 	= trim($this->request->get("lastname"));
		$email 		= trim($this->request->get("email"));
		$ssn 		= trim($this->request->get("ssn"));
		//$phone 		= trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
		$place 		= trim($this->request->get("place"));

		$address 	= trim($this->request->get("address"));
		$zipcode 	= trim($this->request->get("zipcode"));
		$city 		= (int)$this->request->get("city");

		if(Cache::is_brute_force("infocUp-".$this->request->getServer("REMOTE_ADDR"), ["minute"	=> 100, "hour" => 500, "day" => 9000]))
		{
			$error = $this->lang->get("AttemptReached", "You attempted many times. Please wait a while and try again");
		}
		elseif ($this->auth->getData()->type !== "citizen")
		{
			$error = $this->lang->get("YouAreNotCiziten", "You are not citizen");
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
		//elseif (strlen($phone) < 1 || strlen($phone) > 50 || !is_numeric($phone))
		//{
		//	$error = $this->lang->get("PhoneError", "Phone is wrong");
		//}
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
		else
		{
			$update = [
				"firstname"		=> $firstname,
				"lastname"		=> $lastname,
				"email"			=> $email,
				"ssn"			=> $ssn,
				//"phone"			=> $phone,
				"place"			=> $place,
				"address"		=> $address,
				"zipcode"		=> $zipcode,
				"city"			=> $city,
				"updated_at"	=> $this->mymongo->getDate()
			];

			Users::update(["id" => (int)$this->auth->getData()->id], $update);

			$success = $this->lang->get("UpdatedSuccessfully");

			$data = Users::getById($this->auth->getData()->id);

			$response = [
				"status"		=> "success",
				"description"	=> $success,
				"data"			=> $this->auth->filterData($data, $this->lang),
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1021,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function updateemployeeAction()
	{
		$error 		= false;

		$firstname 		= trim($this->request->get("firstname"));
		$lastname 		= trim($this->request->get("lastname"));
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
		elseif ($this->auth->getData()->type !== "employee")
		{
			$error = $this->lang->get("YouAreNotEmployee", "You are not employee");
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
		else
		{
			$update = [
				"firstname"						=> $firstname,
				"lastname"						=> $lastname,
				"email"							=> $email,
				"ssn"							=> $ssn,
				"gender"						=> $gender,
				"address"						=> $address,
				"languages"						=> $languages,
				"zipcode"						=> $zipcode,
				"city"							=> (int)$city,
				"payment_registration_number"	=> $paymentRegNum,
				"payment_account_number"		=> $paymentAccountNum,
				"updated_at"					=> $this->mymongo->getDate()
			];

			Users::update(["id" => (int)$this->auth->getData()->id], $update);

			$success = $this->lang->get("UpdatedSuccessfully");

			$data = Users::getById($this->auth->getData()->id);

			$response = [
				"status"		=> "success",
				"description"	=> $success,
				"data"			=> $this->auth->filterData($data, $this->lang),
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1031,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function logoutAction(){
		$response = [
			"status"		=> "success",
			"description"	=> "",
		];
		echo json_encode($response, true);
		exit();
	}
}
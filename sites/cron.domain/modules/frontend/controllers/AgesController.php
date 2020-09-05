<?php
namespace Controllers;

use Models\MailboxLogs;
use Models\Offers;
use Models\Vacancy;

class AgesController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$data = Vacancy::find([]);


		foreach($data as $value){
			$bd = $value->birthdate;
			$bd = substr($bd, 6, 4)."-".substr($bd, 3, 2)."-".substr($bd, 0, 2);
			$timeStamp = strtotime($bd);
			if($timeStamp > 0 && strlen($value->birthdate) > 6){
				$elapse = $this->lib->findAgeByDate($bd);

				if($elapse !== $value->age)
				{
					echo $value->birthdate." - ".$elapse."<hr/>";
					/**/
					Vacancy::update(
						[
							"_id"	=> $value->_id,
						],
						[
							"age"	=> $elapse
						]
					);
					/**/
				}

			}
		}
		exit;
	}
}
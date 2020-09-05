<?php
namespace Controllers;

use Models\Activities;
use Models\Cases;

class BudgetController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$or = [];
		if((int)date("m-d") == "01-01")
		{
			$or[] = [
				"activity_budget_type"	=> "1_year",
				"last_budget_date"		=> [
					'$lt'	=> Cases::getDate(time() - 5*24*3600)
				]
			];
		}

		if((int)date("d") == 1)
		{
			$or[] = [
				"activity_budget_type"=> "1_month",
				"last_budget_date"	=> [
					'$lt'	=> Cases::getDate(time() - 5*24*3600)
				]
			];
		}

		$or[] = [
			"last_budget_date"	=> null
		];

		$bind = [
			//"id"			=> 54,
			"is_deleted" 	=> 0,
			'$or'			=> $or
		];


		echo "Week day: ".date("N").", Month day: ".date("m-d")."<br/>";

		$cases = Cases::find([
			$bind,
			"limit" => 100,
		]);

		echo "Cases:<br/>";

		foreach($cases as $case)
		{
			echo "Case: ".$case->id."<br/>";

			if($case->activity_budget_type == "1_month")
			{
				$year 	= (int)date("Y", time());
				$month 	= (int)date("m", time())+1;
				if($month > 12)
				{
					$year += 1;
					$month -= 12;
				}

				$month 				= $month < 10 ? "0".$month: "".$month;
				$nextTime 			= strtotime($year."-".$month."-01 00:00:00");
				$elapseSeconds 		= $nextTime - time();
				$fullSeconds 		= $nextTime - strtotime(date("Y-m-01 00:00:00"));
			}
			else //if($case->activity_budget_type == "1_year")
			{
				$year 				= (int)date("Y", time())+1;
				$nextTime 			= strtotime($year."-01-01 00:00:00");
				$elapseSeconds 		= $nextTime - time();
				$fullSeconds 		= $nextTime - strtotime(date("Y-01-01 00:00:00"));

				$case->activity_budget_type = "1_year";
			}

			$odd				= round($elapseSeconds / $fullSeconds, 2);

			$currentBalance = (double)$case->balance - (double)$case->amount_spent;
			if($elapseSeconds > 24*3600){
				$nextBudget = (double)$currentBalance + (double)$case->activity_budget*(float)$odd;
			}else{
				$nextBudget = (double)$currentBalance + (double)$case->activity_budget;
			}
			if((double)$case->activity_budget_max > 0 && $nextBudget > (double)$case->activity_budget_max)
				$nextBudget = (double)$case->activity_budget_max;


			echo "Type: ".$case->activity_budget_type."<br/>";
			echo "Odd: ".(float)$odd."<br/>";
			echo "Budget: ".(double)$case->activity_budget."<br/>";echo "Next budget: ".$nextBudget."<br/>";

			Cases::update(
				[
					"_id" => $case->_id
				],
				[
					"balance"				=> (double)$nextBudget,
					"amount_spent"			=> 0,
					"last_budget_date"  	=> $this->mymongo->getDate(),
				]
			);


			$userInsert = [
				"title"						=> "",
				"creator_id"				=> -1,
				"date"						=> $this->mymongo->getDate(),
				"case"						=> (int)$case->id,
				"amount"					=> (double)($nextBudget - $currentBalance),
				"action"					=> "plus",
				"budget_type"				=> $case->activity_budget_type,
				"status"					=> 1,
				"is_deleted"				=> 0,
				"last"						=> [
					"balance"			=> $case->balance,
					"amount_spent"		=> $case->amount_spent,
					"last_budget_date"	=> $case->last_budget_date,
				],
				"created_at"				=> $this->mymongo->getDate()
			];

			Activities::insert($userInsert);


			echo "<hr/>";
		}

		exit;
	}
}
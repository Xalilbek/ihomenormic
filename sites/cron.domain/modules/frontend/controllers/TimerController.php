<?php
namespace Controllers;

use Models\Activities;
use Models\Cases;
use Models\LogsTimer;

class TimerController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$or = [];
		if((int)date("N") == 1)
		{
			$or[] = [
				"meeting_duration_type"	=> "week",
				"last_timer_date"		=> [
					'$lt'	=> Cases::getDate(time() - 5*24*3600)
				]
			];
		}

		if((int)date("d") == 1)
		{
			$or[] = [
				"meeting_duration_type"=> "month",
				"last_timer_date"	=> [
					'$lt'	=> Cases::getDate(time() - 5*24*3600)
				]
			];
		}

		$or[] = [
			"last_timer_date"	=> null
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

			if($case->meeting_duration_type == "week")
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
				$fullSeconds 		= 7 * 24 * 3600;
			}
			else
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

				$case->meeting_duration_type = "month";
			}

			$odd				= round($elapseSeconds / $fullSeconds, 2);

			echo "Type: ".$case->meeting_duration_type."<br/>";
			echo "Odd: ".(float)$odd."<br/>";
			echo "Time: ".(double)$case->timer_spent."<br/>";

			/**/
			Cases::update(
				[
					"_id" => $case->_id
				],
				[
					"timer_spent"			=> 0,
					"last_timer_date"  		=> $this->mymongo->getDate(),
				]
			);
			/**/


			$Insert = [
				"date"						=> $this->mymongo->getDate(),
				"case"						=> (int)$case->id,
				"meeting_duration_type"		=> $case->meeting_duration_type,
				"last"						=> [
					"timer_spent"			=> $case->timer_spent,
					"last_timer_date"	=> $case->last_timer_date,
				],
				"created_at"				=> $this->mymongo->getDate()
			];

			LogsTimer::insert($Insert);


			echo "<hr/>";
		}

		exit;
	}
}
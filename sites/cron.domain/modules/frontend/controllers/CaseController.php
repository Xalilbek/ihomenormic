<?php
namespace Controllers;

use Models\CasePlans;
use Models\CaseReports;
use Models\Cases;
use Models\Partner;
use Models\Users;

class CaseController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		//Cases::update(["id" => ['$gt' => 0]], ["next_report_date" => null]);

		$cases = Cases::find([
			[
				'$or' => [
					[
						"next_report_date"	=> [
							'$lt' => $this->mymongo->getDate()
						],
					],
					[
						"next_report_date"	=> null
					]
				]
				//"is_deleted"	=> ['$ne'	=> 1]
			]
		]);

		foreach($cases as $case)
		{
			echo "Case: ".$case->id."<br/>";

			$content = "";

			$nextReportDate = Cases::getNextMeetingDate($case->report_interval, $this->mymongo->toSeconds($case->next_report_date));

			echo "Next report date: ".$nextReportDate."<br/>";

			$casePlans = CasePlans::find([
				[
					"case_id"	=> (int)$case->id,
					"is_deleted"	=> [
						'$ne'	=> 1
					]
				]
			]);


			$focuesArea 		= $this->parameters->getById($this->lang, "focusareas", (int)@$case->focus_area[0]);
			$citizen 			= Users::getById((int)@$case->citizen[0]);
			$contactPerson 		= Users::getById((int)@$case->contact_person[0]);
			$partner 			= Partner::getById((int)@$case->partner);

			$content .= "- ". $focuesArea["title"].PHP_EOL;
			$content .= "- ". @$citizen->firstname.' '.@$citizen->lastname.PHP_EOL;
			$content .= "- ". $case->report_interval.PHP_EOL;
			$content .= "- ". date("Y-m-d").PHP_EOL;
			$content .= "- ". @$contactPerson->firstname.' '.@$contactPerson->lastname.PHP_EOL;
			$content .= "- ". $partner->commune.PHP_EOL;

			$content .= PHP_EOL;


			foreach($casePlans as$key => $plan)
			{
				echo "-- Plan: ".$plan->_id."<br/>";
				$questions = $this->parameters->getListByIds($this->lang, "questions", $plan->questions, true);

				$goal = $this->parameters->getById($this->lang, "goals", $plan->goal);

				$content .= "".($key+1).") ".$goal["title"].PHP_EOL;

				foreach($plan->questions as $questionId)
				{
					$quesIns = [
						"case_id"			=> $case->id,
						"plan_id"			=> (string)$plan->_id,
						"report_id"			=> (string)$reportInsId,
						"question_id"		=> (int)$questionId,
						"answer"			=> null,
						"is_deleted"		=> 0,
						"created_at"		=> $this->mymongo->getDate(),
					];

					$content .= "   - ".$questions[$questionId]["title"].PHP_EOL;
					$content .= "   ".PHP_EOL;

					//CaseQuestions::insert($quesIns);
					echo "---- ".$questionId."<br/>";
				}
			}

			$reportIns = [
				"id"			=> CaseReports::getNewId(),
				"case_id"		=> (int)$case->id,
				"status"		=> 4,
				"report_date"	=> date("Y-m-d"),
				"content"		=> $content,
				"is_deleted"	=> 0,
				"updated_at"	=> $this->mymongo->getDate(),
				"created_at"	=> $this->mymongo->getDate(),
			];
			$reportInsId = CaseReports::insert($reportIns);


			Cases::update(
				[
					"_id" => $case->_id
				],
				[
					"next_report_date" => $this->mymongo->getDate(strtotime($nextReportDate." 00:00:00")),
					"last_report_date" => $this->mymongo->getDate(strtotime(date("Y-m-d")." 00:00:00"))
				]
			);

			echo "<hr/>";
		}

		exit;
	}
}
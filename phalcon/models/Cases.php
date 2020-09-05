<?php
namespace Models;

use Lib\MainDB;

class Cases extends MainDB
{
    public static function getSource(){
        return "cases";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getNewId()
    {
        $last = self::findFirst(["sort" => ["id" => -1]]);
        if ($last) {
            $id = $last->id + 1;
        } else {
            $id = 1;
        }
        return $id;
    }

    public static function getReportTypes($lang)
    {
        return [
            1 => [
                "title"    => $lang->get("EvaluationReport", "Evaluation report"),
            ],
            2 => [
                "title"    => $lang->get("StatusReport", "Status report"),
            ],
            3 => [
                "title"    => $lang->get("Letter", "Letter"),
            ],
        ];
    }

    public static function getNextMeetingDate($report_interval, $startTime=false)
    {
        if(!$startTime)
            $startTime = time();
        $nextReportDate = false;
        switch($report_interval)
        {
            default:
                $time = $startTime + 14*24*3600;
                $nextReportDate = date("Y-m-d", $time);
                break;
            case '1_month':
                /**
                $year 	= (int)date("Y", $startTime);
                $month 	= (int)date("m", $startTime)+1;
                if($month > 12)
                {
                    $year += 1;
                    $month -= 12;
                }
                $month = $month < 10 ? "0".$month: "".$month;
                $nextReportDate = $year."-".$month."-01";
                 */

                $time = $startTime + 30*24*3600;
                $nextReportDate = date("Y-m-d", $time);
                break;

            case '3_month':
                /**
                $year 	= (int)date("Y", $startTime);
                $month 	= (int)date("m", $startTime)+3;
                if($month > 12)
                {
                    $year += 1;
                    $month -= 12;
                }
                $month = $month < 10 ? "0".$month: "".$month;
                $nextReportDate = $year."-".$month."-01"; */

                $time = $startTime + 90*24*3600;
                $nextReportDate = date("Y-m-d", $time);
                break;

            case '6_month':
                /**
                $year 	= (int)date("Y", $startTime);
                $month 	= (int)date("m", $startTime)+6;
                if($month > 12)
                {
                    $year += 1;
                    $month -= 12;
                }
                $month = $month < 10 ? "0".$month: "".$month;
                $nextReportDate = $year."-".$month."-01";
                */
                $time = $startTime + 180*24*3600;
                $nextReportDate = date("Y-m-d", $time);
                break;
        }

        return $nextReportDate;
    }

    public static function getNextBudgetDate($interval, $startTime=false)
    {
        if(!$startTime)
            $startTime = time();
        $nextDate = false;
        switch($interval)
        {
            default:
                $time = $startTime + 7*24*3600;
                $nextDate = date("Y-m-d", $time);
                break;
            case '1_month':
                $year 	= (int)date("Y", $startTime);
                $month 	= (int)date("m", $startTime)+1;
                if($month > 12)
                {
                    $year += 1;
                    $month -= 12;
                }
                $month = $month < 10 ? "0".$month: "".$month;
                $nextDate = $year."-".$month."-01";
                break;
        }

        return $nextDate;
    }



    public static function getParams($value, $lang, $lib)
    {
        if(!$value->start_employee_date)
            $value->start_employee_date = $value->start_date;
        $durationSecs = Cases::getUnixtime() - Cases::toSeconds($value->start_employee_date);

        $duration = $lib->durationToStr($durationSecs, $lang);


        $timerMonthly = $value->meeting_duration;
        if($value->meeting_duration_type == "week")
            $timerMonthly = round($value->meeting_duration * 52 / 12, 0);
        $spentHours = $value->timer_spent/3600;

        $monthlySpentHours = TimeRecords::sum("elapse", [
            'time_start' => [
                '$gte' 	=> TimeRecords::getDate(strtotime(date("Y-m-01 00:00:00"))),
            ]
        ]);
        $monthlySpentHours = round($monthlySpentHours/3600, 2);

        if($value->balance !== 0)
            $amountCirclePercent = round(abs($value->amount_spent/$value->balance) * 100);
        if($amountCirclePercent > 0){}else{$amountCirclePercent=0;};
        $amountCirclePercent = 0;

        if($timerMonthly !== 0)
            $timeCirclePercent = round(abs($monthlySpentHours/$timerMonthly) * 100);
        if($timeCirclePercent > 0){}else{$timeCirclePercent=0;};
        $timeCirclePercent = 0;



        $startDate = Cases::dateFormat($value->start_date, "d.m.Y");
        $startEmployeeDate = Cases::dateFormat($value->start_date_for_employee, "d.m.Y");


        return [
            "duration"              => $duration,
            "amountCirclePercent"   => $amountCirclePercent,
            "amountColor"           => self::getCircleColor($amountCirclePercent),
            "spentHours"            => $spentHours,
            "timerMonthly"          => $timerMonthly,
            "timeCirclePercent"     => $timeCirclePercent,
            "timeColor"             => self::getCircleColor($timeCirclePercent),
            "startDate"             => $startDate,
            "startEmployeeDate"     => $startEmployeeDate,
        ];
    }

    public static function getCircleColor($percent)
    {
        $color = "#4caf50";
        if($percent < 25){
            $color = "#de3030";
        }elseif($percent < 50){
            $color = "#e0b01d";
        }elseif($percent < 75){
            $color = "#e0b01d";
        }
        return $color;
    }
}
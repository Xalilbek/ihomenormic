<?php
namespace Models;

use Lib\MainDB;

class CasePlanQuestions extends  MainDB
{
    public static function getSource()
    {
        return "case_plan_questions";
    }

    public static function getIntervals($lang)
    {
        return [
            "after_session" => [
                "title" => $lang->get("AfterSession", "After session")
            ],
            "week" => [
                "title" => $lang->get("1Week", "1 Week")
            ],
            "14_days" => [
                "title" => $lang->get("Every14days")
            ],
            "1_month" => [
                "title" => $lang->get("PerMonth")
            ],
            "3_month" => [
                "title" => $lang->get("onceEvery3Months")
            ],
            "6_month" => [
                "title" => $lang->get("onceEvery6Months")
            ],
        ];
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
}
<?php
namespace Models;

use Lib\MainDB;

class Vacancy extends MainDB
{
    public static function getSource(){
        return "jobdatabase";
    }
    
    public static function getSwitches($lang)
    {
        return [
            ["name" => "ADHD",              "title" => $lang->get("ADHD")],
            ["name" => "autism",            "title" => $lang->get("autism")],
            ["name" => "borderline",        "title" => $lang->get("borderline")],
            ["name" => "PTSD",              "title" => $lang->get("PTSD")],
            ["name" => "OCD",               "title" => $lang->get("OCD")],
            ["name" => "criminals",         "title" => $lang->get("criminals")],
            ["name" => "drug_and_alcohol_abuse", "title" => $lang->get("drug_and_alcohol_abuse")],
            ["name" => "physically_vulnerable", "title" => $lang->get("physically_vulnerable")],
            ["name" => "handicap",          "title" => $lang->get("handicap")],
            ["name" => "diabetes",          "title" => $lang->get("Diabetes")],
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
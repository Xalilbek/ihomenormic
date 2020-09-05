<?php
namespace Models;

use Lib\MainDB;

class Activities extends MainDB
{
    public static function getSource(){
        return "case_logs";
    }

    public static $collection = "activities";

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }
}
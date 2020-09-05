<?php
namespace Models;

use Lib\MainDB;

class Employee extends MainDB
{
    public static function getSource(){
        return "employees";
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
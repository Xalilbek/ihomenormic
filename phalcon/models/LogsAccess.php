<?php
namespace Models;

use Lib\MainDB;

class LogsAccess extends MainDB
{
    public static function getSource(){
        return "logs_access";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }
}
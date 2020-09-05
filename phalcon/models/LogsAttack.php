<?php
namespace Models;

use Lib\MainDB;

class LogsAttack extends MainDB
{
    public static function getSource(){
        return "logs_attack";
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
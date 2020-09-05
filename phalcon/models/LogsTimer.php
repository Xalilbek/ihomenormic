<?php
namespace Models;

use Lib\MainDB;

class LogsTimer extends MainDB
{
    public static function getSource(){
        return "logs_timer";
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
<?php
namespace Models;

use Lib\MainDB;

class LogsPenalty extends MainDB
{
    public static function getSource(){
        return "logs_penalty";
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
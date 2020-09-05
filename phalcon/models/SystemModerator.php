<?php
namespace Models;

use Lib\MainDB;

class SystemModerator extends MainDB
{
    public static function getSource(){
        return "system_moderator";
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
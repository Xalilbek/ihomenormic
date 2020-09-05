<?php
namespace Models;

use Lib\MainDB;

class Tokens extends MainDB
{
    public static function getSource(){
        return "user_tokens";
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
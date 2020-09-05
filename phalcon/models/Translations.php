<?php
namespace Models;

use Lib\MainDB;

class Translations extends MainDB
{
    public static function getSource(){
        return "translations";
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
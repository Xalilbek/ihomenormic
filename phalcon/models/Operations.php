<?php
namespace Models;

use Lib\MainDB;

class Operations extends MainDB
{
    public static function getSource()
    {
        return "operations";
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
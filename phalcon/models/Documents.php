<?php
namespace Models;

use Lib\MainDB;

class Documents extends MainDB
{
    public static function getSource(){
        return "documents";
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
<?php
namespace Models;

use Lib\MainDB;

class TempFiles extends MainDB
{
    public static function getSource(){
        return "files_temp";
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
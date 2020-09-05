<?php
namespace Models;

use Lib\MainDB;

class Parameters extends MainDB
{
    public static function getSource(){
        return self::$collection;
    }

    public static $collection = "";

    public static function setCollection($collection)
    {
        self::$collection = $collection;
        return true;
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getNewId()
    {
        $last = self::findFirst(["sort" => ["id" => -1]]);
        if ($last) {
            $id = $last->id + 1;
        } else {
            $id = 1;
        }
        return $id;
    }
}
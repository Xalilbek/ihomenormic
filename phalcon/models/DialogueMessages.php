<?php
namespace Models;

use Lib\MainDB;

class DialogueMessages extends MainDB
{
    public static function getSource(){
        return "dialogue_messages";
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
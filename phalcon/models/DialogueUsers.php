<?php
namespace Models;

use Lib\MainDB;

class DialogueUsers extends MainDB
{
    public static function getSource(){
        return "dialogue_users";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function createUserForDialogue($dialogueId, $userId)
    {

        $insert = [
            "dialogue"          => (string)$dialogueId,
            "user_id"           => (int)$userId,
            "status"            => (int)0,
            "read_at"           => self::getDate(1),
            "deleted_at"        => self::getDate(1),
            "notified_at"       => self::getDate(1),
            "updated_at"        => self::getDate(),
            "created_at"        => self::getDate(),
        ];
        return self::insert($insert);
    }
}
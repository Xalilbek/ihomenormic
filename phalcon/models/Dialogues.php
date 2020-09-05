<?php
namespace Models;

use Lib\MainDB;

class Dialogues extends MainDB
{
    public static function getSource(){
        return "dialogues";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getDialogue($users)
    {
        foreach($users as $userId)
            $userIds[] = (int)$userId;

        $dialogue = false;
        if(count($userIds) == 2)
            $dialogue = self::findFirst([
                [
                    "users" => [
                        '$all'   => $userIds
                    ],
                    "type"  => "dialogue"
                ]
            ]);
        if($dialogue)
        {
            return $dialogue;
        }
        else
        {
            $dialogueId = self::createDialogue($userIds);
            return self::findById($dialogueId);
        }
    }

    public static function createDialogue($users)
    {
        $userIds = [];
        foreach($users as $userId)
            $userIds[] = (int)$userId;
        $insert = [
            "creator_id"    => $userIds[0],
            "users"         => $userIds,
            "type"          => count($userIds) == 2 ? "dialogue": "group",
            "updated_at"    => self::getDate(),
            "created_at"    => self::getDate(),
        ];
        $dialogueId = self::insert($insert);

        foreach($userIds as $userId)
            DialogueUsers::createUserForDialogue($dialogueId, $userId);

        return $dialogueId;
    }
}
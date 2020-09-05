<?php
namespace Models;

use Lib\MainDB;

class ContractTemplates extends  MainDB
{
    public static function getSource()
    {
        return "contract_templates";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getByPhone($phone)
    {
        return self::findFirst([
            [
                "phone"         => $phone,
                "is_deleted"    => 0
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
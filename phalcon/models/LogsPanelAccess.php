<?php
namespace Models;

use Lib\MainDB;

class LogsPanelAccess extends MainDB
{
    public static function getSource(){
        return "logs_panel_access";
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
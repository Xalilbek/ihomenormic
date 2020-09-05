<?php
namespace Models;

use Lib\MainDB;
use Multiple\Models\Cache;

class Countries extends MainDB
{
    public static function getSource(){
        return "countries";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
            ]
        ]);
    }

    public static function getByList(){
        $array = Cache::get("av_countries_"._LANG_);
        if(!$array){
            $data = self::find([]);
            $array = [];
            foreach($data as $value)
                $array[$value->id] = [
                    "name" => (@$value->title["en"]) ? $value->title["en"]: $value->title[$value->default_lang]
                ];

            Cache::set("av_countries_"._LANG_, $array, 24*3600);
        }
        return $array;
    }

    public static function getBySlug($slug){
        return
            self::findFirst(["slug" => strtolower(strtolower($slug))]);
    }

    public static function getByDialCode($code){
        return
            self::findFirst(["dial_codes" => (int)$code]);
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
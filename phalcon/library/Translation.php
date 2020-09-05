<?php
namespace Lib;

use Models\Translations;
use Models\Cache;

class Translation
{
    public $data;

    public $lang = "en";

    public $templateId = 1;

    public $cacheSeconds = 10;

    public $langs = ["en","da"];

    public $testLangs = ["en","da"];

    public $langData = [
        "en" => ["name" => "English"],
        "da" => ["name" => "Danish"],
        "ru" => ["name" => "Русский язык"],
        "ua" => ["name" => "Українська мова"],
        "tr" => ["name" => "Türk dili"],
        "az" => ["name" => "Azərbaycan dili"],
        "de" => ["name" => "Deutsch"],
    ];

    public $templates = [
        1   => ["name"  => "web"],
        2   => ["name"  => "api"],
        3   => ["name"  => "panel"],
        4   => ["name"  => "app"],
        //5   => ["name"  => "admin"],
        //6   => ["name"  => "partner"],
    ];

    public function init($templateId, $lang=false)
    {
        if(strlen($lang) > 0 && $templateId !== 1)
        {
            $this->lang = $lang;
        }
        else if(strlen(@$_POST["_setlang"]) > 1)
        {
            $this->lang = @$_POST["_setlang"];
        }
        else if(strlen(@$_GET["_setlang"]) > 1)
        {
            $this->lang = @$_GET["_setlang"];
            if(@$_COOKIE['_setlang'] !== $this->lang){
                setcookie("_setlang", $this->lang, time()+365*24*3600, "/");
            }
        }
        else if(strlen(@$_COOKIE['_setlang']) > 1)
        {
            $this->lang = @$_COOKIE['_setlang'];
        }
        else
        {
            $this->lang = _MAIN_LANG_;
        }
        define("_LANG_", $this->lang);
        $this->setLang($this->lang);


        $this->templateId   = $templateId;
        if(!$this->data)
        {
            $this->getTranslationsBySiteID($this->templateId, $this->lang);
        }
        return true;
    }

    public function setLang($lang)
    {
        return $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function getTranslationsBySiteID($templateId, $lang)
    {
        $lang       = strtolower($lang);
        $data       = $this->getFromCache();
        if(!$data)
        {
            $data   = [];
            $query  = Translations::find(["template_id"   => (int)$templateId]);
            if(count($query) > 0)
            {
                foreach($query as $value)
                {

                    $data[$value->key] = (strlen($value->translations->$lang) > 0) ? $value->translations->$lang: $value->translations->en;
                }
            }
            $this->saveCache($data);
        }

        return $this->data = $data;
    }

    public function get($key, $original=false)
    {
        $translation = trim($key);
        if($this->data !== false)
        {
            if(@$this->data[$key])
            {
                $translation = @$this->data[$key];
            }
            elseif(strlen($key) > 0)
            {
                $this->add($key, $original);
                $translation = ($original) ? $original: $key;
                $this->data[$key] = $translation;
            }
        }
        return $translation;
    }

    public function add($key, $original=false)
    {
        $key = trim($key);
        if(strlen($key) > 0 && !$data = Translations::findFirst([["key" => trim($key)]]))
        {
            $insert = [
                "template_id"   => [$this->templateId],
                "key"           => $key,
                "translations"  => [
                                        "da"    => ($original) ? $original: $key
                                    ],
                "created_at"    => MyMongo::getDate()
            ];

            Translations::insert($insert);

            $this->flushCache();
        }
        else
        {
            if(!in_array($this->templateId, $data->template_id))
            {
                if(is_array($data->template_id))
                {
                    $data->template_id[]     = $this->templateId;
                }
                else
                {
                    $data->template_id     = [$this->templateId, (int)$data->template_id];
                }
                Translations::update(["key" => trim($key)], ["template_id"   => $data->template_id]);
            }
        }
        return true;
    }

    public function getFromCache()
    {
        return Cache::get($this->getCacheKey());
    }

    public function getCacheKey()
    {
        return md5("translations-".$this->lang."-".$this->templateId);
    }

    public function flushCache()
    {
        return Cache::set($this->getCacheKey(), false, time());
    }

    public function saveCache($data)
    {
        return Cache::set($this->getCacheKey(), $data, time() + $this->cacheSeconds);
    }
}
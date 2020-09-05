<?php
namespace Lib;

use Models\Cache;

class Parameters
{
    public $table = false;

    public $title = "";

    public function getParametersList($lang)
    {
        return [
            "focusareas"                => ["collection" => "parameters_focus_areas", "title" => $lang->get("FocusAreas", "Focus areas")],
            "focustypes"                => ["collection" => "parameters_focus_types", "title" => $lang->get("FocusTypes", "Focus types")],
            "system_languages"          => ["title" => $lang->get("SystemLanguages", "System languages")],
            "languages"                 => ["title" => $lang->get("Languages")],
            "questions"                 => ["title" => $lang->get("Surveys")],
            "goals"                     => ["title" => $lang->get("Goals")],
            "cities"                    => ["title" => $lang->get("Cities")],
            "case_categories"           => ["title" => $lang->get("CaseCategories")],
            "todo_categories"           => ["title" => $lang->get("TodoCategories")],
            "colors"                    => ["title" => $lang->get("Colors")],
            "activity_statuses"         => ["title" => $lang->get("ActivityStatuses", "Activity statuses")],
            "trading_plan_statuses"     => ["title" => $lang->get("TradingPlanStatuses", "Trading plan statuses")],
            "report_statuses"           => ["title" => $lang->get("ReportStatuses")],
            "document_categories"       => ["title" => $lang->get("DocumentCategories")],
            "vacancy_folders"           => ["title" => $lang->get("VacancyFolders")],
            "calendar_statuses"         => ["title" => $lang->get("CalendarStatuses", "Calendar Statuses")],
            "vacancy_cities"            => ["title" => $lang->get("VacancyCities", "Vacancy Cities")],
            "vacancy_languages"         => ["title" => $lang->get("VacancyLanguages", "Vacancy Languages")],
            "offer_statuses"            => ["title" => $lang->get("OfferStatuses", "Offer Statuses")],
            "session_types"             => ["title" => $lang->get("SessionTypes", "Session Types")],
            "employee_types"            => ["title" => $lang->get("EmployeeGroups", "Employee Groups")],
         ];
    }

    public function setCollection($param, $lang=false)
    {
        $this->table = new \Models\Parameters();

        if($param == "focus_areas")
            $param = "focusareas";

        $paramData = @$this->getParametersList($lang)[$param];
        if($paramData)
        {
            $collection = $paramData["collection"];
            if(!$collection)
                $collection = "parameters_".$param;
            $this->table->setCollection($collection);
            $this->title = $paramData["title"];
        }
        else
        {
            return false;
            $this->table->setCollection("parameters_focus_areas");
            $this->title = $lang->get("FocusAreas", "Focus areas");
        }

        return $this->table;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getList($lang, $param, $filter =[], $withKey = false)
    {
        $this->setCollection($param, $lang);

        $queryFilter    = ["active" => 1, "is_deleted" => ['$ne' => 1]];
        if(count($filter) > 0)
            $queryFilter    = array_merge($queryFilter, $filter);
        $sort           = ["id" => 1];
        if($param == "cities")
            $sort = ["titles.".$lang->getLang() => 1];


        $query          = $this->table->find([$queryFilter, "sort" => $sort]);
        $data           = [];
        foreach($query as $value)
        {
            $filteredData = $this->filterData($lang, $param, $value);

            if($withKey)
            {
                $data[(int)$value->id] = $filteredData;
            }
            else
            {
                $data[] = $filteredData;
            }
        }
        return $data;
    }

    public function getListByIds($lang, $param, $ids=[], $withKey = false)
    {
        $this->setCollection($param, $lang);

        $query  = $this->table->find([["id" => ['$in' => $ids], "active" => 1, "is_deleted" => ['$ne' => 1]], "sort" => ["id" => 1]]);
        $data   = [];
        foreach($query as $value)
        {
            $filteredData = $this->filterData($lang, $param, $value);

            if($withKey)
            {
                $data[(int)$value->id] = $filteredData;
            }
            else
            {
                $data[] = $filteredData;
            }
        }
        return $data;
    }

    public function getById($lang, $param, $id=0)
    {
        $this->setCollection($param, $lang);

        $value  = $this->table->findFirst([["id" => (int)$id, "active" => 1, "is_deleted" => ['$ne' => 1]], "sort" => ["id" => 1]]);
        $data   = false;
        if($value)
        {
            $data = $this->filterData($lang, $param, $value);
        }
        return $data;
    }

    public function filterData($lang, $param, $value)
    {
        $title = strlen(trim(@$value->titles->{$lang->getLang()})) > 0 ? $value->titles->{$lang->getLang()}: $value->titles->{$value->default_lang};
        if(strlen(trim($title)) < 1)
            foreach(@$value->titles as $vTitle)
                if(strlen(trim($vTitle)) > 0)
                    $title = trim(htmlspecialchars($vTitle));
        $filteredData = [
            "id"        => (int)$value->id,
            "title"     => $title,
        ];
        if($param == "todo_categories") $filteredData["color"] = "#f9f9f9";

        if(strlen($value->html_code) > 0) $filteredData["html_code"] = (string)$value->html_code;
        if(strlen($value->code) > 0) $filteredData["code"] = (string)$value->code;
        if(strlen($value->post_number) > 0) $filteredData["post_number"] = (string)$value->post_number;
        return $filteredData;
    }

    public function getFromCache()
    {
        return Cache::get($this->getCacheKey());
    }

    public function getCacheKey()
    {
        return md5("translations-".$lang."-".$this->templateId);
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
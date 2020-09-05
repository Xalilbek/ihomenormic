<?php

namespace Controllers;

use Lib\MyMongo;
use Models\City;
use Models\FocusAreas;
use Models\FocusTypes;
use Models\Goal;
use Models\Languages;
use Models\Questions;

class ParametersController extends \Phalcon\Mvc\Controller
{
    public static $table;
    public static $arr;
    public static $data;
    public function initialize(){
        $type = $this->request->get("type");
        if(strlen($type) < 1)
            $type = "colors";
        switch($type){
            default: self::$table = new FocusAreas(); break;
            case "focusareas": self::$table = new FocusAreas(); break;
            case "focustypes": self::$table = new FocusTypes(); break;
            case "languages": self::$table = new Languages(); break;
            case "questions": self::$table = new Questions(); break;
            case "goals": self::$table = new Goal(); break;
            case "cities": self::$table = new City(); break;
        }
        //self::$table = new MongoCountry();
        $cat_query  = self::$table->find([[], "sort" => ["id" => 1]]);

        $cat_arr    = [];
        $cat_data   = [];
        $lang_keys  = [];

        IF (count($cat_query) > 0)
        {
            foreach($cat_query as $value)
            {
                //if((int)$value->parent_id == 0)
                $cat_arr[(int)$value->parent_id][] = (int)$value->id;
                $cat_data[(int)$value->id] = [
                    "id"        => (int)$value->id,
                    "active"    => (int)@$value->active,
                    "title"     => strlen(trim(@$value->titles->{_LANG_})) > 0 ? $value->titles->{_LANG_}: $value->titles->{$value->default_lang},
                ];
            }
        }
        $this->view->setVar("type", $type);
        $this->view->setVar("cat_arr", $cat_arr);
        $this->view->setVar("cat_data", $cat_data);
    }

    public function addAction($id)
    {
        $error      = false;
        $success    = false;
        if ($this->request->get("submit"))
        {
            $added = false;
            foreach($this->request->get("name") as $lang => $value){
                $lang = strtolower($lang);
                $name = trim($value);
                if(in_array($lang, $this->lang->langs) && strlen($name) > 0){
                    $added = true;
                }
            }
            if(!$added)
                $error = AllFieldsAreEmpty;
            if(!$error) {
                $titles = [];
                foreach($this->request->get("name") as $lang => $value){
                    $lang = strtolower($lang);
                    $name = trim($value);
                    if(in_array($lang, $this->lang->langs) && strlen($name) > 0){
                        $titles[$lang] = $name;
                    }
                }
                $insert = [
                    "id"            => self::$table->getNewId(),
                    "parent_id"     => (int)$id,
                    "titles"        => $titles,
                    "active"        => 1,
                    "index"         => (int)$id,
                    "default_lang"  => _LANG_,
                    "slug"          => str_replace(" ", "_", strtolower(@$titles["en"])),
                    "created_at"    => MyMongo::getDate(),
                ];

                self::$table->insert($insert);
            }
            if (!$error)
                $success = AddedSuccessfully;
        }
        $this->view->setVar("id", $id);
        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->disable();
        $this->view->partial("parameters/add");
    }

    public function editAction($id)
    {
        //ini_set("display_errors", 1);
        $error = false;
        $success = false;
        $I      = self::$table->findFirst([["id" => (int)$id]]);
        $names  = [];
        if (!$I)
        {
            $error = NoCategory;
        }
        else if ($this->request->get("submit"))
        {
            $added = false;
            $titles = [];
            foreach($this->request->get("name") as $lang => $value){
                $lang = strtolower($lang);
                $name = trim($value);
                if(in_array($lang, $this->lang->langs) && strlen($name) > 0)
                {
                    $titles[$lang] = $name;
                    $added = true;
                }
            }
            if(!$added)
            {
                $error = AllFieldsAreEmpty;
            }
            else
            {
                $update = [
                    "titles"        => $titles,
                    "updated_at"    => MyMongo::getDate(),
                ];
                self::$table->update(["id" => (int)$id], $update);
                $I = self::$table->findFirst([["id" => (int)$id]]);
            }
            if (!$error)
                $success = RenamedSuccessfully;
        }
        foreach($I->titles as $key => $value){
            $names[$key] = $value;
        }
        $this->view->setVar("id", $id);
        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->setVar("names", $names);
        $this->view->disable();
        $this->view->partial("parameters/edit");
    }

    public function deleteAction($id)
    {
        $error = false;
        $success = false;
        if ($this->request->get("submit")){
            $data = self::$table->findFirst([["id" => (int)$id]]);
            if(!$data){
                $error = NoCategory;
            }else{
                if (!self::$table->delete(["id" => (int)$id]))
                    $error = UnknownError;
                if (!$error)
                    $success = DeletedSuccessfully;
            }
        }
        $this->view->setVar("id", $id);
        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->disable();
        $this->view->partial("parameters/delete");
    }

    public function setindexAction($cat_id)
    {
        $data = self::$table->findFirst([["id" => (int)$cat_id]]);
        if($data){
            $update = [
                "index"         => (int)$this->request->get("index_id"),
                "updated_at"    => MyMongo::getDate(),
            ];
            self::$table->update(["id" => (int)$cat_id], $update);
        }
        exit;
    }

    public function setvisibleAction($cat_id)
    {

        $data = self::$table->findFirst([["id" => (int)$cat_id]]);
        if($data){
            $update = [
                "active"        => (int)$this->request->get("status"),
                "updated_at"    => MyMongo::getDate(),
            ];
            self::$table->update(["id" => (int)$cat_id], $update);
        }
        exit;
    }

    public function indexAction()
    {


    }


}
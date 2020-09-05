<?php

namespace Controllers;

use Lib\Lib;
use Models\Translations;

class TranslationsController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
        $this->view->setVar("hasDatatable", true);

    }

	public function listAction()
	{
        $records = array();
        if ($this->request->get("customActionType") && $this->request->get("customActionType") == "group_action") {
            foreach($this->request->get("id") as $id){
                $error = false;
                $item = Translations::findById($id);
                $status = (int)$this->request->get("customActionName");
                $status = (in_array($status, [0,1,2])) ? $status: 2;
                $item->active = $status;
                $item->save();
            }
            if ($error){
                $records["customActionStatus"] = "ERROR"; // pass custom message(useful for getting status of group actions)
                $records["customActionMessage"] = $error; // pass custom message(useful for getting status of group actions)
            }else{
                $records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
                $records["customActionMessage"] = RequestDoneSuccessfully; // pass custom message(useful for getting status of group actions)
            }
        }
        $iDisplayLength = intval($this->request->get('length'));
        $iDisplayLength = $iDisplayLength < 0 ? $count : $iDisplayLength;
        $iDisplayStart = intval($this->request->get('start'));
        $sEcho = intval($this->request->get('draw'));

        $records["data"] = array();

        $order = $this->request->get("order");
        $order_column = $order[0]["column"];
        $order_sort = $order[0]["dir"];

        $binds = [];
        $where = [];
        $search = true;
        if ($search){
            if ($this->request->get("item_id") > 0){
                $binds["id"] = $this->request->get("item_id");
            }

            if ($this->request->get("date_from")){
                $binds["date_from"] = $this->request->get("date_from");
            }

            if ($this->request->get("date_to")){
                $binds["date_to"] = $this->request->get("date_to");
            }

            if ($this->request->get("key")){
                $binds["key"] = [
                    '$regex' => trim(($this->request->get("key"))),
                    '$options'  => 'i'
                ];
            }

            if (strlen($this->request->get("to_lang")) > 0){
                $lang = $this->request->get("to_lang") ;
            }else{
                $lang = "da";
            }

            if (strlen($this->request->get("from_lang")) > 0){
                $fromLang = $this->request->get("from_lang") ;
            }else{
                $fromLang = "en";
            }

            if ($this->request->get("keyword")){
                $binds["keyword"] = [
                    '$regex' => trim(strtolower($this->request->get("keyword"))),
                    '$options'  => 'i'
                ];
            }

            if ($this->request->get("from_original"))
                $binds["translations.".$fromLang] = [
                    '$regex' => trim($this->request->get("from_original")),
                    '$options'  => 'i'
                ];

            if ($this->request->get("to_original"))
                $binds["translations.".$lang] = [
                    '$regex' => trim($this->request->get("to_original")),
                    '$options'  => 'i'
                ];

            if (in_array($this->request->get("active"), ["0","1","2"]))
                $binds["active"] = (int)$this->request->get("active");

            if ($this->request->get("template_id") > 0)
                $binds["template_id"] = (int)$this->request->get("template_id");
        }else{
            $data = [];
            $count = 0;
        }
        switch($order_sort){
            default:
                $order_sort = -1;
                break;
            case "desc":
                $order_sort = 1;
                break;
        }
        switch($order_column){
            default:
                $orderBy = ["created_at" => $order_sort];
                break;
        }
        $data = Translations::find([
            $binds,
            "sort"      => $orderBy,
            "limit"     => $iDisplayLength,
            "skip"      => $iDisplayStart,
        ]);
        $count = Translations::count([
            $binds,
        ]);



        $end = $iDisplayStart + $iDisplayLength;
        $end = $end > $count ? $count : $end;

        $level_list = array(
            array("info"    => "Member"),
            array("success" => "Executive"),
            array("warning" => "Moderator"),
            array("danger"  => "Administrator"),
        );
        $status_list = array(
            array("danger"  => "deactive"),
            array("success" => "active"),
            array("warning" => "pending"),
        );

        if ($data){
            foreach($data as $value){
                $templates = [];
                foreach($value->template_id as $tid)
                    $templates[] = @$this->lang->templates[$tid]["name"];
                $records["data"][] = array(
                    '<input type="checkbox" name="id[]" value="'.$value->_id.'">',
                    $value->key,
                    mb_substr($value->translations->$fromLang,0,500),
                    mb_substr($value->translations->$lang,0,500),
                    implode(", ", $templates),
                    '<a href="javascript:editKeyword(\''.$value->_id.'\', \''.$lang.'\', \'html\', \''.$fromLang.'\');" class="btn prop-btn default btn-xs purple"><i class="fa fa-edit"></i> Edit</a>',
                );
            }
        }

        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $count;
        $records["recordsFiltered"] = $count;

        echo json_encode($records);
        $this->view->disable();
        exit;
	}

    public function addAction()
    {
        $error = false;
        $success = false;
        $added = 0;
        if ($this->request->get("submit")){
            $keyword    = trim(strtolower($this->request->get("keyword")));
            $description  = $this->request->get("description");
            $lang     = trim($this->request->get("lang"));

            $tags       = trim(strtolower($this->request->get("tags")));
            $tags       = explode(",", $tags);
            $tags_arr   = [];
            foreach($tags as $tag)
                if(strlen(trim($tag)) > 1)
                    $tags_arr[] = trim($tag);

            $sites_arr  = $this->request->get("sites");
            $site_ids   = [];
            foreach($sites_arr as $value)
                $site_ids[] = (int)$value;

            if (strlen($keyword) < 2){
                $error = 'Keyword is wrong. (min 4, max 150 alphanumeric characters)';
            //}elseif (!in_array($lang, $this->language->langs)){
            //    $error = 'Lang is wrong';
            }else{
                $keyword = trim(strtolower($keyword));
                    if(!Seowords::getByKeyword($keyword)){
                        $S = new Seowords();
                        $S->keyword         = $keyword;
                        $S->description     = $description;
                        $langs = [$lang];
                        $S->lang            = $langs;
                        $S->sites           = $site_ids;
                        $S->tags            = $tags_arr;
                        $S->active          = 0;
                        $S->created_at      = new \MongoDate(time());
                        $S->save();
                        foreach($S->getMessages() as $value)
                            $error = $value;
                        $added++;
                    }else{
                        $error = "This keyword exists";
                    }
                if (!$error){
                    $success = "Successfully added";
                    $this->view->setVar("id", $S->id);
                }
            }
        }
        $sites = Sites::find([
            [],
            "sort" => ["id" => 1],
        ]);

        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->setVar("added", $added);
        $this->view->setVar("sites", $sites);
    }

    public function editAction($id)
    {
        $uuid       = $this->request->get("uuid");
        $error      = false;
        $success    = false;
        $data       = Translations::findById($uuid);
        if(!$data)
        {
            $error = "No translation";
        }
        elseif($this->request->get("translations"))
        {
            $translations_  = $this->request->get("translations");
            $translations = [];
            foreach($data->translations as $key => $value)
                $translations[$key] = $value;
            foreach($translations_ as $key => $value)
                $translations[$key] = mb_strlen(trim($value)) > 0 ? trim($value): null;

            Translations::update(["key" => $data->key], ["translations" => $translations]);
                if (!$error)
                    $success = "Successfully updated";
            $data       = Translations::findById($uuid);
        }

        $this->view->setVar("id", $id);
        $this->view->setVar("data", $data);
        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
        $this->view->disable();
        $this->view->partial("translations/edit");
    }


    public function excelAction(){
        function cleanData($str)
        {
            $str = preg_replace("/\t/", " ", $str);
            $str = preg_replace("/\r?\n/", " ", $str);
            return $str;
        }

        if($this->request->get("to_lang")){
            //header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
            header("Content-type:   application/x-msexcel; charset=utf-8");
            header("Content-Disposition: attachment; filename=translations.xls");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);

            $from   = $this->request->get("from_lang");
            $to     = $this->request->get("to_lang");
            $data = Translations::find([
                [],
                "sort" => ["created_at" => 1],
                //"limit" => $iDisplayLength,
                //"skip" => $iDisplayStart,
            ]);

            echo "Key \t ".$from." \t ".$to." \t\n";

            foreach($data as $value){
                echo $value->key." \t ".cleanData($value->translations[$from])." \t ".cleanData($value->translations[$to])." \t\n";
            }
            exit;
        }
    }

    public function deleteAction($id)
    {
        $id = ($this->request->get("id"));
        $error = false;
        $success = false;
        $data = Translations::findById($id);
        if ($this->request->get("submit") && $data){
                $data->delete();
                foreach($data->getMessages() as $value)
                    $error = $value;
                if (!$error){
                    $success = "Successfully deleted";
                }
        }
        $this->view->setVar("id", $id);
        $this->view->setVar("error", $error);
        $this->view->setVar("success", $success);
    }
}
<?php
namespace Lib;

use Models\Cache;
use Models\Operations;

class Logger
{
    public function save($lang, $data = [])
    {
        $controller         = $data["controller"];
        $action             = $data["action"];
        $id                 = $data["id"];
        $user               = $data["user"];
        $error              = $data["error"];
        $success            = $data["success"];

        if(!$user)
            return false;

        $vars               = $_REQUEST;
        unset($vars["_url"]);

        $actionType = "edit";
        if (stripos(strtolower($action), 'add') !== false)
            $actionType = "add";
        if(@$_REQUEST["customActionName"] == "delete")
            $actionType = "delete";


        if($success)
        {
            $Insert = [
                "user_id"           => (int)$user->id,
                "section"           => $controller,
                "subsection"        => $action,
                "object_id"         => (int)$id,
                "action_type"       => $actionType,
                "success"           => $success,
                "error"             => $error,
                "url"               => $_SERVER["REQUEST_URI"],
                "ip"                => @$_SERVER["REMOTE_ADDR"],
                "browser"           => @$_SERVER["HTTP_USER_AGENT"],
                "variables"         => strlen(json_encode($vars, true)) > 1000 ? substr(json_encode($vars, true),0,1000): $vars,
                "is_deleted"        => 0,
                "created_at"        => Operations::getDate(),
            ];
            Operations::insert($Insert);
        }

        return true;
    }


    public function getSections($lang)
    {
        return [
            "auth" => [
                "title"         => $lang->get("Login"),
            ],
            "citizens" => [
                "title"         => $lang->get("Citizens"),
            ],
            "index" => [
                "title"         => $lang->get("Calendar"),
            ],
            "case" => [
                "title"         => $lang->get("Case"),
            ],
            "profile" => [
                "title"         => $lang->get("Profile"),
            ],
            "cases" => [
                "title"         => $lang->get("Cases"),
            ],
            "settings" => [
                "title"         => $lang->get("Settings"),
            ],
            "todo" => [
                "title"         => $lang->get("TodoList"),
            ],
            "notes" => [
                "title"         => $lang->get("Notes"),
            ],
            "noteitems" => [
                "title"         => $lang->get("Notes"),
            ],
            "calendar" => [
                "title"         => $lang->get("Calendar"),
            ],
            "partners" => [
                "title"         => $lang->get("Partners"),
            ],
            "partnerusers" => [
                "title"         => $lang->get("ContactPersons", "Contact persons"),
            ],
            "employees" => [
                "title"         => $lang->get("Employees"),
            ],
            "moderators" => [
                "title"         => $lang->get("Moderators"),
            ],
            "jobdatabase" => [
                "title"         => $lang->get("JobList"),
            ],
            "knowledgebase" => [
                "title"         => $lang->get("Knowledgebase"),
            ],
            "knowledgebaseitems" => [
                "title"         => $lang->get("Knowledgebase"),
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
            ],
            "worklog" => [
                "title"         => $lang->get("WorkLog"),
            ],
            "mailbox" => [
                "title"         => $lang->get("Mailbox"),
            ],
            "survey" => [
                "title"         => $lang->get("Survey"),
            ],
            "contracts" => [
                "title"         => $lang->get("Contracts"),
            ],
            "offers" => [
                "title"         => $lang->get("Offer"),
            ],
            "translations" => [
                "title"         => $lang->get("Translations"),
            ],
            "vacancies" => [
                "title"         => $lang->get("JobList"),
            ],
        ];
    }

    public function getType($lang, $type)
    {
        $Types = [
            "all"       => $lang->get("All"),
            "add"       => $lang->get("Add"),
            "view"      => $lang->get("View"),
            "edit"      => $lang->get("Edit"),
            "delete"    => $lang->get("Delete"),
            "send"      => $lang->get("Send"),
            "login"     => $lang->get("Login"),
        ];
        return $Types[$type];
    }
}
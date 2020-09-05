<?php
namespace Lib;

use Models\Cache;

class Permission{
    public $data;

    public $lang = "en";

    public $permissions = [
        "users"
    ];



    public function init($templateId, $lang=false)
    {

    }



    public function getEmployeeConstruct($lang)
    {
        return [
            "calendar" => [
                "title"         => $lang->get("Calendar"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "attach_employee" => [
                "title"         => $lang->get("AttachEmployee", "Attach Employee"),
                "permissions"   => [
                    "all", "add", "edit",
                ],
            ],

            "notes" => [
                "title"         => $lang->get("Notes"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
                "has_margin"    => true,
            ],
            "contracts" => [
                "title"         => $lang->get("Contracts"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "documents" => [
                "title"         => $lang->get("Documents"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
                "permissions"   => [
                    "all", "view","send"
                ],
            ],
            "knowledgebase" => [
                "title"         => $lang->get("Knowledgebase"),
                "permissions"   => [
                    "all", "view",
                ],
            ],

            "citizen_parent" => [
                "title"         => $lang->get("CitizenParents", "Citizen parents"),
                "has_margin"    => true,
                "permissions"   => [
                    "all", "view"
                ],
            ],


            "cases" => [
                "title"         => $lang->get("Cases"),
                "has_margin"    => true,
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "case_contracts" => [
                "title"         => $lang->get("CaseContracts", "Case contracts"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "case_documents" => [
                "title"         => $lang->get("CaseDocuments", "Case documents"),
                "permissions"   => [
                    "all", "view","add","edit", "delete"
                ],
            ],
            "case_tradingplans" => [
                "title"         => $lang->get("TradingPlans", "Trading plans"),
                "permissions"   => [
                    "all", "view","add","edit"
                ],
            ],
            "case_evalreports" => [
                "title"         => $lang->get("CaseReports", "Case Reports"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],
            "case_activities" => [
                "title"         => $lang->get("ActivityAndPayment", "Aktivitet & Refusion"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],
            "case_calendar" => [
                "title"         => $lang->get("CaseCalendar", "Case calendar"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],
            "case_notes" => [
                "title"         => $lang->get("CaseNotes", "Case notes"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],
            "case_timerecords" => [
                "title"         => $lang->get("CaseTimerecords", "Case time records"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],

        ];
    }





    public function getCitizenConstruct($lang)
    {
        return [
            "calendar" => [
                "title"         => $lang->get("Calendar"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "contracts" => [
                "title"         => $lang->get("Contracts"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "documents" => [
                "title"         => $lang->get("Documents"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
                "permissions"   => [
                    "all", "view","send"
                ],
            ],



            "cases" => [
                "title"         => $lang->get("Cases"),
                "has_margin"    => true,
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "case_index" => [
                "title"         => $lang->get("CaseInformation", "Case information"),
                "permissions"   => [
                    "all", "view"
                ],
            ],
            "case_documents" => [
                "title"         => $lang->get("CaseDocuments", "Case documents"),
                "permissions"   => [
                    "all", "view","add","edit", "delete"
                ],
            ],
            "case_tradingplans" => [
                "title"         => $lang->get("TradingPlans", "Trading plans"),
                "permissions"   => [
                    "all", "view","add"
                ],
            ],
            "case_activities" => [
                "title"         => $lang->get("ActivityAndPayment", "Aktivitet & Refusion"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],
            "case_calendar" => [
                "title"         => $lang->get("CaseCalendar", "Case calendar"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],
            "case_notes" => [
                "title"         => $lang->get("CaseNotes", "Case notes"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],

        ];
    }





    public function getPartnerConstruct($lang)
    {
        return [
            "calendar" => [
                "title"         => $lang->get("TodoList"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "citizens" => [
                "title"         => $lang->get("Citizens"),
                "permissions"   => [
                    "all", "view",
                ],
            ],
            "notes" => [
                "title"         => $lang->get("Notes"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
                "permissions"   => [
                    "all", "view","send"
                ],
            ],


            "cases" => [
                "title"         => $lang->get("Cases"),
                "has_margin"    => true,
                "permissions"   => [
                    "all", "view"
                ],
            ],
            "case_documents" => [
                "title"         => $lang->get("CaseDocuments", "Case documents"),
                "permissions"   => [
                    "all", "view","add","edit", "delete"
                ],
            ],
            "case_tradingplans" => [
                "title"         => $lang->get("TradingPlans", "Trading plans"),
                "permissions"   => [
                    "all", "view","add"
                ],
            ],
            "case_evalreports" => [
                "title"         => $lang->get("CaseReports", "Case Reports"),
                "permissions"   => [
                    "all", "view"
                ],
            ],
        ];
    }




    public function getModeratorConstruct($lang)
    {
        return [
            "citizens" => [
                "title"         => $lang->get("Citizens"),
                "add", "permissions"   => [
                   "all", "view", "add", "edit", "delete",
                ],
            ],
            "settings" => [
                "title"         => $lang->get("Settings"),
                "permissions"   => [
                    "all",  "view", "add", "edit", "delete",
                ],
            ],
            "todo" => [
                "title"         => $lang->get("TodoList"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "notes" => [
                "title"         => $lang->get("Notes"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "calendar" => [
                "title"         => $lang->get("Calendar"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "partners" => [
                "title"         => $lang->get("Partners"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "employees" => [
                "title"         => $lang->get("Employees"),
                "permissions"   => [
                    "all",  "view", "add", "edit", "delete",
                ],
            ],
            "moderators" => [
                "title"         => $lang->get("Moderators"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "jobdatabase" => [
                "title"         => $lang->get("JobList"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "knowledgebase" => [
                "title"         => $lang->get("Knowledgebase"),
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "communication" => [
                "title"         => $lang->get("Communication"),
                "permissions"   => [
                    "all", "view","add"
                ],
            ],
            "worklog" => [
                "title"         => $lang->get("WorkLog"),
                "permissions"   => [
                    "all", "view","add","delete",
                ],
            ],
            "mailbox" => [
                "title"         => $lang->get("Mailbox"),
                "permissions"   => [
                    "all", "view","add","delete",
                ],
            ],
            "survey" => [
                "title"         => $lang->get("Survey"),
                "permissions"   => [
                    "all", "view","add","delete",
                ],
            ],
            "contracts" => [
                "title"         => $lang->get("Contracts"),
                "permissions"   => [
                    "all", "view","add","delete",
                ],
            ],
            "offers" => [
                "title"         => $lang->get("Offer"),
                "permissions"   => [
                    "all", "view", "add", "delete",
                ],
            ],
            "translations" => [
                "title"         => $lang->get("Translations"),
                "permissions"   => [
                    "all", "view", "edit",
                ],
            ],
            "logs" => [
                "title"         => $lang->get("Logs"),
                "permissions"   => [
                    "all", "view", "delete",
                ],
            ],


            "cases" => [
                "title"         => $lang->get("Cases"),
                "has_margin"    => true,
                "permissions"   => [
                    "all", "view", "add", "edit", "delete",
                ],
            ],
            "case_activities" => [
                "title"         => $lang->get("ActivityAndPayment", "Aktivitet & Refusion"),
                "permissions"   => [
                    "all", "view","add","edit","delete"
                ],
            ],

        ];
    }



    public function getList($lang, $userData)
    {
        $permissions = [];
        $construct = [];
        if($userData->type == "moderator"){
            $construct = $this->getModeratorConstruct($lang);
        }elseif($userData->type == "employee"){
            $construct = $this->getEmployeeConstruct($lang);
        }elseif($userData->type == "citizen"){
            $construct = $this->getCitizenConstruct($lang);
        }elseif($userData->type == "partner"){
            $construct = $this->getPartnerConstruct($lang);
        }
        foreach($construct as $key => $value)
        {
            $types = [];
            foreach($value["permissions"] as $type)
                $types[$type] = $this->getType($type, $lang);

            $permissions[$key] = [
                "title"         => $value["title"],
                "has_margin"    => (@$value["has_margin"]) ? true: false,
                "permissions"   => $types,
            ];
        }

        return $permissions;
    }



    public function getType($type, $lang)
    {
        $permissionTypes = [
            "all"       => $lang->get("All"),
            "add"       => $lang->get("Add"),
            "view"      => $lang->get("View"),
            "edit"      => $lang->get("Edit"),
            "delete"    => $lang->get("Delete"),
            "send"      => $lang->get("Send"),
        ];
        return $permissionTypes[$type];
    }
}
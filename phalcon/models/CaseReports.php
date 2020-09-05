<?php
namespace Models;

use Lib\MainDB;

class CaseReports extends MainDB
{
    public static function getSource()
    {
        return "case_reports";
    }

    public static function getById($id)
    {
        return self::findFirst([
            [
                "id" => (int)$id
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


    public static $leakbots = false;

    public static function getSettings($lang, $data = [], $myData=false, $userData=false)
    {
        $leakbotsQuery = Users::find([
            [
                "type" => "user",
                "is_deleted" => 0,
            ]
        ]);
        $leakbots = [];
        foreach($leakbotsQuery as $value){
            $leakbots[] = [
                "title" => (string)$value->leakbot_id,
                "value" => (string)$value->leakbot_id,
            ];
        }

        $empQuery = Users::find([
            [
                "type"          => "employee",
                "is_deleted"    => 0,
            ]
        ]);
        $empployees = [];
        foreach($empQuery as $value)
        {
            $empployees[] = [
                "title" => (string)$value->firstname." ".(string)$value->lastname,
                "value" => (int)$value->id,
            ];
        }

        $params     = [];
        $params[]   =   [
            //"key"   => "type_22",
            "type"      => "header",
            "visible"   => false,
            "text"      => $lang->get("General"),
        ];
        $params[]   =    [
            "key"       => "type_1",
            "title"     => false,
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_1_1",
                    "title"     => $lang->get("LeakbotID", "Leakbot ID"),
                    "type"      => "select",
                    "value"     => ($userData && $userData->leakbot_id) ? (string)$userData->leakbot_id: false,
                    "visible"   => true,
                    "values"    => $leakbots
                ]
            ],
        ];
        $params[] =    [
            "key"       => "type_2",
            "title"     => $lang->get("Tekniker"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_2_1",
                    "title"     => false,
                    "type"      => "select",
                    "visible"   => true,
                    //"value"     => ($data && @$data->type_2_1) ? (int)$data->type_2_1: false,
                    "values"    => $empployees,
                ]
            ],
        ];
        $params[] =    [
            "key"   => "type_3",
            "title" => $lang->get("Date"),
            "type"      => "multi",
            "visible"   => true,
            "children"  => [
                [
                    "key"       => "type_3_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "date",
                ]
            ],
        ];
        $params[] =    [
            "key"       => "type_4",
            "title"     => $lang->get("LeakbotPlacering", "Leakbot placering"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_4_1",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];


        $paramValues = [];
        for($i=10;$i<150;$i+=5){
            $pv = $i."-".($i+5);
            $paramValues[] = ["title" => $pv." cm", "value" => $pv];
        }
        $params[] =    [
            "key"       => "type_5",
            "title"     => $lang->get("LBinstallationHojde", "LB istallationshøjde"),
            "type"      => "multi",
            "visible"   => true,
            "children"  => [
                [
                    "key"       => "type_5_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => $paramValues,
                ],
                [
                    "key"       => "type_5_2",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];
        $params[] =    [
            "key"   => "type_6",
            "title" => $lang->get("HvidevareTetPaLb", "Hvidavare tæt på LB"),
            "type"  => "multi",
            "visible"   => true,
            "children"  => [
                [
                    "key"       => "type_6_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "multiselect",
                    "values"    => [
                        ["title" => "Vaskemaskine", "value" => 1, "action" => ["hide" => ["type_6_2"]]],
                        ["title" => "Opvaskemaskine", "value" => 2, "action" => ["hide" => ["type_6_2"]]],
                        ["title" => "Tørretumbler", "value" => 3, "action" => ["hide" => ["type_6_2"]]],
                        ["title" => "Køleskab/Fryser", "value" => 4, "action" => ["hide" => ["type_6_2"]]],
                        ["title" => "Gulvvarme", "value" => 5, "action" => ["hide" => ["type_6_2"]]],
                        ["title" => "Kedel", "value" => 6, "action" => ["hide" => ["type_6_2"]]],
                        //["title" => "Andet", "value" => 7, "action" => ["show" => ["type_6_2"]]]
                    ],
                ],
                [
                    "key"       => "type_6_2",
                    //"visible"   => ($data && @$data["type_6_1"] == 7) ? true: false,
                    //"title"     => "Other",
                    "placeholder"=> "Andet",
                    "type"      => "text",
                ],
                [
                    "key"       => "type_6_3",
                    "visible"   => true,
                    "title"     => $lang->get("File"),
                    "type"      => "file",
                ]
            ],
        ];
        $params[] =    [
            "key"       => "type_7",
            "title"     => $lang->get("AndreObservationer", "Andre observationer"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_7_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "multiselect",
                    "values"    => [
                        ["title" => "Mærkbart træk", "value" => 1, "action" => ["hide" => ["type_7_2"]]],
                        ["title" => "Kold hulrum", "value" => 2, "action" => ["hide" => ["type_7_2"]]],
                        ["title" => "Tæt på ydermur", "value" => 3, "action" => ["hide" => ["type_7_2"]]],
                        ["title" => "Isolering", "value" => 4, "action" => ["hide" => ["type_7_2"]]],
                        //["title" => "Anden el", "value" => 5, "action" => ["show" => ["type_7_2"]]]
                    ],
                ],
                [
                    "key"       => "type_7_2",
                    //"visible"   => ($data && @$data["type_7_1"] == 5) ? true: false,
                    "title"     => $lang->get("Other"),
                    "type"      => "text",
                ],
                [
                    "key"       => "type_7_3",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];
        $params[] =    [
            "key"       => "type_8",
            "title"     => $lang->get("HarKunden", "Har kunden"),
            "type"      => "multi",
            "visible"   => true,
            "children"  => [
                [
                    "key"       => "type_8_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "multiselect",
                    "values"    => [
                        ["title" => "Vandmåler", "value" => 1, "action" => ["hide" =>["type_8_2"]]],
                        ["title" => "Blødgøringsanlæg", "value" => 2, "action" => ["hide" =>["type_8_2"]]],
                        ["title" => "Kogendevandhane", "value" => 3, "action" => ["hide" =>["type_8_2"]]],
                        //["title" => "Andre observationer", "value" => 4, "action" => ["show" => ["type_8_2"]]]
                    ],
                ],
                [
                    "key"       => "type_8_2",
                    //"visible"   => ($data && @$data["type_8_1"] == 4) ? true: false,
                    "title"     => false,
                    "type"      => "text",
                ],
                [
                    "key"       => "type_8_3",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];
        $params[] =    [
            "key"       => "type_9",
            "title"     => $lang->get("DataFraMagicEar", "Data Fra magic ear"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_9_1",
                    "title"     => false,
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1],
                        ["title" => $lang->get("Nej"), "value" => 2]
                    ],
                ],
            ],
        ];
        $params[] =    [
            "key"       => "type_10",
            "title"     => $lang->get("MonteretKnakantenne", "Monteret knækantenne"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_10_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1],
                        ["title" => $lang->get("Nej"), "value" => 2]
                    ],
                ],
            ],
        ];
        $params[] =    [
            "key"       => "type_11",
            "title"     => $lang->get("OpvarmVarmtvand", "Opvarm varmtvand"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_11_1",
                    "title"     => false,
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => "Elvarme",      "value" => 1, "action" => ["hide" => ["type_11_2"]]],
                        ["title" => "Gas",          "value" => 2, "action" => ["hide" => ["type_11_2"]]],
                        ["title" => "Olie",         "value" => 3, "action" => ["hide" => ["type_11_2"]]],
                        ["title" => "Jordvarme",    "value" => 4, "action" => ["hide" => ["type_11_2"]]],
                        ["title" => "Solvarme",     "value" => 5, "action" => ["hide" => ["type_11_2"]]],
                        ["title" => "Fjernvarme",    "value" => 6, "action" => ["hide" => ["type_11_2"]]],
                        ["title" => $lang->get("Andet"),        "value" => 7, "action" => ["show" => ["type_11_2"]]]
                    ],
                ],
                [
                    "key"       => "type_11_2",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "text",
                ],
                [
                    "key"       => "type_11_3",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];
        $params[] =    [
            "key"       => "type_12",
            "title"     => $lang->get("VVBstorrelse", "VVB størrelse"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_12_1",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => [
                        ["title" => "30-50L",   "value" => "30-50"],
                        ["title" => "50-100L",  "value" => "50-100"],
                        ["title" => "100-130L", "value" => "100-130"],
                        ["title" => "130-160L", "value" => "130-160"],
                        ["title" => "160-180L", "value" => "160-180"],
                        ["title" => "180-210L", "value" => "180-210"],
                        ["title" => "210L+",    "value" => "210+"]
                    ],
                ],
                [
                    "key"       => "type_12_2",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];

        $params[] =    [
            //"key"   => "type_13",
            "type"  => "header",
            "visible"   => true,
            "text"  => $lang->get("Undersogelser", "Undersøgelser"),
        ];




        $templateCount = 0;
        if($data["type_14_1_0"] || $data["type_14_2_0"] || $data["type_14_3_0"] || $data["type_14_4_0"]){
            for($i=1;$i<10;$i++)
            {
                if($data["type_14_".$i."_0"])
                {
                    $params[] = [
                        "key"       => "type_14_".$i,
                        "title"     => "Test ".$i,
                        "type"      => "multi",
                        "visible"   => true,
                        "children"  => [
                            [
                                "key"       => "type_14_".$i."_0",
                                "title"     => false,
                                "type"      => "select",
                                "visible"   => true,
                                "values"    => [
                                    ["title" => "Alt vand",     "value" => 1],
                                    ["title" => "Kun kold vand", "value" => 2],
                                    ["title" => "Varmtvand",    "value" => 3],
                                    ["title" => "Fejltest",     "value" => 4],
                                    ["title" => "Overvågning",     "value" => 4],
                                ]
                            ],
                            [
                                "key"       => "type_14_".$i."_1",
                                "title"     => "Tryktab",
                                "visible"   => true,
                                "type"      => "text",
                            ],
                            [
                                "key"       => "type_14_".$i."_2",
                                "title"     => $lang->get("Kommentar"),
                                "visible"   => true,
                                "type"      => "text",
                            ]
                        ]
                    ];

                    $templateCount++;
                }
            }
            //var_dump($templateChilds);
        }
        $params[] =    [
            "key"       => "type_14",
            "title"     => false,
            "type"      => "multi",
            "visible"   => true,
            "children"  => [
                [
                    "key"       => "type_14_0",
                    "title"     => false,
                    "visible"   => true,
                    "type"      => "button_plus",
                    "action"    => "generate",
                    "count"     => $templateCount,
                    "template_key"      => "type_14_{i}",
                    "template_title"    => "Test {i}",
                    "template"  => [
                        [
                            "key"       => "type_14",
                            "title"     => false,
                            "type"      => "select",
                            "visible"   => true,
                            "values"    => [
                                ["title" => "Alt vand",     "value" => 1],
                                ["title" => "Kun kold vand", "value" => 2],
                                ["title" => "Varmtvand",    "value" => 3],
                                ["title" => "Fejltest",     "value" => 4],
                            ]
                        ],
                        [
                            "key"       => "type_14",
                            "title"     => "Tryktab",
                            "visible"   => true,
                            "type"      => "text",
                        ],
                        [
                            "key"       => "type_14",
                            "title"     => "Kommentar",
                            "visible"   => true,
                            "type"      => "text",
                        ]
                    ],
                ]
            ],
        ];




        $params[] =    [
            "key"       => "type_15",
            "title"     => $lang->get("andreObservationer", "Andre observationer"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_15_1",
                    "visible"   => true,
                    "type"      => "input",
                ]
            ],
        ];


        $params[] =    [
            "key"       => "type_30",
            "title"     => $lang->get("HvorErLRCplaceret", "aHvor er LRC placeret"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_30_1",
                    "title"     => $lang->get("Kommentar"),
                    "visible"   => true,
                    "type"      => "text",
                ],
                [
                    "key"       => "type_30_2",
                    "title"     => $lang->get("File"),
                    "visible"   => true,
                    "type"      => "file",
                ]
            ],
        ];


        $params[] =    [
            "key"       => "type_16",
            "title"     => $lang->get("forstemalingfraLRC", "Første måling fra LRC"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_16_1",
                    "visible"   => true,
                    "type"      => "input",
                    "unit"      => "ml.",
                ]
            ],
        ];

        $params[] =    [
            "key"       => "type_17",
            "title"     => $lang->get("AfsluttendeMaling", "Afsluttende måling"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_17_1",
                    "visible"   => true,
                    "type"      => "input",
                    "unit"      => "ml.",
                ]
            ],
        ];

        $params[] =    [
            "key"       => "type_18",
            "title"     => $lang->get("AfsluttetPaStedet", "Afsluttet på stedet"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_18_1",
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1],
                        ["title" => $lang->get("Nej"), "value" => 2],
                        ["title" => "Delvist", "value" => 3],
                        ["title" => $lang->get("Andet"), "value" => 4],
                    ]
                ],
                [
                    "key"       => "type_18_2",
                    "title"     => $lang->get("Kommentar"),
                    "visible"   => true,
                    "type"      => "text",
                ]
            ]
        ];

        $params[] =    [
            "key"       => "type_19",
            "title"     => $lang->get("AntalLeakierFundet", "Antal lækager funder"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_19_1",
                    "visible"   => true,
                    "type"      => "input",
                ]
            ],
        ];

        $params[] =    [
            "key"       => "type_20",
            "title"     => $lang->get("AntalLeakierStoppet", "Antal lækager stoppet"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_20_1",
                    "visible"   => true,
                    "type"      => "input",
                ]
            ],
        ];

        $params[] =    [
            "key"       => "type_21",
            "title"     => $lang->get("InfoTilKunden", "Info til kunden"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_21_1",
                    "visible"   => true,
                    "type"      => "input",
                ]
            ],
        ];

        $params[] =    [
            //"key"   => "type_22",
            "type"      => "header",
            "visible"   => true,
            "text"      => $lang->get("Opfolgende", "Opfølgende"),
        ];

        $params[] =    [
            "key"       => "type_23",
            "title"     => $lang->get("Blevderfunderlekage", "Blev der fundet lækage?"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_23_1",
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1],
                        ["title" => $lang->get("Nej"), "value" => 2],
                        ["title" => $lang->get("Andet"), "value" => 3],
                    ]
                ],
                [
                    "key"       => "type_23_2",
                    "visible"   => true,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ]
            ]
        ];


        $params[] =    [
            "key"       => "type_31",
            "title"     => $lang->get("BillederAfRep", "Billeder af rep"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_31_1",
                    "visible"   => true,
                    "title"     => $lang->get("File"),
                    "type"      => "file",
                ]
            ]
        ];

        $params[] =    [
            "key"       => "type_24",
            "title"     => $lang->get("VardernogenSkjult", "Var der nogen skjult?"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_24_1",
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1],
                        ["title" => $lang->get("Nej"), "value" => 2],
                        ["title" => $lang->get("Andet"), "value" => 3],
                    ]
                ],
                [
                    "key"       => "type_24_2",
                    "visible"   => true,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ]
            ]
        ];

        $params[] =    [
            "key"       => "type_25",
            "title"     => $lang->get("RisikoForVandkande", "Risiko for vandskade?"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_25_1",
                    "visible"   => true,
                    "type"      => "select",
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_25_3"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_25_3"]]],
                        ["title" => $lang->get("Andet"), "value" => 3, "action" => ["hide" => ["type_25_3"]]],
                    ]
                ],
                [
                    "key"       => "type_25_3",
                    "title"     => $lang->get("File"),
                    "visible"   => ($data && @$data["type_25_1"] == 1) ? true: false,
                    "type"      => "file",
                ],
                [
                    "key"       => "type_25_2",
                    "visible"   => true,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
            ]
        ];

        $params[] =    [
            "key"       => "type_26",
            "title"     => $lang->get("HvadErUdført", "Hvad er udført?"),
            "visible"   => true,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_26_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values" => [
                        ["title" => "Indmad I toilet",  "value" => 1, "action" => ["hide" => ["type_26_2"]]],
                        ["title" => "Stophane/Ventil",  "value" => 2, "action" => ["hide" => ["type_26_2"]]],
                        ["title" => "Sikkerhedsventil", "value" => 3, "action" => ["hide" => ["type_26_2"]]],
                        ["title" => "Kuglehane",        "value" => 4, "action" => ["show" => ["type_26_2"]]],
                        ["title" => "Rørudskiftning",   "value" => 5, "action" => ["hide" => ["type_26_2"]]],
                        ["title" => "Spulehane",        "value" => 10, "action" => ["hide" => ["type_26_2"]]],
                        ["title" => "Udendørshane",     "value" => 11, "action" => ["hide" => ["type_26_2"]]],
                        ["title" => "Indmad armatur",   "value" => 12, "action" => ["hide" => ["type_26_2"]]],
                    ],
                ],
                [
                    "key"       => "type_26_2",
                    "type"      => "select",
                    "visible"   => false,
                    "values" => [
                        ["title" => "Køkken", "value" => 6],
                        ["title" => "Håndvask", "value" => 7],
                        ["title" => "Bruser", "value" => 8],
                        ["title" => "Bryggers", "value" => 9],
                    ],
                ],
            ]
        ];

        $params[] =    [
            "key"       => "type_27",
            "title"     => $lang->get("Klassificering"),
            "visible"   => true,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_27_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => "Vedligeholdelse", "value" => 1],
                        ["title" => "Arbejde der skal udføres", "value" => 2],
                        ["title" => "Ingen lækage fundet", "value" => 3],
                    ],
                ],
            ]
        ];

        $params[] =    [
            "key"       => "type_28",
            "title"     => $lang->get("MisvedligeholdtInstallation", "Misligeholdt installation?"),
            "visible"   => true,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_28_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1],
                        ["title" => $lang->get("Nej"), "value" => 2],
                        ["title" => $lang->get("Andet"), "value" => 3],
                    ]
                ],
                [
                    "key"       => "type_28_2",
                    "visible"   => true,
                    "title" => $lang->get("Kommentar"),
                    "type"  => "text",
                ]
            ]
        ];

        $params[] =    [
            "key"       => "type_29",
            "title"     => $lang->get("ErDerUdforKlsArbejde", "Er der udført KLS arbejde?"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_29_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_2", "type_29_3", "type_29_4", "type_29_5", "type_29_6", "type_29_7", "type_29_8", "type_29_9"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_2", "type_29_3", "type_29_4", "type_29_5", "type_29_6", "type_29_7", "type_29_8", "type_29_9"]]],
                    ],
                ],
                [
                    "key"       => "type_29_2",
                    "title"     => $lang->get("Installationstype"),
                    "visible"   => ($data && (int)@$data["type_29_1"] == 1) ? true: false,
                    "type"      => "select",
                    "values"    => [
                        ["title" => "Sikkerhedsventil", "value" => 1],
                        ["title" => "Rørudskiftning", "value" => 2],
                        ["title" => "Ventil udskiftning", "value" => 3],
                        ["title" => "Armatur", "value" => 4],
                    ],
                ],
            ]
        ];


        // Modtagekontrol
        $params[] =    [
            "key"       => "type_29_3",
            "title"     => $lang->get("MaterialerKontrolleret", "Materialer kontrolleret"),
            "visible"   => ($data && (int)@$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_3_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_3_2"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_3_2"]]],
                    ]
                ],
                [
                    "key"       => "type_29_3_2",
                    "visible"   => ($data && (int)@$data["type_29_3_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
            ]
        ];


        // Udførselskontrol
        $params[] =    [
            "key"       => "type_29_4",
            "title"     => $lang->get("SamlingerKontrolleret", "Samlinger kontrolleret"),
            "visible"   => ($data && (int)@$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_4_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_4_2"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_4_2"]]],
                    ]
                ],
                [
                    "key"       => "type_29_4_2",
                    "visible"   => ($data && (int)@$data["type_29_4_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
            ]
        ];


        // Slutkontrol
        $params[] =    [
            "key"       => "type_29_5",
            "title"     => $lang->get("VisuelTæthedskontrol", "Visuel tæthedskontrol"),
            "visible"   => ($data && @$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_5_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_5_2"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_5_2"]]],
                    ]
                ],
                [
                    "key"       => "type_29_5_2",
                    "visible"   => ($data && (int)@$data["type_29_5_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
            ]
        ];


        // Tæthedskontrol
        $params[] =    [
            "key"       => "type_29_6",
            "title"     => $lang->get("Tæthedskontrol", "Tæthedskontrol"),
            "visible"   => ($data && @$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_6_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_6_2"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_6_2"]]],
                    ]
                ],
                [
                    "key"       => "type_29_6_2",
                    "visible"   => ($data && (int)@$data["type_29_6_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
            ]
        ];


        // Funktionsafprøvning
        $params[] = [
            "key"       => "type_29_7",
            "title"     => $lang->get("Funktionsafprøvning", "Funktionsafprøvning"),
            "visible"   => ($data && @$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_7_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_7_2", "type_29_7_3"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_7_2", "type_29_7_3"]]],
                    ]
                ],
                [
                    "key"       => "type_29_7_2",
                    "visible"   => ($data && (int)@$data["type_29_7_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
                [
                    "key"       => "type_29_7_3",
                    "visible"   => ($data && (int)@$data["type_29_7_1"] == 1) ? true: false,
                    "title"     => $lang->get("File"),
                    "type"      => "file",
                ],
            ]
        ];


        // Afslutning
        $params[] =    [
            "key"       => "type_29_8",
            "title"     => $lang->get("Færdig"),
            "visible"   => ($data && @$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_8_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_8_2"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_8_2"]]],
                    ]
                ],
                [
                    "key"       => "type_29_8_2",
                    "visible"   => ($data && (int)@$data["type_29_8_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
            ]
        ];


        // Udført tilsyn
        $params[] =    [
            "key"       => "type_29_9",
            "title"     => $lang->get("UdførtTilsyn", "Udført tilsyn"),
            "visible"   => ($data && (int)@$data["type_29_1"] == 1) ? true: false,
            "type"      => "multi",
            "children" => [
                [
                    "key"       => "type_29_9_1",
                    "type"      => "select",
                    "visible"   => true,
                    "values"    => [
                        ["title" => $lang->get("Ja"), "value" => 1, "action" => ["show" => ["type_29_9_2", "type_29_9_3"]]],
                        ["title" => $lang->get("Nej"), "value" => 2, "action" => ["hide" => ["type_29_9_2", "type_29_9_3"]]],
                    ]
                ],
                [
                    "key"       => "type_29_9_2",
                    "visible"   => ($data && (int)@$data["type_29_9_1"] == 1) ? true: false,
                    "title"     => $lang->get("Kommentar"),
                    "type"      => "text",
                ],
                [
                    "key"       => "type_29_9_3",
                    "visible"   => ($data && (int)@$data["type_29_9_1"] == 1) ? true: false,
                    "title"     => $lang->get("KontrolAfFA", "Kontrol af FA"),
                    "type"      => "date",
                ],
            ]
        ];



        $params[] =    [
            "key"       => "type_32",
            "title"     => $lang->get("File"),
            "visible"   => true,
            "type"      => "multi",
            "children"  => [
                [
                    "key"       => "type_32_1",
                    "visible"   => true,
                    "title"     => false,
                    "type"      => "file",
                ]
            ]
        ];



        return $params;
    }
}
<?php
namespace Models;

use Lib\MainDB;

class CaseBookings extends  MainDB
{
    public static function getSource(){
        return "case_bookings";
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


    public static $hours = ["07:30-09:30", "09:30-12:30", "12:30-15:00"];

    public static function getNextDatesOfEmps($date)
    {
        $empQuery = Users::find([["type" => "employee", "is_deleted" => ['$ne' => 1]]]);
        $employees = [];
        foreach ($empQuery as $value)
            $employees[$value->id] = $value;

        $bookingQuery = self::find([[
            "date_start"    => ['$gt' => self::getDate(time()-24*3600)],
            "is_deleted"    => ['$ne' => 1]
        ]]);

        $bookings = [];
        foreach ($bookingQuery as $value){
            $bookings[$value->employee_id][$value->dateraw] = 1;
        }

        //exit(json_encode($bookings));


        $data = [];
        foreach ($empQuery as $value)
        {
            for($i=time();$i<time()+30*24*3600;$i+= 24*3600)
            {
                if($date && $date !== date("Y-m-d", $i))
                {

                }
                elseif(!$data[$value->id] && in_array((int)date("w", $i), $value->weekdays))
                {
                    $date = date("Y-m-d", $i);
                    foreach (self::$hours as $key => $hour){
                        $dateRaw = $date."_".$hour;
                        if(!$bookings[$value->id][$dateRaw]){
                            $data[$value->id] = [
                                "title" => $hour." ".date("d/m/Y", $i),
                                "date"  => date("Y-m-d", $i)."_".$key
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return $data;
    }
}
<?php
namespace Lib;

interface Model {
    public static function getSource();
}

class MainDB implements Model
{
    public static $connection = false;

    public static $db = MONGO_DB;

    public static $collection = false;

    public static function getSource()
    {
        return null;
    }

    public static function setCollection($collection)
    {
        self::$collection = $collection;
    }

    public static function init()
    {
        self::$collection = static::getSource();
        if(!self::$connection)
            self::$connection = new \MongoDB\Driver\Manager('mongodb://localhost:27017/'.self::$db);
    }

    public static function insert($data)
    {
        self::init();
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $id = $insRec->insert($data);
        $result       = self::$connection->executeBulkWrite(self::$db.'.'.self::$collection, $insRec);

        if($result)
        {
            return $id;
        }
        else
        {
            return false;
        }
    }

    /**
    public static function save($data)
    {
    return self::insert($data);
    } */


    public function save()
    {
        $ins = [];
        foreach($this as $key => $value)
        {
            if($key !== "_id")
                $ins[$key] = $value;
        }

        if($this->_id)
        {
            self::update(["_id" => $this->_id], $ins);
            //exit(json_encode($this));
            return (string)$this->_id;
        }
        return self::insert($ins);
    }


    public static function count($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        self::init();

        $Command = new \MongoDB\Driver\Command(["count" => self::$collection, "query" => $filter]);
        $Result = self::$connection->executeCommand(self::$db, $Command);
        return $Result->toArray()[0]->n;
    }

    public static function find($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        if(isset($array["limit"]))
            $options["limit"]    = @$array["limit"];
        if(isset($array["sort"]))
            $options["sort"]    = @$array["sort"];
        if(isset($array["skip"]))
            $options["skip"]   = $array["skip"];
        self::init();

        $query  = new \MongoDB\Driver\Query($filter, $options);
        $rows   = self::$connection->executeQuery(self::$db.'.'.self::$collection, $query);

        return $rows->toArray();
    }

    public static function findById($id)
    {
        if(strlen($id) < 5)
            return false;
        $filter["_id"] = self::objectId($id);
        self::init();
        $query  = new \MongoDB\Driver\Query($filter, []);
        $rows   = self::$connection->executeQuery(self::$db.'.'.self::$collection, $query);
        foreach($rows as $row)
        {
            $obj = new static();
            foreach($row as $k => $v){
                $obj->{$k} = $v;
            }
            return $obj;
        }
        return false;
    }

    public static function findFirst($array = [])
    {
        $filter = (@$array[0]) ? $array[0]: [];
        $options = [];
        $options["limit"]   = 1;
        if(isset($array["sort"]))
            $options["sort"]    = @$array["sort"];
        if(isset($array["skip"]))
            $options["skip"]   = $array["skip"];
        self::init();
        $query  = new \MongoDB\Driver\Query($filter, $options);
        $rows   = self::$connection->executeQuery(self::$db.'.'.self::$collection, $query);
        foreach($rows as $row)
        {
            $obj = new static();
            foreach($row as $k => $v){
                $obj->{$k} = $v;
            }
            return $obj;
        }
        return false;
    }

    public static function sum($field, $filter=[])
    {
        self::init();

        $pipleLine = [];
        if(count($filter) > 0)
            $pipleLine[] = ['$match'   => $filter];

        $pipleLine[] = [
            '$group' => ['_id' => '$asdaksdkaskk', 'total' => ['$sum' => '$'.$field], 'count' => ['$sum' => 1]],
        ];

        $Command = new \MongoDB\Driver\Command([
            'aggregate' => self::$collection,
            'pipeline' => $pipleLine,
            //'cursor' => new stdClass,
        ]);

        $Result = self::$connection->executeCommand(self::$db, $Command);

        //echo var_dump($field);
        //echo "<pre>";var_dump($Result->toArray()[0]->result[0]);exit;
        return $Result->toArray()[0]->result[0]->total;
    }

    public static function update($filter, $data)
    {
        self::init();
        $options = ['multi' => true, 'upsert' => false];
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $insRec->update(
            $filter,
            ['$set' => $data],
            $options
        );
        $result       = self::$connection->executeBulkWrite(self::$db.'.'.self::$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function increment($filter, $data)
    {
        self::init();
        $options = ['multi' => true, 'upsert' => false];
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $insRec->update(
            $filter,
            ['$inc' => $data],
            $options
        );
        $result       = self::$connection->executeBulkWrite(self::$db.'.'.self::$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function updateAndIncrement($filter, $update, $increment)
    {
        self::init();
        $options = ['multi' => true, 'upsert' => false];
        $insRec       = new \MongoDB\Driver\BulkWrite;
        $insRec->update(
            $filter,
            [
                '$set' => $update,
                '$inc' => $increment
            ],
            $options
        );
        $result       = self::$connection->executeBulkWrite(self::$db.'.'.self::$collection, $insRec);

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function delete($filter)
    {
        self::init();
        $bulk   = new \MongoDB\Driver\BulkWrite;
        $bulk->delete($filter, ['limit' => 0]);
        $result = self::$connection->executeBulkWrite(self::$db.'.'.self::$collection, $bulk);
        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function getDate($time=false)
    {
        if(!$time)
            $time=time();
        $time *= 1000;
        $datetime = new \MongoDB\BSON\UTCDateTime($time);
        return $datetime;
    }

    public static function dateTime($date)
    {
        if($date && method_exists($date, "toDateTime"))
            return strtotime(@$date->toDateTime()->format("Y-m-d H:i:s"));
        return 0;
    }

    public static function dateFormat($date, $format = "Y-m-d H:i:s")
    {
        if($date && method_exists($date, "toDateTime"))
            return @$date->toDateTime()->format($format);
        return 0;
    }

    public static function toSeconds($date)
    {
        if($date && method_exists($date, "toDateTime"))
            return round(@$date->toDateTime()->format("U.u"), 0);
        return 0;
    }

    public static function objectId($id)
    {
        if(strlen($id) < 5)
            return false;
        return new \MongoDB\BSON\ObjectID($id);
    }


    public function getUnixtime()
    {
        return time() + 1*3600;
    }

}
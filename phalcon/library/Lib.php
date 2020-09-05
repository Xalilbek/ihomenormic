<?php
/**
 * Created by PhpStorm.
 * User: kashmar
 * Date: 8/9/14
 * Time: 4:56 PM
 */
namespace Lib;

class Lib
{
    public function getMonthList($lang){
        return [
            1 => $lang->get("January"),
            2 => $lang->get("February"),
            3 => $lang->get("March"),
            4 => $lang->get("April"),
            5 => $lang->get("May"),
            6 => $lang->get("June"),
            7 => $lang->get("July"),
            8 => $lang->get("August"),
            9 => $lang->get("September"),
            10 => $lang->get("October"),
            11 => $lang->get("November"),
            12 => $lang->get("December"),
        ];
    }

    public function getCircleColor($num){
        $color = "#4caf50";
        if($num < 0.25){
            $color = "#de3030";
        }elseif($num < 0.5){
            $color = "#e0b01d";
        }elseif($num < 0.75){
            $color = "#e0b01d";
        }
        return $color;
    }

    public function navigator($p_n, $sql_num, $limit, $url)
    {
        $nav="";
        if ($sql_num > $limit) {
            $pnum = $sql_num / $limit + 1;
            $pn = (int) $pnum;
            if ($pn == $pnum) {$pn=$pn-1;}
            $pn1 = $p_n/$limit;
            $pn1 = (int) $pn1;
            $pn0 = $pn1 - 2;
            if ($pn0 < 0) {$pn0=0;}
            $pn2 = $pn1 + 3;
            if ($pn2>$pn) {$pn2=$pn;}
            if ($pn1 !== 0) {
                $nav .= "<a class=\"btn-page btn-page-first\" href=\"" . $url . "".(($pn1-1)*$limit)."\">First</a>";
            }
            for ($i = $pn0; $i < $pn2; $i++) {
                if ($i == $pn1) {
                    $nav .= "<a class=\"btn-page btn-page-selected\" href=\"#\">" . ($i+1) . "</a>";
                } else {
                    $nav .= "<a class=\"btn-page\" href=\"" . $url . "" . ($i * $limit) . "\">" . ($i+1) . "</a>";
                }
            }
            if ($pn1 !== $pn - 1) {
                $nav .= "<a class=\"btn-page btn-page-last\" href=\"" . $url . "" . (($pn1+1)*$limit) . "\">Last</a>";
            }
        }
        return $nav;
    }

    // ################################ FILTER SYMBOLS ################################
    public function filterSymbols($text)
    {
        $arr1=array('"', "'", '>', '<', '\\');
        $arr2=array("&#34;", "&#39;", '&#62;', '&#60;', '&#92;');
        $text = str_replace($arr1, $arr2, $text);
        return $text;
    }

    public function timeToSeperate($real_time)
    {
        $time = time() - $real_time;
        $time_counted=0;
        $echo = "";
        if ($real_time < strtotime(date("Y-m-d 00:00:00"))){
            $echo = date("Y-m-d H:i", $real_time);
        }else{
            $echo = date("H:i", $real_time);
        }
        /*
        $days = (int) ($time / (24*3600));
            $time_counted+=$days*24*3600;
        $hours = (int) (($time-$time_counted)/3600);
            $time_counted+=$hours*3600;
        $minutes = (int) (($time-$time_counted)/60);
            $time_counted+=$minutes*60;
        $seconds = $time-$time_counted;
        if ($days>0){
            $rT .= $days.' gun ';
        }else if ($hours>0){
            $rT .= $hours.' saat ';
        }else if ($minutes>=0){
            $rT .= $minutes.' deq ';
        }else{
            if ($seconds>0)$rT .= $seconds.' san ';
        }
        $rT .= " evvel";
         *
         */
        return $echo;
    }

    public function findConstellation($birthday)
    {
        $month = substr($birthday, 5, 2);
        $month = (substr($month, 0, 1) == '0') ? substr($month, 1): $month;
        $day = substr($birthday, 8, 2);
        $day = (substr($day, 0, 1) == '0') ? substr($day, 1): $day;
        $burcInterval = array(
            '1' =>20,
            '2' =>18,
            '3' =>20,
            '4' =>20,
            '5' =>21,
            '6' =>21,
            '7' =>22,
            '8' =>23,
            '9' =>23,
            '10'=>23,
            '11'=>22,
            '12'=>21
        );
        $burcContent = array(
            '1'=>oglaq,
            '2'=>dolcha,
            '3'=>baliqlar,
            '4'=>qoch,
            '5'=>buga,
            '6'=>ekizler,
            '7'=>xercheng,
            '8'=>shir,
            '9'=>qiz,
            '10'=>terezi,
            '11'=>eqreb,
            '12'=>oxatan
        );
        if ($day > $burcInterval[$month]){
            $burc=$month+1;
            if ($burc > 12){
                $burc=1;
            }else if ($burc < 1){
                $burc=12;
            }
        }else{
            $burc=$month;
        }
        return $burcContent[$burc];
    }

    public function checkJavascript()
    {
        $keys = ['android', 'ios', 'blackberry', 'chrome', 'mozilla', 'linux', 'windows'];
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $trimmed = str_replace($keys, "", $agent);
        return (strlen($agent) > strlen($trimmed)) ? true: false;
    }

    public function replaceAzLatinSymbols($string, $variation = false)
    {
        $from =  array('Ə', 'ə', 'Ş', 'ş', 'I', 'ı', 'Ü', 'ü', 'Ö', 'ö', 'Ğ', 'ğ', 'Ç', 'ç');
        if(!$variation)
            $to   =  array('E', 'e', '&#350;', '&#351;', '&#304;', '&#305;', '&#220;', '&#252;', '&#214;', '&#246;', '&#286;', '&#287;', '&#199;', '&#231;');
        else
            $to   =  array('E', 'e', 'Sh', 'sh', 'I', 'i', 'U', 'u', 'O', 'o', 'G', 'g', 'Ch', 'ch');

        return str_replace($from, $to, $string);
    }

    public function initCurl($url, $vars_array, $method)
    {
        $ch = curl_init();
        $var_fields = "";
        FOREACH ($vars_array AS $key => $value){
            $var_fields .= $key.'='.urlencode($value).'&';
        }
        IF (strtoupper($method) == "POST") {
            $post_vars = $var_fields;
        }ELSE{
            $get_vars = (strlen($var_fields) > 0) ? "?".$var_fields: "";
            $url .= $get_vars;
        }
        curl_setopt($ch, CURLOPT_URL,$url);
        IF (strtoupper($method) == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(substr($url,0,5) == "https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        }
        $response = curl_exec ($ch);
        if (curl_errno($ch) > 0){
            //exit(curl_error($ch));
        }
        curl_close ($ch);
        return $response;
    }


    public function sendSMS($number, $text)
    {
        $method = "post";
        $url = "https://mm.inmobile.dk/Api/V2/Get/SendMessages?apiKey=95e8ea28-34d7-4400-b44e-7cb7ffe97041&sendername=CareCom&recipients=".$number."&text=".urlencode($text);
        $vars_array = [];


        $ch = curl_init();
        $var_fields = "";
        FOREACH ($vars_array AS $key => $value){
            $var_fields .= $key.'='.urlencode($value).'&';
        }
        IF (strtoupper($method) == "POST") {
            $post_vars = $var_fields;
        }ELSE{
            $get_vars = (strlen($var_fields) > 0) ? "?".$var_fields: "";
            $url .= $get_vars;
        }
        curl_setopt($ch, CURLOPT_URL,$url);
        IF (strtoupper($method) == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars);
        }

        //curl_setopt($ch, CURLOPT_HEADER, 1);
        //curl_setopt($ch, CURLOPT_USERPWD, "AC5fe6a80bd3abaebc5ba62c0e6eb825d3:c5957be8c58cb3522e4e8c428c5856f6");

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(substr($url,0,5) == "https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        }
        $response = curl_exec ($ch);
        if (curl_errno($ch) > 0){
            //exit(curl_error($ch));
        }
        curl_close ($ch);
        return $response;
    }

    public function checkUsername($login)
    {
        if(!preg_match('/^[a-zA-Z0-9]{4,20}+$/i', $login)){
            return false;
        }else if(is_numeric(substr($login,0,1))){
            return false;
        }else{
            return true;
        }
    }

    public function checkDate($d, $m, $y)
    {
        if (isset($d) && isset($m) && isset($y)) {
            if (substr($d,0,1)=='0'){
                $d=substr($d,1);
            }
            if (substr($m,0,1)=='0'){
                $m=substr($m,1);
            }
            if ($d > 0 && $d < 10 ) {
                $day = "0" . $d;
            } else if ($d > 0 && $d < 32) {
                $day = $d;
            } else {
                return false;
            }
            if ($m > 0 && $m < 10) {
                $month = "0" . $m;
            } else if ($m > 0 && $m < 13) {
                $month = $m;
            } else {
                return false;
            }
            if ($y > 1900 && $y < 2013) {
                $year = $y;
            } else {
                return false;
            }
            if (($m == "04" || $m == "06" || $m == "09" || $m == "11") && $d == 31) {
                return false;
            } else if ($m == "02" && $d > 29) {
                return false;
            } else {
                if ($day && $month && $year) {
                    $bd = $year.'-'.$month.'-'.$day;
                    return $bd;
                }
            }
        } else {
            return false;
        }
    }

    public function checkPassword($password)
    {
        if (strlen($password)<6) {
            return false;
        }else if (strlen($password)>20) {
            return false;
        }else{
            return true;
        }
    }

    public function getAge($date)
    {
        $currentTime = time();
        $dateTime = strtotime($date);
        $diff = $currentTime - $dateTime;
        $scale = (int)($diff/365/24/3600);
        return $scale;
    }

    public function showDate($date, $type=0)
    {
        $time = strtotime($date);
        $out = "";
        if (date("Y-m-d") == date("Y-m-d", $time)){
            if ($type == 1) $out .= "bugun ";
            $out .= date("H:i", $time);
        }else if (date("Y-m-d", time() - 24*3600) == date("Y-m-d", $time)){
            $out = "dunen ".date("H:i", $time);
        }else{
            $out = $date;
        }
        return $out;
    }

    public function generatePassword($password)
    {
        return md5("@#$%".sha1($password));
    }

    public function smsSendNew($msisdn, $text, $operator)
    {
        if((int)$operator == 1){
            self::azercellSMS($msisdn, $text);
        }else{
            self::smsSmile($msisdn, $text);
        }
    }

    public function smsSmile($number, $text)
    {
        /*
         * 787 - Bekar.az
         * 851 - Azklub.az
         * 712 azcard
         */
        $url = "http://api.msm.az/sendsms?user=baltaxiapi&password=7BVHWL8K&gsm=".$number."&from=FaytonTaxi&text=".urlencode($text);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        $is_sent = (substr(trim(strip_tags($output)),0,9) == 'errno=100') ? 1: 0;

        $S                  = new MongoSmslog();
        $S->id              = (float)MongoSmslog::getNewId();
        $S->url             = (string)$url;
        $S->destination     = (string)$number;
        $S->response        = trim($output);
        $S->description     = $text;
        $S->is_sent         = (int)$is_sent;
        $S->attempt_count   = 1;
        $S->operator        = 2;
        $S->created_at      = new \MongoDate(time());
        $S->save();

        if($is_sent){
            return true;
        }else{
            return false;
        }
    }

    public function sendSMSua($number, $text)
    {
        return
            self::smsSmile($number, $text);
        /*
         * 787 - Bekar.az
         * 851 - Azklub.az
         * 712 azcard
         */
        $url = "https://api.mobizon.com/service/message/sendsmsmessage?apiKey=3380004fffef993d33aca9318319810b208c7571&recipient=".$number."&text=".urlencode($text);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        $is_sent = (substr(trim(strip_tags($output)),0,2) == 'Ok') ? 1: 0;

        $S                  = new MongoSmslog();
        $S->id              = (float)MongoSmslog::getNewId();
        $S->url             = (string)$url;
        $S->destination     = (string)$number;
        $S->response        = trim($output);
        $S->description     = $text;
        $S->is_sent         = (int)$is_sent;
        $S->attempt_count   = 1;
        $S->operator        = 2;
        $S->created_at      = new \MongoDate(time());
        $S->save();

        if($is_sent){
            return true;
        }else{
            return false;
        }
    }

    public function smsSimtoday($number, $text)
    {
        $url = "http://baltaksi.com/simsms/?msisdn=".$number."&message=".urlencode($text);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        $is_sent = (substr(trim(strip_tags($output)),0,2) == 'Ok') ? 1: 0;

        $S                  = new MongoSmslog();
        $S->id              = (float)MongoSmslog::getNewId();
        $S->destination     = (string)$number;
        $S->response        = trim($output);
        $S->description     = $text;
        $S->is_sent         = (int)$is_sent;
        $S->attempt_count   = 1;
        $S->operator        = 2;
        $S->created_at      = new \MongoDate(time());
        $S->save();

        if($is_sent){
            return true;
        }else{
            return false;
        }
    }

    public function is_number($number)
    {
        if(strlen($number) == 7 &&
            (int)substr($number, 0, 2) > 0 &&
            ((int)substr($number, 0, 2) <= 72 || in_array((int)substr($number, 0, 2), [85,90,99])) &&
            preg_match("/[A-Z]+/", substr($number, 2, 2)) &&
            !preg_match("/[0-9]+/", substr($number, 2, 2)) &&
            is_numeric(substr($number, 4, 3))){
            return true;
        }else if(strlen($number) == 7 &&
            substr($number, 0, 1) == "H" &&
            is_numeric(substr($number, 1))){
            return true;
        }else{
            return false;
        }
    }

    public function is_bike($number){
        if(strlen($number) == 6 &&
            (int)substr($number, 0, 2) > 0 && (int)substr($number, 0, 2) <= 99 &&
            preg_match("/[A-Z]+/", substr($number, 2, 1)) &&
            !preg_match("/[0-9]+/", substr($number, 2, 1)) &&
            is_numeric(substr($number, 3, 3))){
            return true;
        }else{
            return false;
        }
    }

    public function is_tech($number){
        $number = str_replace(["N"],"",$number);
        if(strlen($number) == 8 &&
            preg_match("/[A-Z]+/", substr($number, 0, 2)) &&
            !preg_match("/[0-9]+/", substr($number, 0, 2)) &&
            is_numeric(substr($number, 2))
        ){
            return true;
        }else{
            return false;
        }
    }

    public function secToStr($inputSeconds, $lang)
    {
        $elapse     = time() - $inputSeconds + 7200;

        $months    = (int)($elapse/(30*24*3600));
        $days       = (int)($elapse/(24*3600));
        $hours      = (int)($elapse/3600);
        $minutes    = (int)($elapse/60);
        $date_text  = "";
        if($months > 0){
            $date_text .= $months." month(s) ";
        }else if($days > 0){
            $date_text .= $days." day(s) ";
        }else if($hours > 0){
            $date_text .= $hours." hour(s) ";
        }else if($minutes > 0){
            $date_text .= $minutes." minute(s) ";
        }else{
            $date_text .= $elapse." second(s) ";
        }
        $date_text .= "ago";
        return trim($date_text);
    }

    public function durationToStr($elapse, $lang)
    {
        $months    = (int)($elapse/(30*24*3600));
        $days       = (int)($elapse/(24*3600));
        $hours      = (int)($elapse/3600);
        $minutes    = (int)($elapse/60);
        $date_text  = "";
        if($months > 0){
            $date_text .= $months." month(s) ";
        }else if($days > 0){
            $date_text .= $days." day(s) ";
        }else if($hours > 0){
            $date_text .= $hours." hour(s) ";
        }else if($minutes > 0){
            $date_text .= $minutes." minute(s) ";
        }else{
            $date_text .= $elapse." second(s) ";
        }
        $date_text .= "";
        return trim($date_text);
    }


    public function hmac($key, $data)
    {
        $b = 64;
        if (strlen($key) > $b){
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack("H*",md5($k_ipad . $data)));
    }

    public function getDateRange($type, $ranges=false)
    {
        switch($type)
        {
            default:
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", time())))
                ];
                break;
            case "today":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", time())))
                ];
                break;
            case "yesterday":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", time() - 24 * 3600))),
                    '$lt' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", time()))),
                ];
                break;
            case "24hour":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", time() - 24 * 3600))),
                ];
                break;
            case "this_week":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", strtotime("last Monday")))),
                ];
                break;
            case "last_week":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", strtotime("last Monday") - 7 * 24 * 3600))),
                    '$lt' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", strtotime("last Monday")))),
                ];
                break;
            case "this_month":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-01 00:00:00", time())))
                ];
                break;
            case "30days":
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date("Y-m-d 00:00:00", time() - 30 * 24 * 3600)))
                ];
                break;
            case "last_month":
                $year 	= (int)date("Y");
                $month 	= (int)date("m");
                $month 	-= 1;
                if($month<1)
                {
                    $year 	-= 1;
                    $month 	= 12;
                }
                if($month < 10)
                    $month = "0".$month;
                $dateBind = [
                    '$gte' => new \MongoDate(strtotime(date($year."-".$month."-01 00:00:00", time()))),
                    '$lt' => new \MongoDate(strtotime(date("Y-m-01 00:00:00", time()))),
                ];
                break;
        }
        return $dateBind;
    }

    public function jsonPrettyPrint(&$j, $indentor = "\t", $indent = "") {
        $inString = $escaped = false;
        $result = $indent;

        if(is_string($j)) {
            $bak = $j;
            $j = str_split(trim($j, '"'));
        }

        while(count($j)) {
            $c = array_shift($j);
            if(false !== strpos("{[,]}", $c)) {
                if($inString) {
                    $result .= $c;
                } else if($c == '{' || $c == '[') {
                    $result .= $c."\n";
                    $result .= $this->jsonPrettyPrint($j, $indentor, $indentor.$indent);
                    $result .= $indent.array_shift($j);
                } else if($c == '}' || $c == ']') {
                    array_unshift($j, $c);
                    $result .= "\n";
                    return $result;
                } else {
                    $result .= $c."\n".$indent;
                }
            } else {
                $result .= $c;
                $c == '"' && !$escaped && $inString = !$inString;
                $escaped = $c == '\\' ? !$escaped : false;
            }
        }

        $j = $bak;
        return $result;
    }

    public function findAgeByDate($birthDate)
    {
        if(strtotime($birthDate) > 0){
            $from = new \DateTime($birthDate);
            $to   = new \DateTime('today');
            return $from->diff($to)->y;
        }
        return 0;
    }

    public function getBirthdateFromSSN($ssn)
    {
        $birthYear  = substr($ssn, 4, 2);
        $birthYear  = (int)$birthYear > date("y") ? "19".$birthYear: "20".$birthYear;
        $birthMonth = substr($ssn, 2, 2);
        $birthDay   = substr($ssn, 0, 2);
        $birthDate  = $birthYear."-".$birthMonth."-".$birthDay;
        return $birthDate;
    }

    public function floatToDanish($amount, $round=-1)
    {
        if($round >= 0){
            $amount = (string)round($amount, $round);
        }else{
            $amount = (string)$amount;
        }
        $amount = str_replace(".", ",", $amount);
        return $amount;
    }

    public function danishToFloat($amount)
    {
        $amount = (float)str_replace(",", ".", $amount);
        return $amount;
    }


    public function dateToDanish($date)
    {
        if(strlen($date) < 2)
            return "";
        $time = strtotime($date." 00:00:00");
        return date("d-m-Y", $time);
    }


    public function dateFomDanish($date)
    {
        if(strlen($date) < 2)
            return "";
        $date = substr($date, 6, 4)."-".substr($date, 3, 2)."-".substr($date, 0, 2);
        return $date;
    }

    public function getIp()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function xss_clean($data)
    {
// Fix &entity\n;
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

// Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

// Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

// Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do
        {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        }
        while ($old_data !== $data);

// we are done...
        return $data;
    }




    public function getAddress($latitude, $longitude){
        $urlParams 			= "latlng=".$latitude.",".$longitude;
        $urlParams 			.= "&language=da";
        $urlParams 			.= "&key=".GOOGLE_API_KEY;
        $url 				= "https://maps.googleapis.com/maps/api/geocode/json?".$urlParams;

        $google_response = Lib::initCurl($url, [], "get");
        //var_dump($google_response);exit;
        $google_response = json_decode($google_response, true);

        $data = false;
        if(count($google_response["results"]) > 0)
        {
            list($startName, $startState) = $this->getNameAndStateByFormattedAddress($google_response["results"][0]["formatted_address"]);
            $data = [
                "name"		=> $startName,
                "state"		=> $startState,
                "place_id"	=> false, //$google_response["results"][0]["place_id"],
                "location" => [
                    "latitude"	=> (float)$latitude,
                    "longitude"	=> (float)$longitude
                ]
            ];
        }

        return $data;
    }


    public function getNameAndStateByFormattedAddress($address)
    {
        $formattedAddress = explode(",", $address);
        if(count($formattedAddress) == 5){
            $addressName 	= $formattedAddress[0].", ".$formattedAddress[1].", ".$formattedAddress[2];
            $addressState 	= $formattedAddress[3].", ".$formattedAddress[4];
        }elseif(count($formattedAddress) == 4){
            $addressName 	= $formattedAddress[0].", ".$formattedAddress[1];
            $addressState 	= $formattedAddress[2].", ".$formattedAddress[3];
        }elseif(count($formattedAddress) == 3){
            $addressName 	= $formattedAddress[0];
            $addressState 	= $formattedAddress[1].", ".$formattedAddress[2];
        }elseif(count($formattedAddress) == 2){
            $addressName 	= $formattedAddress[0];
            $addressState 	= $formattedAddress[1];
        }else{
            $addressName 	= $address;
            $addressState 	= $address;
        }
        $addressName = trim(str_replace("  ", " ",$addressName));
        $addressState = trim(str_replace("  ", " ",$addressState));
        return [$addressName, $addressState];
    }
}
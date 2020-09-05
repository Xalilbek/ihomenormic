<?php
namespace Lib;

use Models\Tokens;
use Models\Cache;
use Models\Users;

class Auth
{
    public $data;

    public $error = false;

    public $errorCode = 0;

    public $cacheSeconds = 10;

    public function init($request, $lang)
    {
        $data = false;
        if($request->get("token"))
        {
            $token = Tokens::findFirst([
                [
                    "token"     => trim($request->get("token")),
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
            $this->setData($data);
        return $data;
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




    public function filterData($data, $lang)
    {
        $filtered = false;

        $filtered = [
            "id"			=> $data->id,
            "firstname"		=> (string)$data->firstname,
            "lastname"		=> (string)$data->lastname,
            "phone"			=> (string)$data->phone,
            "email"			=> (string)$data->email,
            "ssn"			=> (string)$data->ssn,
            "place"			=> (string)$data->place,
            "address"		=> (string)$data->address,
            "zipcode"		=> (string)$data->zipcode,
            "city"			=> (int)$data->city,
            "type"			=> "citizen",
            "gender"		=> [
                "value"         => $data->gender == "male" ? "male": "female",
                "text"	        => $data->gender == "male" ? $lang->get("Male"): $lang->get("Female"),
            ],
        ];

        if($data->type == "citizen")
        {

        }
        elseif($data->type == "employee")
        {
            $filtered = [
                "id"			                => $data->id,
                "avatar"                        => $this->getAvatar($data),
                "firstname"		                => $data->firstname,
                "lastname"		                => $data->lastname,
                "phone"			                => $data->phone,
                "email"			                => $data->email,
                "gender"		                => [
                    "value"         => $data->gender == "male" ? "male": "female",
                    "text"	        => $data->gender == "male" ? $lang->get("Male"): $lang->get("Female"),
                ],
                "languages"		                => count($data->languages) > 0 ? $data->languages: [],
                /**
                "ssn"			                => $data->ssn,
                "address"		                => (string)$data->address,
                "zipcode"		                => (string)$data->zipcode,
                "city"			                => (int)$data->city,
                "payment_registration_number"	=> (string)$data->payment_registration_number,
                "payment_account_number"		=> (string)$data->payment_account_number, */
                "type"			                => "employee",
            ];
        }
        else
        {
            $filtered = [
                "id"			=> $data->id,
                "firstname"		=> (string)$data->firstname,
                "lastname"		=> (string)$data->lastname,
                "phone"			=> (string)$data->phone,
                "email"			=> (string)$data->email,
                "gender"		=> [
                    "value"         => $data->gender == "male" ? "male": "female",
                    "text"	        => $data->gender == "male" ? $lang->get("Male"): $lang->get("Female"),
                ],
            ];
        }
        return $filtered;
    }

    public function getAvatar($data)
    {
        return ($data->avatar_id) ? [
            "small" => FILE_URL."/upload/".(string)$data->_id."/".(string)$data->avatar_id."/small.jpg",
            "large" => FILE_URL."/upload/".(string)$data->_id."/".(string)$data->avatar_id."/medium.jpg",
        ]:
            [
                "small" => "http://placehold.it/80x80",
                "large" => "http://placehold.it/80x80",
            ];
    }


    public function setData($data)
    {
        return $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
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
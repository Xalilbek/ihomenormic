<?php

namespace Controllers;

use Lib\Image;
use Lib\MyMongo;
use Models\LogsAccess;
use Models\TempFiles;
use Models\Users;

class FilesController extends \Phalcon\Mvc\Controller
{
    public function uploadAction(){
        $error      = false;
        $success    = false;

        $file       = @$_FILES['file'];
        $filetype   = explode(".",$file["name"]);
        $filetype   = strtolower($filetype[count($filetype)-1]);
        $filename   = substr($file["name"], 0, (strlen($file["name"])-strlen($filetype)-1));
        $filename   = str_replace([' ', '-'], '_', trim($filename));
        $filename   = preg_replace('/[^A-Za-z0-9\_]/', '', $filename);
        if(strlen($filename) < 1)
            $filename = rand(1, 99999999);

        $for        = strtolower($this->request->get("for"));

        $this->auth->init($this->request, $this->lang);
        $object = $this->auth->getData();
        if($object)
        {
            $uuid   = (string)$object->_id;
        }
        else
        {
            $uuid   = md5($this->request->getServer("REMOTE_ADDR")."-".microtime(true));
        }

        if (!@$file["tmp_name"])
        {
            $error = $this->lang->get("YouDidntChoosePhoto", "You didn`t choose photo");
        }
        else if (!in_array($for, ["profile", "idscan", "case", "note", "calendar"]))
        {
            $error = "Parameter for is empty";
        }
        else if (!in_array(strtolower($filetype), array("jpeg", "jpg", "png", "gif", "pdf", "doc","docx", "xls", "xlsx")))
        {
            $error = $this->lang->get("FileTypeNotAllowed", "This file type is not allowed");
        }
        else if ($file['size'] > 30*1024*1024)
        {
            $error = $this->lang->get("FileSizeMaxLimit", "File size cann`t be larger than 30 MB");
        }
        else
        {
            $dir    = FILE_DIR.$uuid.'/';
            if (!is_dir($dir))
            {
                mkdir($dir,0777);
                chmod($dir, 0777);
            }

            $insert = [
                "user_id"           => ($object) ? (int)$object->id: 0,
                "uuid"              => $uuid,
                "type"              => $filetype,
                "size"          	=> $file['size'],
                "filename"          => $filename,
                "realname"          => $file["name"],
                "for"               => $for,
                "active"            => 1,
                "created_at"        => TempFiles::getDate(),
            ];
            $insertId = (string)TempFiles::insert($insert);

            $dir2 = FILE_DIR.$uuid.'/'.$insertId.'/';
            if (!is_dir($dir2))
            {
                mkdir($dir2,0777,true);
                chmod($dir2, 0777);
            }
            chmod($file["tmp_name"], 0777);

            if (in_array(strtolower($filetype), array("jpeg", "jpg", "png", "gif")))
            {
                list($width, $height) = getimagesize($file["tmp_name"]);
                if($width > 0)
                {
                    Image::resize($file["tmp_name"], $width, $height, $dir2.'org.jpg', 100);
                    Image::resize($file["tmp_name"], 300, 300, $dir2.'medium.jpg', 90);
                    Image::resize($file["tmp_name"], 100, 100, $dir2.'small.jpg', 90);

                    @chmod($dir2.'org.jpg', 0777);
                    @chmod($dir2.'medium.jpg', 0777);
                    @chmod($dir2.'small.jpg', 0777);

                    $response = [
                        "status"        => "success",
                        "description"   => $this->lang->get("UploadedSuccessfully", "Uploaded successfully"),
                        "file_id"       => (string)$insertId,
                        "avatar"        => FILE_URL.'/upload/'.$uuid.'/'.$insertId.'/small.jpg'
                    ];
                }
                else
                {
                    $error = $this->lang->get("FileTypeIsWrong", "Image type is wrong. Accepted types: JPEG, PNG, GIF");
                }
            }
            else
            {
                move_uploaded_file($file["tmp_name"], $dir2.$filename.".".$filetype);
                $response = [
                    "status"        => "success",
                    "description"   => "..",
                ];
            }
        }

        $vars               = $_REQUEST;
        unset($vars["_url"]);

        $insert = [
            "user_id"       => ($this->auth->getData()) ? (int)$this->auth->getData()->id: 0,
            "url"           => @$_SERVER["REQUEST_URI"],
            "ip"            => @$_SERVER["REMOTE_ADDR"],
            "browser"       => @$_SERVER["HTTP_USER_AGENT"],
            "filetype"       => $filetype,
            "variables"     => strlen(json_encode($vars, true)) > 1000 ? substr(json_encode($vars, true),0,1000): $vars,
            "file"          => $file,
            "response"          => $response,
            //"headers"       => strlen(json_encode($_SERVER, true)) > 2000 ? substr(json_encode($_SERVER, true),0,2000): $_SERVER,
            "created_at"    => MyMongo::getDate(),
        ];
        LogsAccess::insert($insert);

        if($error)
        {
            $response = [
                "status" 			=> "error",
                "err_code" 			=> 2501,
                "description" 		=> $error,
            ];
        }
        echo json_encode($response, true);
        exit();
    }

    public function deleteAction($photo_id)
    {
        $error = false;
        $success = false;

        $photo = MongoPhotos::getById($photo_id);

        if(!$photo){
            $error = $this->lang->get("PhotoDoesntExist", "Photo doesnt exists");
        }elseif((float)$photo->user_id !== (float)MongoUsers::$id) {
            $error = "You don`t have access delete this photo";
        }elseif((float)$photo->active == 0) {
            $error = $this->lang->get("PhotoAlreadyDeleted", "Photo already has been deleted");
        }else{
            $photo->active = 0;
            $photo->is_avatar = 0;
            $photo->save();

            $object = MongoObjects::getById($photo->object_id);
            if($object){
                $object->avatar_id = 0;

                $new_avatar = MongoPhotos::findFirst([
                   [
                       "object_id"  => (float)$photo->object_id,
                       "active"     => 1
                   ]
                ]);
                if($new_avatar){
                    $new_avatar->is_avatar = 1;
                    $new_avatar->save();

                    $object->avatar_id = (float)$new_avatar->id;
                }
                $object->save();
            }

            $success = $this->lang->get("DeletedSuccessfully", "DeletedSuccessfully");
            $response = [
                "status" 			=> "success",
                "description" 		=> $success,
            ];
        }
        if($error){
            $response = [
                "status" 			=> "error",
                "err_code" 			=> 1421,
                "description" 		=> $error,
            ];
        }
        echo json_encode($response, true);
        exit();
    }

    public function makeavatarAction($photo_id)
    {
        $error = false;
        $success = false;

        $photo = MongoPhotos::getById($photo_id);

        if(!$photo){
            $error = "Photo doesnt exists";
        }elseif((float)$photo->user_id !== (float)MongoUsers::$id) {
            $error = "You don`t have access delete this photo";
        }elseif((float)$photo->active == 0) {
            $error = "Photo has been deleted";
        }elseif((float)$photo->is_avatar == 1) {
            $error = "This photo currently is avatar";
        }else{
            $photo->is_avatar = 1;
            $photo->save();

            $object = MongoObjects::getById($photo->object_id);
            if($object){
                $object->avatar_id = (float)$photo_id;
                $object->save();

                $old_avatar = MongoPhotos::findFirst([
                   [
                       "object_id"  => (float)$photo->object_id,
                       "active"     => 1,
                       "is_avatar"  => 1,
                       "id"         => [
                           '$ne'    => (float)$photo_id
                       ]
                   ]
                ]);
                if($old_avatar){
                    $old_avatar->is_avatar = 0;
                    $old_avatar->save();
                }
            }

            $success = "Maked avatar";
            $response = [
                "status" 			=> "success",
                "description" 		=> $success,
            ];
        }
        if($error){
            $response = [
                "status" 			=> "error",
                "err_code" 			=> 1421,
                "description" 		=> $error,
            ];
        }
        echo json_encode($response, true);
        exit();
    }

    public function listAction($photo_id)
    {
        $error = false;
        $success = false;

        $photo = MongoPhotos::getById($photo_id);

        if(!$photo){
            $error = "Photo doesnt exists";
        }elseif((float)$photo->user_id !== (float)MongoUsers::$id) {
            $error = "You don`t have access delete this photo";
        }elseif((float)$photo->active == 0) {
            $error = "Photo has been deleted";
        }elseif((float)$photo->is_avatar == 1) {
            $error = "This photo currently is avatar";
        }else{
            $photo->is_avatar = 1;
            $photo->save();

            $object = MongoObjects::getById($photo->object_id);
            if($object){
                $object->avatar_id = (float)$photo_id;
                $object->save();

                $old_avatar = MongoPhotos::findFirst([
                   [
                       "object_id"  => (float)$photo->object_id,
                       "active"     => 1,
                       "is_avatar"  => 1,
                       "id"         => [
                           '$ne'    => (float)$photo_id
                       ]
                   ]
                ]);
                if($old_avatar){
                    $old_avatar->is_avatar = 0;
                    $old_avatar->save();
                }
            }

            $success = "Maked avatar";
            $response = [
                "status" 			=> "success",
                "description" 		=> $success,
            ];
        }
        if($error){
            $response = [
                "status" 			=> "error",
                "err_code" 			=> 1421,
                "description" 		=> $error,
            ];
        }
        echo json_encode($response, true);
        exit();
    }
}
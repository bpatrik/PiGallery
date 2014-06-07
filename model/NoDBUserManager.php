<?php

namespace piGallery\model;

require_once __DIR__."/../config.php";
require_once __DIR__."/../db/entities/User.php";

use piGallery\db\entities\User;
use piGallery\Properties;

class NoDBUserManager {


    public static function login($userName, $password){
        foreach(Properties::$users as &$value){
            if($value['userName'] == $userName && $value['password'] == $password){
                $user = new User($userName, $password, $value["role"]);
                $user->setSessionID(md5($userName.$password));
                return $user;
            }
        }

        return null;
    }

    public static function loginWithSessionID($sessionID){
        foreach(Properties::$users as &$value){
            if(md5($value['userName'].$value['password']) == $sessionID){
                $user = new User($value['userName'], $value['password'], $value["role"]);
                $user->setSessionID($sessionID);
                return $user;
            }
        }

        return null;
    }

} 
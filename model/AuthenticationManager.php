<?php

namespace piGallery\model;

require_once __DIR__."./../db/entities/Role.php";
require_once __DIR__."./UserManager.php";

use piGallery\db\entities\Role;
use piGallery\Properties;

class AuthenticationManager {

    /**
     * Try to authenticate toe use, if it fails, stops the execution and show error message to the user
     * @param int $roleNeeded
     * @return null|\piGallery\db\entities\User
     */
    public static function authenticate($roleNeeded = Role::User){
        /*Checking session id*/
        if(isset($_COOKIE["pigallery-sessionid"]) && !empty($_COOKIE["pigallery-sessionid"])){

            $sessionID = $_COOKIE["pigallery-sessionid"];

            if(Properties::$databaseEnabled){ //Using database enabled?

                return null;

            }else{//No-database mode

                $user = UserManager::loginWithSessionID($sessionID);
                if($user != null && $user->getRole() >= $roleNeeded){
                    return $user;
                }

            }

        }

        /*Authentication failed*/
        header('Forbidden', true, 403);
        die("<h1>Please login...</h1>");



    }

} 
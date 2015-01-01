<?php

namespace piGallery\model;

require_once __DIR__."/../config.php";
require_once __DIR__."/../db/entities/Role.php";
require_once __DIR__."/../db/DB_UserManager.php";
require_once __DIR__."/NoDBUserManager.php";

use piGallery\db\DB_UserManager;
use piGallery\db\entities\Role;
use piGallery\db\entities\User;
use piGallery\Properties;
require_once __DIR__."/../lang/".Properties::$language.".php";

class AuthenticationManager {

    /**
     * Try to authenticate toe use, if it fails, stops the execution and show error message to the user
     * @param int $roleNeeded
     * @return \piGallery\db\entities\User
     */
    public static function authenticate($roleNeeded = Role::Guest){
        global $LANG;

        $user = null;
        if($roleNeeded <= Role::Guest && Helper::isClientInSameSubnet() === TRUE && Properties::$GuestLoginAtLocalNetworkEnabled === TRUE){
            $user = new User($LANG['Guest'],null,Role::Guest);
        }

        /*Checking session id*/
        $tmp_user = null;
        if(isset($_COOKIE["pigallery-sessionid"]) && !empty($_COOKIE["pigallery-sessionid"])){

            $sessionID = $_COOKIE["pigallery-sessionid"];

            if(Properties::$databaseEnabled){ //Using database enabled?

                $tmp_user = DB_UserManager::loginWithSessionID($sessionID);
                if($user != null && $user->getRole() >= $roleNeeded){
                    return $user;
                }

            }else{//No-database mode

                $tmp_user = NoDBUserManager::loginWithSessionID($sessionID);
                if($user != null && $user->getRole() >= $roleNeeded){
                    return $user;
                }

            }

        }

        return is_null($tmp_user) ? $user :  $tmp_user;

    }

} 
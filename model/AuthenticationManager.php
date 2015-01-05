<?php

namespace piGallery\model;

require_once __DIR__."/../config.php";
require_once __DIR__."/../db/entities/Role.php";
require_once __DIR__."/../db/DB_UserManager.php";
require_once __DIR__."/NoDBUserManager.php";
require_once __DIR__."/Helper.php";

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
    public static function authenticate($roleNeeded = Role::RemoteGuest){
        global $LANG;

        //Try to login normally
        /*Checking session id*/
        if(isset($_COOKIE["pigallery-sessionid"]) && !empty($_COOKIE["pigallery-sessionid"])){

            $sessionID = $_COOKIE["pigallery-sessionid"];

            if(Properties::$databaseEnabled){ //Using database enabled?

                $user = DB_UserManager::loginWithSessionID($sessionID);
                if(!is_null($user) && $user->getRole() >= $roleNeeded){
                    return $user;
                }

            }else{//No-database mode

                $user = NoDBUserManager::loginWithSessionID($sessionID);
                if(!is_null($user) && $user->getRole() >= $roleNeeded){
                    return $user;
                }

            }

        }

        //Login as guest user at localnetwork
        if ($roleNeeded <= Role::LocalGuest && Helper::isClientInSameSubnet() === TRUE && Properties::$GuestLoginAtLocalNetworkEnabled === TRUE) {
            return new User($LANG['guest'], null, Role::LocalGuest);
        }

        //login as guest with link (share link)
        if (Properties::$databaseEnabled  && $roleNeeded <= Role::RemoteGuest) { //if a local network guest: no extra login needed
            if (isset($_REQUEST["s"]) && !empty($_REQUEST["s"])) {
                return DB_UserManager::loginWithShareLink($_REQUEST["s"]);
            }
        }
       

        return null;

    }

} 
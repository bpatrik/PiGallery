<?php

namespace piGallery\model;


/*LOGIC*/
require_once __DIR__."/../db/DB.php";
require_once __DIR__."/../db/DB_ContentManager.php";
require_once __DIR__."/Helper.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/DirectoryScanner.php";
require_once __DIR__."/../db/entities/AjaxError.php";
require_once __DIR__."/NoDBUserManager.php";

use piGallery\db\DB;
use piGallery\db\DB_ContentManager;
use piGallery\db\DB_UserManager;
use piGallery\db\entities\AjaxError;
use piGallery\db\entities\User;
use piGallery\Properties;
use piGallery\db\entities\Role;

/* empty string set as null*/
foreach ($_REQUEST as $key => $value){
    if($value == ""){
        unset($_REQUEST[$key]);
    }
}

/**
 * @param int $role
 * @return null|User
 */
function authenticate($role = Role::User) {

    /*Authenticating*/
    require_once __DIR__."/AuthenticationManager.php";
    require_once __DIR__."/../db/entities/Role.php";

    /*Authentication need for images*/
    $user = AuthenticationManager::authenticate($role);
    if(is_null($user)){
        die(json_encode(array("error" => (new AjaxError(AjaxError::AUTHENTICATION_FAIL, "Authentication failed"))->getJsonData(), "data" => "")));
    }
    return $user;
}


switch (Helper::require_REQUEST('method')) {

    case 'getContent':
        $user = authenticate(Role::RemoteGuest);
        $error = null;
        $data = null;

        $dir = Helper::require_REQUEST('dir');
        if (Properties::$enableUTF8Encode) {
            $dir = utf8_decode($dir);
        }
        $dir = Helper::toDirectoryPath($dir);
        $lastModificationDate = Helper::get_REQUEST('lastModificationDate',null);
        if($lastModificationDate == "null")
            $lastModificationDate = null;

        try {
            if (Properties::$databaseEnabled) {
                $data = DB_ContentManager::getDirectoryContent($dir, $lastModificationDate);
            } else {
                $data = DirectoryScanner::getDirectoryContent($dir);
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }
        
        if($user->getPathRestriction() != null && $user->getPathRestriction()->isRecursive() === FALSE){
            $data['directories'] = array();
        }
        
        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => Helper::contentArrayToJSONable($data))));
        break;

    case 'indexDirectoryAndGetContent':
        authenticate(Role::RemoteGuest);
        $error = null;
        $data = null;
        if(Properties::$enableOnTheFlyIndexing){

            $dir = Helper::require_REQUEST('dir');
            if (Properties::$enableUTF8Encode) {
                $dir = utf8_decode($dir);
            }
            $dir = Helper::toDirectoryPath($dir);

            try {
                if(Properties::$databaseEnabled){
                    DB::indexDirectory($dir);
                    $data = DB_ContentManager::getDirectoryContent($dir, null);
                }else{
                    $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: IndexDirectoryAndGetContent function in no-db mode  not supported");
                }
            }catch(\Exception $ex){
                $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
            }


        }else{
            $error = new AjaxError(AjaxError::GENERAL_ERROR, "On the fly indexing is not enabled");
        }


        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => Helper::contentArrayToJSONable($data))));
        break;

    case 'autoComplete':

        authenticate(Role::LocalGuest);
        $count= intval(Helper::get_REQUEST('count',5));
        $searchText= Helper::require_REQUEST('searchText');

        $error = null;
        $data = null;
        try {
            if(Properties::$databaseEnabled){
                $data = DB_ContentManager::getAutoComplete($searchText,$count);
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Auto complete in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));
        break;

    case 'search':
        authenticate(Role::LocalGuest);

        $error = null;
        $data = null;

        $searchString = Helper::require_REQUEST('searchString');

        try {
            if(Properties::$databaseEnabled){
                $data = DB_ContentManager::getSearchResult($searchString);
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Search in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => Helper::contentArrayToJSONable($data))));
        break;
    
    case 'share':
        $user = authenticate(Role::User);
        $error = null;
        $data = null;

        $dir = Helper::require_REQUEST('dir');
        $currentShareId = Helper::get_REQUEST('currentShareId',null);
        $isRecursive = filter_var( Helper::get_REQUEST('isRecursive',false),FILTER_VALIDATE_BOOLEAN);
        $validInterval = intval(Helper::get_REQUEST('validInterval',24 * 30)); //default 30 days
        
        
        try {
            if(Properties::$databaseEnabled){
                $shareId = DB_ContentManager::shareFolder($user, $dir, $validInterval, $isRecursive, $currentShareId);
                $data = array("link" => Properties::$siteUrl.'?s='.$shareId,
                              "shareId" => $shareId,
                              "path" => $dir,
                              "validInterval" => $validInterval,
                              "isRecursive" => $isRecursive );
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Share in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));

        break;
    
    case 'login':
        $userName =  Helper::require_REQUEST('userName');
        $password = Helper::require_REQUEST('password');
        $rememberMe = filter_var(Helper::get_REQUEST('rememberMe',"false"), FILTER_VALIDATE_BOOLEAN);

        $error = null;
        $data = null;
        try {
            if (Properties::$databaseEnabled) {
                $user = DB_UserManager::login($userName, $password,$rememberMe);
                if ($user != null) {
                    $user->setPassword(null);
                    if($user->getPathRestriction() != null)
                      $user->getPathRestriction()->setPath(Helper::toURLPath($user->getPathRestriction()->getPath()));
                    $data = $user->getJsonData();
                } else {
                    $error = new AjaxError(AjaxError::AUTHENTICATION_FAIL, "Wrong user name or password");
                }
            } else {
                $user = NoDBUserManager::login($userName, $password);
                if ($user != null) {
                    $user->setPassword(null);
                    if($user->getPathRestriction() != null)
                        $user->getPathRestriction()->setPath(Helper::toURLPath($user->getPathRestriction()->getPath()));
                    $data = $user->getJsonData();
                } else {
                    $error = new AjaxError(AjaxError::AUTHENTICATION_FAIL, "Wrong user name or password");
                }
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));

        break;
    case 'logout':
        $user = authenticate(Role::User);
        $sessionID =  Helper::require_REQUEST('sessionID');
        $error = null;
        $data = null;
        try {
            if(Properties::$databaseEnabled){
                $data = DB_UserManager::logout($user->getId(), $sessionID);
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" =>$data)));
        break;


/*-------------ADMIN methods--------------*/

    case 'recreateDatabase':
        authenticate(Role::Admin);

        $error = null;
        $data = null;

        try {
            if(Properties::$databaseEnabled){
                $data = DB::recreateDatabase();
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Recreate DB table in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" =>$data)));
        break;

    case 'clearGalleryDatabase':
        authenticate(Role::Admin);
        $error = null;
        $data = null;

        try {
            if(Properties::$databaseEnabled){
                $data = DB::clearDatabase();
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Creating gallery database in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" =>$data)));
        break;
    case 'indexDirectory':
        authenticate(Role::Admin);
        $error = null;
        $data = null;
        $dir = Helper::require_REQUEST('dir');
        if (Properties::$enableUTF8Encode) {
            $dir = utf8_decode($dir);
        }
        $dir = Helper::toDirectoryPath($dir);

        try {
            if(Properties::$databaseEnabled){
               $data = DB::indexDirectory($dir);
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Directory indexing in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }

        if($data != null){
            for($i = 0; $i < count($data['foundDirectories']); $i++){
                if (Properties::$enableUTF8Encode) {
                    $data['foundDirectories'][$i] = utf8_encode($data['foundDirectories'][$i]);
                }
            }
        }

        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" =>$data)));
        break;



    case 'getUsersList':
        authenticate(Role::Admin);
        $error = null;
        $data = null;
        try {
            if(Properties::$databaseEnabled){
                $data = DB_UserManager::getUsersList();
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Getting User list from db in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }
        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => Helper::phpObjectArrayToJSONable($data))));
        break;

    case 'registerUser':
        authenticate(Role::Admin);

        $error = null;
        $data = null;
        $userName = Helper::require_REQUEST('userName');
        $password = Helper::require_REQUEST('password');
        $role = filter_var(Helper::get_REQUEST('role',0), FILTER_VALIDATE_INT);
        try {
            if(Properties::$databaseEnabled){
                $data = DB_UserManager::register(new User($userName, $password, $role));
            }else{
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Registering user in no-db mode not supported");
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }
        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));
        break;
    case 'deleteUser':
        $user = authenticate(Role::Admin);

        $error = null;
        $data = null;
        $id = Helper::require_REQUEST('id');
        if ($user->getId() == $id){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, "You cant delete yourself!");
        }else{
            try {
                if(Properties::$databaseEnabled){
                    $data = DB_UserManager::deleteUser($id);
                }else{
                    $error = new AjaxError(AjaxError::GENERAL_ERROR,"Error: Deleting in no-db mode not supported");
                }
            }catch(\Exception $ex){
                $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
            }
        }
        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));

        break;
}

<?php

namespace piGallery\model;


/*LOGIC*/
require_once __DIR__."/../db/DB.php";
require_once __DIR__."/../db/DB_ContentManager.php";
require_once __DIR__."/Helper.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/DirectoryScanner.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/NoDBUserManager.php";

use piGallery\db\DB;
use piGallery\db\DB_ContentManager;
use piGallery\db\DB_UserManager;
use piGallery\db\entities\User;
use piGallery\Properties;
use piGallery\db\entities\Role;
use piGallery\model\AuthenticationManager;

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
    return AuthenticationManager::authenticate($role);
}


switch (Helper::require_REQUEST('method')) {

    case 'getContent':
        authenticate();

        $dir = Helper::require_REQUEST('dir');
        if (Properties::$enableUTF8Encode) {
            $dir = utf8_decode($dir);
        }
        $dir = Helper::toDirectoryPath($dir);

        $error = null;
        $data = null;
        try {
            if (Properties::$databaseEnabled) {
                $data = DB_ContentManager::getDirectoryContent($dir);
            } else {
                $data = DirectoryScanner::getDirectoryContent($dir);
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

        die(json_encode(array("error" => $error, "data" => Helper::contentArrayToJSONable($data))));
        break;

    case 'autoComplete':

        authenticate();
        $count= intval(Helper::get_REQUEST('count',5));
        $searchText= Helper::require_REQUEST('searchText');

        $error = null;
        $data = null;
        try {
            if(Properties::$databaseEnabled){
                $data = DB_ContentManager::getAutoComplete($searchText,$count);
            }else{
                die("Error: not supported");
            }
        }catch(\Exception $ex){
                $error = utf8_encode($ex->getMessage());
        }

        foreach($data as &$item){
            $item['text'] = (Helper::toURLPath($item['text']));
            $item['text'] = (utf8_encode($item['text']));
        }
        die(json_encode(array("error" => $error, "data" => $data)));
        break;

    case 'search':
        authenticate();

        $error = null;
        $data = null;

        $searchString = Helper::require_REQUEST('searchString');

        try {
            if(Properties::$databaseEnabled){
                $data = DB_ContentManager::getSearchResult($searchString);
            }else{
                die("Error: not supported");
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

        die(json_encode(array("error" => $error, "data" => Helper::contentArrayToJSONable($data))));
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
                    $data = $user->getJsonData();
                } else {
                    $error = "Wrong user name or password";
                }
            } else {
                $user = NoDBUserManager::login($userName, $password);
                if ($user != null) {
                    $user->setPassword(null);
                    $data = $user->getJsonData();
                } else {
                    $error = "Wrong user name or password";
                }
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

        die(json_encode(array("error" => $error, "data" => $data)));

        break;
    case 'logout':
        $user = authenticate();
        $sessionID =  Helper::require_REQUEST('sessionID');
        $error = null;
        $data = null;
        try {
            if(Properties::$databaseEnabled){
                $data = DB_UserManager::logout($user->getId(), $sessionID);
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

        die(json_encode(array("error" => $error, "data" =>$data)));
        break;


/*-------------ADMIN methodes--------------*/

    case 'recreateDatabase':
        authenticate(Role::Admin);

        $error = null;
        $data = null;

        try {
            if(Properties::$databaseEnabled){
                $data = DB::recreateDatabase();
            }else{
                $error =  "Error: not supported";
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

        die(json_encode(array("error" => $error, "data" =>$data)));
        break;

    case 'clearGalleryDatabase':
        authenticate(Role::Admin);
        $error = null;
        $data = null;

        try {
            if(Properties::$databaseEnabled){
                $data = DB::clearDatabase();
            }else{
                $error =  "Error: not supported";
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

        die(json_encode(array("error" => $error, "data" =>$data)));
        break;
    case 'indexDirectory':
        authenticate(Role::Admin);
        $error = null;
        $data = null;
        $dir = Helper::toDirectoryPath(utf8_decode(Helper::require_REQUEST('dir')));
        try {
            if(Properties::$databaseEnabled){
               $data = DB::indexDirectory($dir);
            }else{
               $error = "Error: not supported";
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }
        for($i = 0; $i < count($data['foundDirectories']); $i++){
            $data['foundDirectories'][$i] = utf8_encode($data['foundDirectories'][$i] );
        }

        die(json_encode(array("error" => $error, "data" =>$data)));
        break;



    case 'getUsersList':
        authenticate(Role::Admin);
        $error = null;
        $data = null;
        try {
            if(Properties::$databaseEnabled){
                $data = DB_UserManager::getUsersList();
            }else{
                $error = "Error: not supported";
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }
        die(json_encode(array("error" => $error, "data" => Helper::phpObjectArrayToJSONable($data))));
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
                $error = "Error: not supported";
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }
        die(json_encode(array("error" => $error, "data" => $data)));
        break;
    case 'deleteUser':
        $user = authenticate(Role::Admin);

        $error = null;
        $data = null;
        $id = Helper::require_REQUEST('id');
        if ($user->getId() == $id){
            $error = "You cant delete yourself!";
        }else{
            try {
                if(Properties::$databaseEnabled){
                    $data = DB_UserManager::deleteUser($id);
                }else{
                    $error = "Error: not supported";
                }
            }catch(\Exception $ex){
                $error = utf8_encode($ex->getMessage());
            }
        }
        die(json_encode(array("error" => $error, "data" => $data)));

        break;
}

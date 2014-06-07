<?php

namespace piGallery\model;


/*LOGIC*/
require_once __DIR__."/../db/DB.php";
require_once __DIR__."/Helper.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/DirectoryScanner.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/UserManager.php";

use piGallery\db\DB;
use piGallery\Properties;
use piGallery\db\entities\Role;
use piGallery\model\AuthenticationManager;

/* empty string set as null*/
foreach ($_REQUEST as $key => $value){
    if($value == ""){
        unset($_REQUEST[$key]);
    }
}

function authenticate($role = Role::User) {

    /*Authenticating*/
    require_once __DIR__."/AuthenticationManager.php";
    require_once __DIR__."/../db/entities/Role.php";

    /*Authentication need for images*/
    AuthenticationManager::authenticate($role);
}


switch (Helper::require_REQUEST('method')){

    case 'getContent':
        authenticate();

        $dir = Helper::toDirectoryPath(utf8_decode(Helper::require_REQUEST('dir')));

        $error = null;
        $data = null;
        try {
            if (Properties::$databaseEnabled) {
                $data = DB::getDirectoryContent($dir);
            } else {
                $data = DirectoryScanner::getDirectoryContent($dir);
            }
        }catch(\Exception $ex){
            $error = utf8_encode($ex->getMessage());
        }

       /* foreach($data['directories'] as $dir){
            $dir->toUTF8();
        }
        foreach($data['photos'] as $photo){
            $photo->toUTF8();
        }*/

        die(json_encode(array("error" => $error, "data" => Helper::contentArrayToJSONable($data))));
        break;

    case 'autoComplete':

        authenticate();
        $count= intval(Helper::get_REQUEST('count',5));
        $searchText= Helper::require_REQUEST('searchText');


        if(Properties::$databaseEnabled){
            die(json_encode(DB::getAutoComplete($searchText,$count)));
        }else{
            die("Error: not supported");
        }
        break;

    case 'search':
        authenticate();
        $searchString = Helper::require_REQUEST('searchString');

        if(Properties::$databaseEnabled){
            die(Helper::contentArrayToJSON(DB::getSearchResult($searchString)));
        }else{
            die("Error: not supported");
        }

        break;
    case 'login':
        $userName =  Helper::require_REQUEST('userName');
        $password = Helper::require_REQUEST('password');
        $rememberMe = filter_var(Helper::get_REQUEST('rememberMe',"false"), FILTER_VALIDATE_BOOLEAN);

        $error = null;
        $data = null;
        if(Properties::$databaseEnabled){

        }else{
            $user = UserManager::login($userName, $password);
            if($user != null){
                $user->setPassword(null);
               $data= $user->getJsonData();
            }else{
                $error = "Wrong user name or password";
            }
        }

        die(json_encode(array("error" => $error, "data" =>$data)));

        break;
    case 'logout':
        $sessionID =  Helper::require_REQUEST('sessionID');
        $error = null;
        $data = null;
        if(Properties::$databaseEnabled){

        }

        die(json_encode(array("error" => $error, "data" =>$data)));
        break;

    case 'recreateDatabase':
        authenticate(Role::Admin);
        if(Properties::$databaseEnabled){
            die(DB::recreateDatabase());
        }else{
            die("Error: not supported");
        }
        break;
    case 'reScanDirectory':
        authenticate(Role::Admin);
        $dir = Helper::require_REQUEST('dir');
        if(Properties::$databaseEnabled){
            die(DB::reScanDirectory($dir));
        }else{
            die("Error: not supported");
        }
        break;
}

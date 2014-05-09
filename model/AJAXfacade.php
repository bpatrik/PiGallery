<?php

namespace piGallery\model;

require_once __DIR__ ."./../db/DB.php";
require_once __DIR__ ."./Helper.php";
require_once __DIR__ ."./../config.php";
require_once __DIR__ ."./DirectoryScanner.php";
require_once __DIR__ ."./../config.php";

use piGallery\db\DB;
use piGallery\Properties;

/* empty string set as null*/
foreach ($_REQUEST as $key => $value){
    if($value == ""){
        unset($_REQUEST[$key]);
    }
}



switch (Helper::require_REQUEST('method')){

    case 'getContent':
        $dir = Helper::require_REQUEST('dir');

        if(Properties::$databaseEnabled){
            die(Helper::contentArrayToJSON(DB::getDirectoryContent($dir)));
        }else{
            die(Helper::contentArrayToJSON(DirectoryScanner::getDirectoryContent($dir)));
        }
        break;
    case 'autoComplete':
        $count= intval(Helper::get_REQUEST('count',5));
        $searchText= Helper::require_REQUEST('searchText');


        if(Properties::$databaseEnabled){
            die(json_encode(DB::getAutoComplete($searchText,$count,"/")));
        }else{
            die("Error: not supported");
        }
        break;

    case 'search':

        $searchString = Helper::require_REQUEST('searchString');

        if(Properties::$databaseEnabled){
            die(Helper::contentArrayToJSON(DB::getSearchResult($searchString)));
        }else{
            die("Error: not supported");
        }

        break;
    case 'recreateDatabase':
        if(Properties::$databaseEnabled){
            die(DB::recreateDatabase());
        }else{
            die("Error: not supported");
        }
        break;
    case 'login':
        $userName =  Helper::require_REQUEST('userName');
        $password = Helper::require_REQUEST('password');
        $searchText = filter_var(Helper::get_REQUEST('rememberMe',"false"), FILTER_VALIDATE_BOOLEAN);

        $error = null;
        $data = null;
        if(Properties::$databaseEnabled){

        }else{
            foreach(Properties::$users as &$value){
                if($value['userName'] == $userName && $value['password'] == $password){
                    $data = array(
                        "sessionId" => md5($userName.$password),
                        "userName" => $userName,
                        "role" => "user"
                    );
                    break;
                }
            }
        }
        if($data == null){
            $error = "Wrong user name or password";
        }

        die(json_encode(array("error" => $error, "data" =>$data)));

        break;

}

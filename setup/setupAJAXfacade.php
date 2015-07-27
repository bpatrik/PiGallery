<?php

namespace piGallery\model;


/*LOGIC*/
require_once __DIR__."/../db/DB.php";
require_once __DIR__."/../db/DB_ContentManager.php";
require_once __DIR__."/../model/Helper.php";
require_once __DIR__."/../config.php";  
require_once __DIR__."/../db/entities/AjaxError.php";

use \mysqli;
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
    if(Properties::$installerWizardEnabled){
        return new User("Guest", null, Role::Admin);
    }
    /*Authenticating*/
    require_once __DIR__."/../model/AuthenticationManager.php";
    require_once __DIR__."/../db/entities/Role.php";

    /*Authentication need for images*/
    $user = AuthenticationManager::authenticate($role);
    if(is_null($user)){
        die(json_encode(array("error" => (new AjaxError(AjaxError::AUTHENTICATION_FAIL, "Authentication failed"))->getJsonData(), "data" => "")));
    }
    return $user;
}

switch (Helper::require_REQUEST('method')) {

    case 'validateImageAndThumbnailFolder':
        authenticate(Role::Admin);
        $error = null;
        $data = null;

        $documentRoot = Helper::toDirectoryPath(Helper::require_REQUEST('documentRoot'));
        $documentRoot = Helper::concatPath(Helper::toDirectoryPath($_SERVER['DOCUMENT_ROOT']), $documentRoot);
        $imageFolder = Helper::concatPath($documentRoot,Helper::toDirectoryPath(Helper::require_REQUEST('imageFolder')));
        $thumbnailFolder = Helper::concatPath($documentRoot,Helper::toDirectoryPath(Helper::require_REQUEST('thumbnailFolder')));

        if(!file_exists($imageFolder)){
            $error = new AjaxError(AjaxError::GENERAL_ERROR,"Image folder: '". Helper::require_REQUEST('imageFolder')."'  not exist");
        }else if(!file_exists($thumbnailFolder)){
            $error = new AjaxError(AjaxError::GENERAL_ERROR,"Thumbnail folder: '". Helper::require_REQUEST('thumbnailFolder')."' not exist");
        }else if(!is_readable($imageFolder)){
            $error = new AjaxError(AjaxError::GENERAL_ERROR,"Image folder: '". Helper::require_REQUEST('imageFolder')."'' must be readable");
        }else if(!is_writable($thumbnailFolder)){
            $error = new AjaxError(AjaxError::GENERAL_ERROR,"Thumbnail folder: '". Helper::require_REQUEST('thumbnailFolder')."' must be writable");
        }else{
            $data="ok";
        }

            die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));
        break;

    case 'validateDataBaseSettings':
        authenticate(Role::Admin);
        $error = null;
        $data = null;


        $databaseAddress = Helper::require_REQUEST('databaseAddress');
        $databaseUserName = Helper::require_REQUEST('databaseUserName');
        $databasePassword = Helper::require_REQUEST('databasePassword');
        $databaseName = Helper::require_REQUEST('databaseName');

        if (!function_exists('mysqli_connect')) {
            $error = new AjaxError(AjaxError::GENERAL_ERROR,"Can't find php-gd! This site is using php gd for generating thumbnails! Install it and enable it in php.ini!");
        }else{
            $mysqli = @new mysqli(
                $databaseAddress,
                $databaseUserName,
                $databasePassword,
                $databaseName);

            if ($mysqli->connect_errno) {
                $error = new AjaxError(AjaxError::GENERAL_ERROR,"Failed to connect to MySQL: " . $mysqli->connect_error);
            }else{
                $data="ok";
                $mysqli->close();
            }

        }


        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));
        break;

    case 'getUsersList':
        authenticate(Role::Admin);
        $error = null;
        $data = null;

        $databaseMode = Helper::require_REQUEST('databaseMode');

        try {
            if($databaseMode == "UseDatabase"){

                $databaseAddress = Helper::require_REQUEST('databaseAddress');
                $databaseUserName = Helper::require_REQUEST('databaseUserName');
                $databasePassword = Helper::require_REQUEST('databasePassword');
                $databaseName = Helper::require_REQUEST('databaseName');

                $mysqli = @new mysqli(
                    $databaseAddress,
                    $databaseUserName,
                    $databasePassword,
                    $databaseName);

                if ($mysqli->connect_errno) {
                    $error = new AjaxError(AjaxError::GENERAL_ERROR, "Failed to connect to MySQL: " . $mysqli->connect_error);
                }else {

                    $users = array();
                    $stmt = $mysqli->prepare("SELECT
                                    u.ID,
                                    u.userName,
                                    u.role
                                    FROM
                                    users u ");
                    if ($stmt === false) {
                        $error = $mysqli->error;
                        $mysqli->close();
                        throw new \Exception("Error: " . $error);
                    }
                    $stmt->execute();
                    $stmt->bind_result($userID, $userName, $role);


                    while ($stmt->fetch()) {
                        $user = new User($userName, $userName, $role);
                        $user->setId($userID);
                        array_push($users, $user);
                    }
                    $stmt->close();

                    $mysqli->close();

                    $data = Helper::phpObjectArrayToJSONable($users);
                }
            }else{

                $data =  Properties::$users;
            }
        }catch(\Exception $ex){
            $error = new AjaxError(AjaxError::GENERAL_ERROR, utf8_encode($ex->getMessage()));
        }
        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));
        break;

}

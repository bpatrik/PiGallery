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
                        $user = new User($userName, "******", $role);
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

    case 'saveSettings':
        authenticate(Role::Admin);
        $error = null;
        $data = null;

        $properties = json_decode(Helper::require_REQUEST('properties'),true);

        $usersString ="";
        if($properties["\$databaseEnabled"]) {
            $mysqli = @new mysqli(
                $properties["\$databaseAddress"],
                $properties["\$databaseUserName"],
                $properties["\$databasePassword"],
                $properties["\$databaseName"]);

            if ($mysqli->connect_errno) {
                $error = new AjaxError(AjaxError::GENERAL_ERROR, "Failed to connect to MySQL: " . $mysqli->connect_error);
            }else {
                $idArray = array();
                foreach ($properties["\$users"] as $value) {
                    $idArray[]=intval($value["id"]);
                }


                $inQuery = implode(',', $idArray);
                $stmt = $mysqli->prepare("DELETE FROM users WHERE id NOT IN (".$inQuery.")");

                if($stmt === false) {
                    $error = $mysqli->error;
                    $mysqli->close();
                    throw new \Exception("Error: ". $error);
                }

                $stmt->execute();

                $stmt ->close();

                foreach ($properties["\$users"] as $value) {
                    //insert new users
                    if(!$value["id"] || $value["id"] == null || $value["id"] == ""){
                        $stmt = $mysqli->prepare("INSERT INTO users (userName, password, passwordSalt, role) VALUES (?, ?, ?, ?)");

                        if($stmt === false) {
                            $error = $mysqli->error;
                            $mysqli->close();
                            throw new \Exception("Error: ". $error);
                        }
                        $userName = $value["userName"];
                        $password = $value["password"];
                        $salt = uniqid(mt_rand(), true);
                        $encrypted_password = sha1($salt.$password);
                        $role = $value["role"];

                        $stmt->bind_param('sssi', $userName, $encrypted_password, $salt, $role);
                        $stmt->execute();

                        $stmt ->close();
                    }
                }



            }
        }else{
            $maxCount = count($properties["\$users"]);
            $counter = 0;
            $usersString.="\r\n";
            foreach($properties["\$users"] as $value){
                $usersString.= "                    ".
                                    'array("userName" =>"'.$value["userName"].'", "password" =>"'.$value["password"].'", "role" => '.$value["role"].')';
                $counter++;
                if($counter < $maxCount){
                    $usersString.=",\r\n";
                }
            }
        }

        $propertiesText = '<?php
namespace piGallery;

class Properties{
    public static $installerWizardEnabled = false;

    public static $language = "'.$properties["\$language"].'";

    public static $siteUrl = "'.$properties["\$siteUrl"].'";
    public static $documentRoot  = "'.$properties["\$documentRoot"].'";
    public static $imageFolder = "'.$properties["\$imageFolder"].'";
    public static $thumbnailFolder = "'.$properties["\$thumbnailFolder"].'";

    public static $thumbnailSizes = array('.$properties["\$thumbnailSizes"].');
    public static $thumbnailJPEGQuality = '.$properties["\$thumbnailJPEGQuality"].';
    public static $EnableThumbnailResample = '.($properties["\$EnableThumbnailResample"] ? 'true' : 'false').';
    public static $enableImageCaching = '.($properties["\$enableImageCaching"] ? 'true' : 'false').';
    public static $enableUTF8Encode = '.($properties["\$enableUTF8Encode"] ? 'true' : 'false').';

    public static $databaseEnabled = '.($properties["\$databaseEnabled"] ? 'true' : 'false').';
    public static $databaseAddress = "'.$properties["\$databaseAddress"].'";
    public static $databaseUserName = "'.$properties["\$databaseUserName"].'";
    public static $databasePassword = "'.$properties["\$databasePassword"].'";
    public static $databaseName = "'.$properties["\$databaseName"].'";
    public static $enableSearching = '.($properties["\$enableSearching"] ? 'true' : 'false').';
    public static $enableSharing = '.($properties["\$enableSharing"] ? 'true' : 'false').';

    public static $enableOnTheFlyIndexing = '.($properties["\$enableOnTheFlyIndexing"] ? 'true' : 'false').';
    public static $maxSearchResultItems = '.$properties["\$maxSearchResultItems"].';
    public static $GuestLoginAtLocalNetworkEnabled = '.($properties["\$GuestLoginAtLocalNetworkEnabled"] ? 'true' : 'false').';

    public static $users = array('.$usersString.');

}
';

        $manualConfigFileContent = str_replace("<?","&lt;?",str_replace("\r\n","<br/>",$propertiesText));
        $configFileUrl = __DIR__."/../config.php";
        if ( !file_exists($configFileUrl) ) {
            $error = new AjaxError(AjaxError::GENERAL_ERROR, "Config file not found. Open the config.php and override the content with this: ".$manualConfigFileContent);
        }else {

            $configFile = fopen($configFileUrl, "w+");
            if (!$configFile) {
                $error = new AjaxError(AjaxError::GENERAL_ERROR, "Can't open config file for write. Open the config.php and override the content with this: " . $manualConfigFileContent);
            } else {
                $data = "ok";
                fwrite($configFile, $propertiesText);
                fclose($configFile);
            }

        }


        die(json_encode(array("error" => is_null($error) ? null : $error->getJsonData(), "data" => $data)));
        break;
}

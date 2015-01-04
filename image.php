<?php

namespace piGallery;

/*Authenticating*/
require_once __DIR__."/model/AuthenticationManager.php";
require_once __DIR__."/db/entities/Role.php";
require_once __DIR__."/db/entities/AjaxError.php";

use piGallery\db\entities\AjaxError;
use piGallery\db\entities\Role;
use piGallery\model\AuthenticationManager;

/*Authentication need for images*/
$user = AuthenticationManager::authenticate(Role::RemoteGuest);
if(is_null($user)){
    die(json_encode(array("error" => (new AjaxError(AjaxError::AUTHENTICATION_FAIL, "Authentication failed"))->getJsonData(), "data" => "")));
}

/*SITE*/
require_once __DIR__."/model/Helper.php";
require_once __DIR__."/config.php";
require_once __DIR__."/model/Logger.php";


use piGallery\model\Helper;
$imagePath= Helper::require_REQUEST("path");
if (Properties::$enableUTF8Encode) {
    $imagePath= utf8_decode($imagePath);
}
$imagePath = Helper::toDirectoryPath($imagePath);
if($user->getPathRestriction() != null){
    $dir = dirname($imagePath);
    if($user->getPathRestriction()->isRecursive() == false && !Helper::isPathEqual($dir, $user->getPathRestriction()->getPath())){
        die(json_encode(array("error" => (new AjaxError(AjaxError::GENERAL_ERROR, "Don't have rights for thr directory"))->getJsonData(), "data" => "")));
    }else if(Helper::isSubPath($dir, $user->getPathRestriction()->getPath()) === FALSE){
        die(json_encode(array("error" => (new AjaxError(AjaxError::GENERAL_ERROR, "Don't have rights for thr directory"))->getJsonData(), "data" => "")));
    }
}

$imagePath =  Helper::concatPath(Helper::getAbsoluteImageFolderPath(), $imagePath);

if(Properties::$enableImageCaching){
    /*Enable caching*/
    $time = 1280951171;
    $lastmod = gmdate('D, d M Y H:i:s \G\M\T', $time);
    $etag = "pigalleryimg-".md5($imagePath);

    $ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastmod : null;
    $iftag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] == $etag : null;

    if (($ifmod || $iftag) && ($ifmod !== false && $iftag !== false)) {
        header('Not Modified',true,304);
    } else {
        header("Last-Modified: $lastmod");
        header("ETag: $etag");
    }
    header('Cache-Control: max-age=31104000');
}

header('content-type: '. Helper::imageToMime($imagePath));
header("Content-Length: " . filesize($imagePath));
echo file_get_contents($imagePath);
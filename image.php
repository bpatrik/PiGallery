<?php

namespace piGallery;

/*Authenticating*/
require_once __DIR__."/model/AuthenticationManager.php";
require_once __DIR__."/db/entities/Role.php";

use piGallery\db\entities\Role;
use piGallery\model\AuthenticationManager;

/*Authentication need for images*/
AuthenticationManager::authenticate(Role::User);

/*SITE*/

require_once __DIR__."/model/Helper.php";
require_once __DIR__."/config.php";
require_once __DIR__."/model/Logger.php";


use piGallery\model\Helper;
use piGallery\model\Logger;
use piGallery\Properties;


$imagePath= Helper::toDirectoryPath(Helper::require_REQUEST("path")); 
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
    header('Cache-Control: max-age=259200');
}

header('content-type: image/jpeg');
header("Content-Length: " . filesize($imagePath));
echo file_get_contents($imagePath);
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

require_once __DIR__."/config.php";
require_once __DIR__."/model/Helper.php";
require_once __DIR__."/model/Logger.php";
require_once __DIR__."/model/ThumbnailManager.php";
require_once __DIR__."/model/AuthenticationManager.php";
require_once __DIR__."/db/entities/Role.php";

use piGallery\model\Logger;
use piGallery\model\ThumbnailManager;
use piGallery\model\Helper;



$image= Helper::toDirectoryPath(utf8_decode(Helper::require_REQUEST("image")));
$size= Helper::require_REQUEST("size");


$thumbnail = ThumbnailManager::requestThumbnail($image, $size);

if(Properties::$enableImageCaching){
    /*Enable caching*/
    $time = 1280951171;
    $lastmod = gmdate('D, d M Y H:i:s \G\M\T', $time);
    $etag = "pigallerythumbnail-".md5($image.$size);

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
header("Content-Length: " . $thumbnail["filesSze"]);
echo $thumbnail["image"];

?>
<?php

namespace piGallery;

require_once __DIR__."./model/Helper.php";
require_once __DIR__ ."./config.php";


use piGallery\model\Helper;
use piGallery\Properties;

$imagePath= Helper::toDirectoryPath(Helper::require_REQUEST("path"));
$imagePath = Helper::concatPath(Properties::$imageFolder, $imagePath);
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
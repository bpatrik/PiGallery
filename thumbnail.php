<?php

namespace piGallery;

require_once __DIR__ ."./config.php";
require_once __DIR__."./model/Helper.php";
require_once __DIR__."./model/Logger.php";
require_once __DIR__ ."./model/ThumbnailManager.php";

use piGallery\model\Logger;
use piGallery\model\ThumbnailManager;
use piGallery\model\Helper;


Logger::v("thumbnail.php","starting");
$image= Helper::toDirectoryPath(Helper::require_REQUEST("image"));
$size= Helper::require_REQUEST("size");

Logger::v("thumbnail.php","started: " . $image . ": " . $size);

$thumbnail = ThumbnailManager::requestThumbnail($image, $size);

header('content-type: image/jpeg');
header("Content-Length: " . $thumbnail["filesSze"]);
echo $thumbnail["image"];

Logger::v("thumbnail.php","ended");
?>
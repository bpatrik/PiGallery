<?php
$startTime = time();
require_once __DIR__."/config.php";
require_once __DIR__."/model/ThumbnailManager.php";
require_once __DIR__."/model/DirectoryScanner.php";
require_once __DIR__."/model/Helper.php";
require_once __DIR__."/db/DB_ContentManager.php";

use \piGallery\model\Helper;
use \piGallery\Properties;

date_default_timezone_set('UTC'); //set it if not set
$executionTime = ini_get('max_execution_time');
if($executionTime == 0) $executionTime = 60;

echo "[".date(DATE_RFC2822)."]"."[VERBOSE]: execution time ".$executionTime." sec <br/>\n";
$timeForExit = 8; //leave seconds for saving things
$maxCreationAtOnce = 20;

$thumbnailSize = 300; //TODO:: bring from config
$directoriesToScan = array(Helper::toDirectoryPath(DIRECTORY_SEPARATOR));
$ThumbnailsToCreate = array();

//override doc root
if(!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])){
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    if(Helper::isSubPath(__DIR__, Properties::$documentRoot)){
        $_SERVER['DOCUMENT_ROOT'] = ltrim(str_replace(__DIR__,"", Properties::$documentRoot),DIRECTORY_SEPARATOR);
    }
}

$my_file = Helper::concatPath(Helper::getAbsoluteThumbnailFolderPath(),'batchThumbnailGeneration.data');
if(file_exists($my_file))
{
    $handle = fopen($my_file, 'r');
    if ($handle) {
        $data = json_decode(fread($handle, filesize($my_file)),true);
        if (is_array($data)) {
            if (isset($data['directoriesToScan'])) {
                $directoriesToScan = $data['directoriesToScan'];
                if(Properties::$enableUTF8Encode){
                    foreach($directoriesToScan as &$item){
                        $item = utf8_decode($item);
                    }
                }
            }
            if (isset($data['ThumbnailsToCreate'])) {
                $ThumbnailsToCreate = $data['ThumbnailsToCreate'];
                if(Properties::$enableUTF8Encode){
                    foreach($ThumbnailsToCreate as &$item){
                        $item = utf8_decode($item);
                    }
                }
            }
        }
    } else {
        echo "[".date(DATE_RFC2822)."]"."cant read file";
    }
}else{
    echo "[".date(DATE_RFC2822)."]"."[VERBOSE]: TMP work file not created yet (".$my_file.") <br/>\n";
}
/*if nothing to create, index some more*/
while(count($ThumbnailsToCreate) == 0 && count($directoriesToScan) != 0) {
    if (count($directoriesToScan) != 0) {
        $dir = array_pop($directoriesToScan);
        echo "[".date(DATE_RFC2822)."]"."[VERBOSE]: Scanning directory: ".$dir ."<br/>\n";
        $content = array("directories" => array() ,
                         "photos" => array());
        if (Properties::$databaseEnabled) {
            $content = \piGallery\db\DB_ContentManager::getDirectoryContent($dir);
        } else {
            $content = \piGallery\model\DirectoryScanner::getDirectoryContent($dir);
        }
        foreach($content['directories'] as $directory){
            $directoriesToScan[] = Helper::concatPath($directory->getPath(), $directory->getDirectoryName());
        }
        foreach($content['photos'] as $photo) {
            foreach($photo->getAvailableThumbnails() as $thInfo){
                if($thInfo->getSize() == $thumbnailSize && $thInfo->getAvailable() == false){
                    $ThumbnailsToCreate[] = Helper::concatPath($photo->getPath(), $photo->getFileName());
                    break;
                }
            }
        }

    }
}

/*index photos*/
echo "[".date(DATE_RFC2822)."]"."[VERBOSE]: Indexing photos <br/>\n";
$createdThumbnail = 0;
while(count($ThumbnailsToCreate) > 0) {
    $photo = array_pop($ThumbnailsToCreate);
    if (\piGallery\model\ThumbnailManager::generateThumbnailIfNeeded($photo, $thumbnailSize) == false){
        echo "[".date(DATE_RFC2822)."]"."[ERROR]: Error during creating thumbnail: ".$photo."<br/>\n";
    }

    $createdThumbnail++;
    //Check if already created enough thumbnail in this session or time is up
    if($maxCreationAtOnce <= $createdThumbnail || (time() - $startTime ) > ($executionTime - $timeForExit) ){
        break;
    }
}
echo "[".date(DATE_RFC2822)."]"."[VERBOSE]:".$createdThumbnail." thumbnails created in: ".(time() - $startTime) ."sec \n";

$handle = fopen($my_file, 'w');
if($handle){
    $data = array("directoriesToScan" => $directoriesToScan, "ThumbnailsToCreate" => $ThumbnailsToCreate );
    if(Properties::$enableUTF8Encode){
        foreach($data['directoriesToScan'] as &$item){
            $item = utf8_encode($item);
        }
        foreach($data['ThumbnailsToCreate'] as &$item){
            $item = utf8_encode($item);
        }
    }
    fwrite($handle, json_encode($data));
}else{
    echo "[".date(DATE_RFC2822)."]"."cant write file";
}

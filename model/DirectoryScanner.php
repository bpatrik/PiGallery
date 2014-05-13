<?php

namespace piGallery\model;

require_once __DIR__."/../config.php";
require_once __DIR__."/../db/entities/Photo.php";
require_once __DIR__."/../db/entities/Directory.php";
require_once __DIR__."/ThumbnailManager.php";


use piGallery\db\entities\Directory;
use piGallery\db\entities\Photo;
use piGallery\Properties;

class DirectoryScanner {

    /**
     * @param string $path
     * @return array
     */
    public static function getDirectoryContent($path = "/"){

        $relativePath = $path;
        $path = utf8_decode(urldecode($path));
        $path = Helper::toDirectoryPath($path);

        //is image folder already added?
        if(!Helper::isSubPath($path,Properties::$imageFolder)){
            $path = Helper::concatPath(Properties::$imageFolder,$path);
        }

		$documentRoot = Helper::concatPath(Helper::toDirectoryPath($_SERVER['DOCUMENT_ROOT']), Properties::$documentRoot);
        //set absolute positition
        if(!Helper::isSubPath($path,$documentRoot)){
            $path = Helper::concatPath($documentRoot,$path);
        }


        $dirContent = scandir($path);
        $directories = array();
        $photos = array();
        foreach ($dirContent as &$value) { //search for directories and other files
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                if(is_dir($contentPath) == true){
                    $value = utf8_encode($value);
                    array_push($directories, new Directory(0, Helper::toURLPath(Helper::relativeToImageDirectory($path)),$value, 0, DirectoryScanner::getPhotos($contentPath,5)));

                }else{
                    list($width, $height, $type, $attr) = getimagesize($contentPath, $info);
                    //loading lightroom keywords
                    $keywords = array();
                    if(isset($info['APP13'])) {
                        $iptc = iptcparse($info['APP13']);

                        if(isset($iptc['2#025'])) {
                            $keywords = $iptc['2#025'];
                        }
                    }

                    //TODO: simplify
                    $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                        Helper::relativeToImageDirectory($contentPath));

                    array_push($photos, new Photo(md5($contentPath), urlencode(Helper::toURLPath(Helper::relativeToImageDirectory($path))), $value,$width, $height, $keywords, $availableThumbnails ));
                }
            }
        }
        return array("currentPath" => $relativePath ,"directories" => $directories , "photos" => $photos);
    }



    public static function getPhotos($path, $maxCount){

        $path = Helper::concatPath($path,DIRECTORY_SEPARATOR);
        $dirContent = scandir($path);
        $photos = array();
        foreach ($dirContent as &$value) { //search for directories and other files
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                if(is_dir($contentPath) == true){
                }else{
                    list($width, $height, $type, $attr) = getimagesize($contentPath, $info);
                    //loading lightroom keywords
                    $keywords = array();
                    if(isset($info['APP13'])) {
                        $iptc = iptcparse($info['APP13']);

                        if(isset($iptc['2#025'])) {
                            $keywords = $iptc['2#025'];
                        }
                    }

                    $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                        Helper::relativeToImageDirectory($contentPath));

                    array_push($photos, new Photo(md5($contentPath), urlencode (Helper::toURLPath(Helper::relativeToImageDirectory($path))), $value,$width, $height, $keywords, $availableThumbnails ));
                    $maxCount--;
                    if($maxCount <=0)
                        break;
                }
            }
        }
        return $photos;
    }



} 
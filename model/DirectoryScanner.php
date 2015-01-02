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
     * @throws \Exception
     */
    public static function getDirectoryContent($path = "/"){

        $relativePath = $path;

        //is image folder already added?
        if(!Helper::isSubPath(Helper::getAbsoluteImageFolderPath(),Properties::$imageFolder)){
            $path = Helper::concatPath(Helper::getAbsoluteImageFolderPath(),$path);
        }

        if(!is_dir($path)){
            throw new \Exception('No such directory: '.$path);
        }

        $directories = array();
        $photos = array();

        $handle = opendir($path);
        while (false !== ($value = readdir($handle))) {
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                //read directory
                if(is_dir($contentPath) == true){
                    array_push($directories, new Directory(0, Helper::relativeToImageDirectory($path),$value, 0, 1, DirectoryScanner::getSamplePhotos($contentPath,5)));
                //read photo
                }else{

                    list($width, $height, $type, $attr) = getimagesize($contentPath, $info);

                    if($type != IMAGETYPE_JPEG && $type != IMAGETYPE_PNG && $type != IMAGETYPE_GIF)
                        continue;

                    //loading lightroom keywords
                    $keywords = array();
                    if(isset($info['APP13'])) {
                        $iptc = iptcparse($info['APP13']);

                        if(isset($iptc['2#025'])) {
                            $keywords = $iptc['2#025'];
                        }
                    }
                    $creationDate = filectime($contentPath);

                    $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                        Helper::relativeToImageDirectory($contentPath));

                    array_push($photos, new Photo(md5($contentPath), Helper::relativeToImageDirectory($path), $value,$width, $height, $keywords,$creationDate, $availableThumbnails ));
                }
            }
        }
        closedir($handle);
        return array("currentPath" => $relativePath ,"directories" => $directories , "photos" => $photos);
    }



    public static function getSamplePhotos($path, $maxCount){

        $path = Helper::concatPath($path,DIRECTORY_SEPARATOR);
        $photos = array();

        $handle = opendir($path);
        while (false !== ($value = readdir($handle))) {
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                if(is_dir($contentPath) == true){
                }else{
                    list($width, $height, $type, $attr) = getimagesize($contentPath);


                    $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                        Helper::relativeToImageDirectory($contentPath));

                    array_push($photos, new Photo(md5($contentPath), Helper::relativeToImageDirectory($path), $value,$width, $height, null, null, $availableThumbnails ));
                    $maxCount--;
                    if($maxCount <=0)
                        break;
                }
            }
        }
        closedir($handle);
        return $photos;
    }



} 
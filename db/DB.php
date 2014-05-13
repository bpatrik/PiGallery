<?php

namespace piGallery\db;

require_once __DIR__ ."/../config.php";
require_once __DIR__ ."/entities/Photo.php";
require_once __DIR__ ."/entities/Directory.php";
require_once __DIR__ ."/../model/ThumbnailManager.php";


use piGallery\db\entities\Directory;
use piGallery\db\entities\Photo;
use piGallery\model\Helper;
use piGallery\model\ThumbnailManager;
use piGallery\Properties;
use \mysqli;
use \Exception;

/**
 * Class DB
 * @package piGallery\db
 */
class DB {

    private static function getDatabseConnection(){

        $mysqli = new mysqli(Properties::$databaseAddress,
                             Properties::$databseUserName,
                             Properties::$databsePassword,
                             Properties::$databseName);

        if ($mysqli->connect_errno) {
            throw new Exception("Failed to connect to MySQL: " . $mysqli->connect_error);
        }

        return $mysqli;

    }

    public static function recreateDatabase(){


        $dropPhotoTableSql ="DROP TABLE photos";
        $dropDirectoryTableSql ="DROP TABLE directories";

        $createPhotoTableSQL = "CREATE TABLE photos
                                (
                                    ID INT NOT NULL AUTO_INCREMENT,
                                    PRIMARY KEY(ID),
                                    directory_id INT,
                                    fileName NVARCHAR(64),
                                    width INT,
                                    height INT,
                                    keywords NVARCHAR(128),
                                    FOREIGN KEY (directory_id)
                                        REFERENCES directories(ID)
                                        ON DELETE CASCADE
                                )";

        $createDirectoryTableSQL = "CREATE TABLE directories
                                    (
                                        ID INT NOT NULL AUTO_INCREMENT,
                                        PRIMARY KEY(ID),
                                        path NVARCHAR(256),
                                        directoryName NVARCHAR(64),
                                        lastModification DATETIME
                                    )";


        try{

            $mysqli = DB::getDatabseConnection();

            //Dropping table
            $mysqli->query($dropPhotoTableSql);
            $mysqli->query($dropDirectoryTableSql);

            //Creating table
            if(!$mysqli->query($createDirectoryTableSQL)){
                throw new Exception("Error: ". $mysqli->error);
            }

            if(!$mysqli->query($createPhotoTableSQL)){
                throw new Exception("Error: ". $mysqli->error);
            }


        }catch(Exception $ex){

            $mysqli->close();
            return $ex->getMessage();
        }


        $mysqli->close();
        return "ok";


    }

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

        //set absolute positition
        if(!Helper::isSubPath($path,$_SERVER['DOCUMENT_ROOT'])){
            $path = Helper::concatPath($_SERVER['DOCUMENT_ROOT'],$path);
        }


        $dirContent = scandir($path);
        $directories = array();
        $photos = array();
        foreach ($dirContent as &$value) { //search for directories and other files
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                if(is_dir($contentPath) == true){
                    $value = utf8_encode($value);
                    array_push($directories, new Directory(0, Helper::toURLPath(Helper::relativeToDocumentRoot($path)),$value, 0, DB::getPhotos($contentPath,5)));

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
                                                Helper::toDirectoryPath(
                                                        Helper::toURLPath(
                                                            Helper::relativeToDocumentRoot($contentPath))));

                    array_push($photos, new Photo(0, urlencode(Helper::toURLPath(Helper::relativeToDocumentRoot($path))), $value,$width, $height, $keywords, $availableThumbnails ));
                }
            }
        }
        sleep(1);
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
                        Helper::toDirectoryPath(
                            Helper::toURLPath(
                                Helper::relativeToDocumentRoot($contentPath))));

                    array_push($photos, new Photo(0, urlencode (Helper::toURLPath(Helper::relativeToDocumentRoot($path))), $value,$width, $height, $keywords, $availableThumbnails ));
                    $maxCount--;
                    if($maxCount <=0)
                        break;
                }
            }
        }
        return $photos;
    }

    public static function getSearchResult($searchString, $path = "/"){
        $relativePath = $path;
        $path = Helper::toDirectoryPath($path);

        //is image folder already added?
        if(!Helper::isSubPath($path,Properties::$imageFolder)){
            $path = Helper::concatPath(Properties::$imageFolder,$path);
        }

        //set absolute positition
        if(!Helper::isSubPath($path,$_SERVER['DOCUMENT_ROOT'])){
            $path = Helper::concatPath($_SERVER['DOCUMENT_ROOT'],$path);
        }


        $dirContent = scandir($path);
        $directories = array();
        $photos = array();
        foreach ($dirContent as &$value) { //search for directories and other files
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                if(is_dir($contentPath) == true){

                    $subDriResult = DB::getSearchResult($searchString, Helper::concatPath(Helper::relativeToDocumentRoot($contentPath),"/"));

                    $directories = array_merge($directories,$subDriResult["directories"]);
                    $photos = array_merge($photos,$subDriResult["photos"]);
                    if(stripos($value, $searchString) !== FALSE){
                        array_push($directories, new Directory(0, Helper::toURLPath(Helper::relativeToDocumentRoot($path)), $value, 0, DB::getPhotos($contentPath,5)));
                    }

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
                    $found = false;
                    foreach($keywords as &$keyword){
                        if(stripos($keyword, $searchString) !== FALSE){
                            $found = true;
                            break;
                        }
                    }

                    if($found == false && stripos($value, $searchString) !== FALSE){
                        $found = true;
                    }

                    if($found){

                        $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                            Helper::toDirectoryPath(
                                Helper::toURLPath(
                                    Helper::relativeToDocumentRoot($contentPath))));
                        array_push($photos, new Photo(0, Helper::toURLPath(Helper::relativeToDocumentRoot($path)), $value,$width, $height, $keywords, $availableThumbnails ));
                    }
                }
            }
        }

        return array("searchString" => $searchString ,"directories" => $directories , "photos" => $photos);

    }



    public static function getAutoComplete($prefix, $count, $path = "/"){
        if($count <= 0)
            return array();
        //is image folder already added?
        if(!Helper::isSubPath($path,Properties::$imageFolder)){
            $path = Helper::concatPath(Properties::$imageFolder,$path);
        }

        //set absolute positition
        if(!Helper::isSubPath($path,$_SERVER['DOCUMENT_ROOT'])){
            $path = Helper::concatPath($_SERVER['DOCUMENT_ROOT'],$path);
        }

        $dirContent = scandir($path);
        $foundItems = array();
        $directories = array();
        foreach ($dirContent as &$value) { //search for directories and other files
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                if(is_dir($contentPath) == true){

                    if(stripos($value, $prefix) !== FALSE){
                        $count--;
                        array_push($foundItems, array("text" => $value, "type"=>"dir"));
                        if($count <= 0)
                            return $foundItems;
                    }
                    array_push($directories,Helper::relativeToDocumentRoot($contentPath));
                }else{
                    getimagesize($contentPath, $info);

                    //loading lightroom keywords
                    $keywords = array();
                    if(isset($info['APP13'])) {
                        $iptc = iptcparse($info['APP13']);

                        if(isset($iptc['2#025'])) {
                            $keywords = $iptc['2#025'];
                        }
                    }

                    foreach($keywords as &$keyword){
                        if(stripos($keyword, $prefix) !== FALSE){
                            $count--;
                            if($count <= 0)
                                return $foundItems;
                            array_push($foundItems, array("text" => $keyword, "type"=>"keyword"));
                        }
                    }

                    if(stripos($value, $prefix) !== FALSE){
                        $count--;
                        array_push($foundItems, array("text" => $value, "type"=>"photo"));
                        if($count <= 0)
                            return $foundItems;
                    }


                }
            }
        }
        foreach ($directories as &$dir) {
            $foundItems = array_merge($foundItems,DB::getAutoComplete($prefix,$count,$dir));
        }

        return $foundItems;

    }
} 
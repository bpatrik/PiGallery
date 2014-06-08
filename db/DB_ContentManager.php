<?php

namespace piGallery\db;

require_once __DIR__ ."/../config.php";
require_once __DIR__ ."/DB_UserManager.php";
require_once __DIR__ ."/DB.php";
require_once __DIR__ ."/entities/Photo.php";
require_once __DIR__ ."/entities/Role.php";
require_once __DIR__ ."/entities/Directory.php";
require_once __DIR__ ."/../model/ThumbnailManager.php";


use piGallery\db\entities\Directory;
use piGallery\db\entities\Photo;
use piGallery\db\entities\User;
use piGallery\db\entities\Role;
use piGallery\db\DB_UserManager;
use piGallery\db\DB;
use piGallery\model\Helper;
use piGallery\model\ThumbnailManager;
use piGallery\Properties;
use \mysqli;
use \Exception;

/**
 * Class DB
 * @package piGallery\db
 */
class DB_ContentManager {


    /**
     * @param $directory
     * @param $mysqli
     * @return Photo[]
     * @throws \Exception
     */
    private static function loadSamplePhotosToDirectory($directory, $mysqli){

        $photos = array();
        $stmt = $mysqli->prepare("SELECT  p.ID, p.fileName, p.width, p.height
                                    FROM photos p
                                    WHERE
                                    p.directory_id = ?
                                    LIMIT 0,5 ");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $dirId = $directory->getId();
        $stmt->bind_param('i', $dirId);
        $stmt->execute();
        $stmt->bind_result($id, $fileName, $width, $height);

        $directoryPath = Helper::concatPath($directory->getPath(),$directory->getDirectoryName());

        while($stmt->fetch()) {
            $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                Helper::relativeToImageDirectory(Helper::concatPath($directoryPath, $fileName)));
             array_push($photos, new Photo($id, $directoryPath, $fileName, $width, $height, null, $availableThumbnails));
         }
        $stmt->close();

        return $photos;
    }





    /**
     * @param string $path
     * @return array
     * @throws \Exception
     */
    public static function getDirectoryContent($path = DIRECTORY_SEPARATOR){
        $directories = array();
        $photos = array();
        $path_parts = pathinfo($path);


        $mysqli = DB::getDatabaseConnection();

        //load directories
        $stmt = $mysqli->prepare("SELECT d.ID, d.directoryName,  d.lastModification
                                    FROM directories d
                                    WHERE
                                    d.path = ?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->bind_param('s', $path);
        $stmt->execute();
        $stmt->bind_result($dirID, $baseName, $dirLastMod);
        while($stmt->fetch()){
            array_push($directories, new Directory($dirID, $path, $baseName, $dirLastMod, null));
        }
        $stmt->close();

        //get sample photos for directories
        foreach($directories as $dir){
            $dir->setSamplePhotos(DB_ContentManager::loadSamplePhotosToDirectory($dir, $mysqli));
        }

        //load photos
        $stmt = $mysqli->prepare("SELECT p.ID, p.fileName, p.width, p.height, p.keywords
                                    FROM directories d, photos p
                                    WHERE
                                    d.path = ? AND
                                    d.directoryName = ? AND
                                    d.ID = p.directory_id");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $dirName = Helper::toDirectoryPath($path_parts['dirname']);
        $baseName = Helper::toDirectoryPath($path_parts['basename']);

        if(empty($baseName)){
            $baseName = $dirName;
            $dirName = "";
        }

        $stmt->bind_param('ss', $dirName, $baseName);
        $stmt->execute();
        $stmt->bind_result($photoID, $fileName, $width, $height, $keywords);
        while($stmt->fetch()){
            $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                Helper::relativeToImageDirectory(Helper::concatPath($path, $fileName)));
            array_push($photos, new Photo($photoID, $path, $fileName, $width, $height, explode(",", $keywords), $availableThumbnails));
        }
        $stmt->close();



        $mysqli->close();
        return array("currentPath" => $path ,"directories" => $directories , "photos" => $photos);
    }





    public static function getSearchResult($searchString){
        $SQLsearchText = '%' . $searchString . '%';

        $photos = array();
        $directories = array();

        $mysqli = DB::getDatabaseConnection();
        //look in keywords
        $stmt = $mysqli->prepare("SELECT
                                    p.ID, d.path, d.directoryName, p.fileName, p.width, p.height, p.keywords
                                    from photos p, directories d
                                    WHERE
                                    UPPER(p.keywords) LIKE UPPER(?) AND
                                    d.ID = p.directory_id");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('s', $SQLsearchText);
        $stmt->execute();
        $stmt->bind_result($photoID, $DirPath, $directoryName, $fileName, $width, $height, $keywords);
        while($stmt->fetch()){
            $path = Helper::concatPath($DirPath, $directoryName);
            $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                Helper::relativeToImageDirectory(Helper::concatPath($path, $fileName)));
            array_push($photos, new Photo($photoID, $path, $fileName, $width, $height, explode(",", $keywords), $availableThumbnails));
        }

        $stmt->close();

        //load photos
        $stmt = $mysqli->prepare("SELECT
                                    p.ID, d.path, d.directoryName, p.fileName, p.width, p.height, p.keywords
                                    from photos p, directories d
                                    WHERE
                                    UPPER(p.fileName) LIKE UPPER(?) AND
                                    d.ID = p.directory_id");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('s', $SQLsearchText);
        $stmt->execute();
        $stmt->bind_result($photoID, $DirPath, $directoryName, $fileName, $width, $height, $keywords);
        while($stmt->fetch()){
            $path = Helper::concatPath($DirPath, $directoryName);
            $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                Helper::relativeToImageDirectory(Helper::concatPath($path, $fileName)));
            array_push($photos, new Photo($photoID, $path, $fileName, $width, $height, explode(",", $keywords), $availableThumbnails));
        }

        $stmt->close();

        //load photos
        $stmt = $mysqli->prepare("SELECT
                                    directories.ID, directories.path, directories.directoryName,  directories.lastModification
                                    FROM
                                    directories
                                    WHERE
                                    UPPER(directories.directoryName) LIKE UPPER(?) ");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('s', $SQLsearchText);
        $stmt->execute();
        $stmt->bind_result($dirID, $dirPath, $baseName, $dirLastMod);
        while($stmt->fetch()){
            array_push($directories, new Directory($dirID, $dirPath, $baseName, $dirLastMod, null));
        }
        //get sample photos for directories
        foreach($directories as $dir){
            $dir->setSamplePhotos(DB_ContentManager::loadSamplePhotosToDirectory($dir, $mysqli));
        }

        $stmt->close();
        $mysqli->close();

        return array("searchString" => $searchString ,"directories" => $directories , "photos" => $photos);

    }



    private static function isKeywordAlreadyAdded($keyword,$array){
        foreach($array as $item){
            if($item['text'] == $keyword){
                return TRUE;
            }
        }
        return FALSE;
    }

    public static function getAutoComplete($searchText, $count ){
        $SQLsearchText = '%' . $searchText . '%';

        $foundKeywords = array();
        $foundImages = array();
        $foundDirectories = array();

        $mysqli = DB::getDatabaseConnection();
        //look in keywords
        $stmt = $mysqli->prepare("SELECT
                                    photos.keywords
                                    from photos
                                    WHERE
                                    UPPER(photos.keywords) LIKE UPPER(?)
                                    GROUP BY keywords
                                    LIMIT 0,100");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('s', $SQLsearchText);
        $stmt->execute();
        $stmt->bind_result($DBkeywords);
        while($stmt->fetch()){
            $keywords =  explode(",", $DBkeywords);
            foreach($keywords as $keyword) {
                if (stripos($keyword, $searchText) !== FALSE && DB_ContentManager::isKeywordAlreadyAdded($keyword, $foundKeywords) == FALSE) {
                    array_push($foundKeywords, array("text" => $keyword, "type" => "keyword"));
                }
                if (count($foundKeywords) >= $count)
                    break;
            }
        }
        $stmt->close();

        //load photos
        $stmt = $mysqli->prepare("SELECT
                                    photos.fileName
                                    from photos
                                    WHERE
                                    UPPER(photos.fileName) LIKE UPPER(?)
                                    GROUP BY fileName
                                    LIMIT 0,?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('si', $SQLsearchText, $count);
        $stmt->execute();
        $stmt->bind_result($filename);
        while($stmt->fetch()) {
            if (DB_ContentManager::isKeywordAlreadyAdded($filename, $foundImages) == FALSE) {
                array_push($foundImages, array("text" => $filename, "type" => "photo"));
            }
        }

        $stmt->close();

        //load photos
        $stmt = $mysqli->prepare("SELECT
                                    directories.directoryName
                                    FROM
                                    directories
                                    WHERE
                                    UPPER(directories.directoryName) LIKE UPPER(?)
                                    GROUP BY directoryName
                                    LIMIT 0,?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('si', $SQLsearchText, $count);
        $stmt->execute();
        $stmt->bind_result($filename);
        while($stmt->fetch()) {
            if (DB_ContentManager::isKeywordAlreadyAdded($filename, $foundDirectories) == FALSE) {
                array_push($foundDirectories, array("text" => $filename, "type" => "dir"));
            }
        }

        $stmt->close();
        $mysqli->close();

        $sum = count($foundKeywords) + count($foundImages) + count($foundDirectories);
        if($sum > $count){
            $prop = 1- ($count / $sum);
            $remove = floor(count($foundKeywords) * $prop);
            array_splice($foundKeywords, -$remove, $remove);

            $remove = floor(count($foundImages) * $prop);
            array_splice($foundImages, -$remove, $remove);

            $remove = floor(count($foundDirectories) * $prop);
            array_splice($foundDirectories, -$remove, $remove);

        }
        $foundItems = array_merge($foundKeywords, $foundImages, $foundDirectories);

        return $foundItems;

    }
} 
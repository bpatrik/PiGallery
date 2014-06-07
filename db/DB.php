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

    private static function getDatabaseConnection(){

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

            $mysqli = DB::getDatabaseConnection();

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

    private static function loadDirectory($path, $mysqli){

        $path_parts = pathinfo($path);
        $directory = null;
        $stmt = $mysqli->prepare("SELECT id,lastModification FROM directories WHERE path = ? AND directoryName = ?");
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
        $stmt->bind_result($dirID,  $dirLastMod);
        if($stmt->fetch()){
            $directory = new Directory($dirID, $dirName, $baseName, $dirLastMod, null);
        }
        $stmt->close();

        return $directory;
    }

    /**
     * @param $directory Directory
     * @param $mysqli
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

    private static function saveDirectory($dirName, $baseName, $mysqli){

        if(empty($baseName)){
            $baseName = $dirName;
            $dirName = "";
        }

        $directory = null;
        $stmt = $mysqli->prepare("INSERT INTO directories (path, directoryName, lastModification) VALUES (?, ?, NOW())");

        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $dirName = Helper::toDirectoryPath($dirName);
        $baseName = Helper::toDirectoryPath($baseName);

        $stmt->bind_param('ss', $dirName, $baseName);
        $stmt->execute();

        $directory = new Directory($stmt->insert_id, $dirName, $baseName, null, null);
        $stmt ->close();
        return $directory;
    }

    public static function scanDirectory($path = "/"){
        $currentPath = $path;
        $path = Helper::toDirectoryPath($path);

        //is image folder already added?
        if(!Helper::isSubPath(Helper::getAbsoluteImageFolderPath(),Properties::$imageFolder)){
            $path = Helper::concatPath(Helper::getAbsoluteImageFolderPath(),$path);
        }

        if(!is_dir($path)){
            throw new \Exception('No such directory: '.$path);
        }


        $mysqli = DB::getDatabaseConnection();

        //read current directory
        $currentDirectory = DB::loadDirectory($currentPath, $mysqli);
        if($currentDirectory == null){
            $path_parts = pathinfo($currentPath);
            $currentDirectory = DB::saveDirectory($path_parts['dirname'],$path_parts['basename'], $mysqli);
        }

        $foundDirectories = array();
        $handle = opendir($path);
        while (false !== ($value = readdir($handle))) {
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                //read directory
                if(is_dir($contentPath) == true){
                    array_push($foundDirectories, DB::saveDirectory($currentPath, $value, $mysqli));

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

                    $stmt = $mysqli->prepare("INSERT INTO photos (directory_id, fileName, width, height, keywords) VALUES (?, ?, ?, ?, ?)");

                    if($stmt === false) {
                        closedir($handle);
                        $error = $mysqli->error;
                        $mysqli->close();
                        throw new \Exception("Error: ". $error);
                    }
                    $keywordsStr = implode(",", $keywords);
                    $currentDirPath = $currentDirectory->getId();
                    $stmt->bind_param('isiis', $currentDirPath, $value, $width, $height, $keywordsStr);
                    $stmt->execute();
                    $stmt->close();

                }
            }
        }
        closedir($handle);
        $mysqli->close();
        return array("directories" => $foundDirectories);
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    public static function reScanDirectory($path = "/"){

        $mysqli = DB::getDatabaseConnection();
        $path = Helper::toDirectoryPath($path);
        $insertDirectoryQuery = "DELETE FROM directories WHERE path = ?";
        $stmt = $mysqli->prepare($insertDirectoryQuery);

        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->bind_param('s', $path);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();

        $result =  DB::scanDirectory($path);

        //TODO: think again
        foreach($result['directories'] as $dir){
            $fullPath = Helper::concatPath($dir->getPath(),$dir->getDirectoryName());
            DB::reScanDirectory($fullPath);
        }
    }


    /**
     * @param string $path
     * @return array
     * @throws \Exception
     */
    public static function getDirectoryContent($path = "/"){
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
            $dir->setSamplePhotos(DB::loadSamplePhotosToDirectory($dir, $mysqli));
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



    public static function getAutoComplete($searchText, $count ){
        $SQLsearchText = '%' . $searchText . '%';

        $foundKeywords = array();
        $foundImages = array();

        $mysqli = DB::getDatabaseConnection();
        //look in keywords
        $stmt = $mysqli->prepare("SELECT
                                    photos.keywords
                                    from photos
                                    WHERE
                                    photos.keywords LIKE ?
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
                if (stripos($keyword, $searchText) !== FALSE && array_search($keyword, $foundKeywords) == FALSE) {
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
                                        photos.fileName LIKE ?
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
                if (array_search($filename, $foundImages) == FALSE) {
                    array_push($foundImages, array("text" => $filename, "type" => "photo"));
                }
            }

        $stmt->close();
        $mysqli->close();

        $foundItems = array_merge($foundKeywords, $foundImages);

        return $foundItems;

    }
} 
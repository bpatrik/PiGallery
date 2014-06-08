<?php

namespace piGallery\db;

require_once __DIR__ ."/../config.php";
require_once __DIR__ ."/DB_UserManager.php";
require_once __DIR__ ."/entities/Photo.php";
require_once __DIR__ ."/entities/Role.php";
require_once __DIR__ ."/entities/Directory.php";
require_once __DIR__ ."/../model/ThumbnailManager.php";


use piGallery\db\entities\Directory;
use piGallery\db\entities\Photo;
use piGallery\db\entities\User;
use piGallery\db\entities\Role;
use piGallery\db\DB_UserManager;
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

    public static function getDatabaseConnection(){

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
        $dropDirectoryTableSql = "DROP TABLE directories";

        $dropUsersTableSQL = "DROP TABLE users";
        $dropSessionIDTableSQL = "DROP TABLE sessionids";

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


        $createUsersTableSQL = "CREATE TABLE users
                                    (
                                        ID INT NOT NULL AUTO_INCREMENT,
                                        PRIMARY KEY(ID),
                                        userName NVARCHAR(128),
                                        password NVARCHAR(128),
                                        passwordSalt NVARCHAR(128),
                                        role TINYINT,
                                        UNIQUE (userName)
                                    )";


        $createSessionIDTableSQL = "CREATE TABLE sessionids
                                    (
                                        ID INT NOT NULL AUTO_INCREMENT,
                                        PRIMARY KEY(ID),
                                        user_id INT,
                                        session_id NVARCHAR(128),
                                        timestamp TIMESTAMP,
                                        validTime DATETIME,
                                        FOREIGN KEY (user_id)
                                            REFERENCES users(ID)
                                            ON DELETE CASCADE
                                    )";



        $mysqli = DB::getDatabaseConnection();

        //Dropping table
        $mysqli->query($dropPhotoTableSql);
        $mysqli->query($dropDirectoryTableSql);

        $mysqli->query($dropSessionIDTableSQL);
        $mysqli->query($dropUsersTableSQL);

        //Creating table
        if(!$mysqli->query($createDirectoryTableSQL)){
            $mysqli->close();
            throw new Exception("Error: ". $mysqli->error);
        }

        if(!$mysqli->query($createPhotoTableSQL)){
            $mysqli->close();
            throw new Exception("Error: ". $mysqli->error);
        }

        if(!$mysqli->query($createUsersTableSQL)){
            $mysqli->close();
            throw new Exception("Error: ". $mysqli->error);
        }

        if(!$mysqli->query($createSessionIDTableSQL)){
            $mysqli->close();
            throw new Exception("Error: ". $mysqli->error);
        }

        DB_UserManager::register(new User("admin", "admin", Role::Admin));
        $mysqli->close();

        return "ok";


    }

    private static function loadDirectory($dirName, $baseName, $mysqli){

        if(empty($baseName)){
            $baseName = $dirName;
            $dirName = "";
        }
        $dirName = Helper::toDirectoryPath($dirName);
        $baseName = Helper::toDirectoryPath($baseName);
        $directory = null;

        $stmt = $mysqli->prepare("SELECT id,lastModification FROM directories WHERE path = ? AND directoryName = ?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
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

    private static function isPhotoExist($directory, $fileName, $mysqli){

        $found = false;

        $stmt = $mysqli->prepare("SELECT id FROM photos WHERE directory_id  = ? AND fileName = ?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $dirId = $directory->getId();
        $stmt->bind_param('is', $dirId, $fileName);
        $stmt->execute();
        $stmt->bind_result($dirIDd);
        if($stmt->fetch()){
            $found = true;
        }

        $stmt->close();

        return $found;
    }



    private static function saveDirectory($dirName, $baseName, $mysqli){

        if(empty($baseName)){
            $baseName = $dirName;
            $dirName = "";
        }
        $dirName = Helper::toDirectoryPath($dirName);
        $baseName = Helper::toDirectoryPath($baseName);
        $directory = null;

        $stmt = $mysqli->prepare("INSERT INTO directories (path, directoryName, lastModification) VALUES (?, ?, NOW())");

        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }


        $stmt->bind_param('ss', $dirName, $baseName);
        $stmt->execute();

        $directory = new Directory($stmt->insert_id, $dirName, $baseName, null, null);
        $stmt ->close();
        return $directory;
    }



    public static function indexDirectory($path = "/"){
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
        $path_parts = pathinfo($currentPath);
        $currentDirectory = DB::loadDirectory($path_parts['dirname'],$path_parts['basename'], $mysqli);
        if($currentDirectory == null){
            $currentDirectory = DB::saveDirectory($path_parts['dirname'],$path_parts['basename'], $mysqli);
        }

        $foundDirectories = array();
        $handle = opendir($path);
        while (false !== ($value = readdir($handle))) {
            if($value != "." && $value != ".."){
                $contentPath = Helper::concatPath($path,$value);
                //read directory
                if(is_dir($contentPath) == true) {
                    $directory = DB::loadDirectory($currentPath, $value, $mysqli);
                    if ($directory == null) {
                        $directory = DB::saveDirectory($currentPath, $value, $mysqli);
                    }
                    array_push($foundDirectories, Helper::concatPath($directory->getPath(),$directory->getDirectoryName()));
                //read photo
                }else{
                    if(DB::isPhotoExist($currentDirectory,$value, $mysqli ) == true)
                        continue;

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
        return array("foundDirectories" => $foundDirectories);
    }


    public static function clearDatabase(){
        $mysqli = DB::getDatabaseConnection();
        $insertDirectoryQuery = "DELETE FROM directories";
        $stmt = $mysqli->prepare($insertDirectoryQuery);

        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->execute();
        $stmt->close();
        $mysqli->close();

        return "ok";
    }

    /**
     * @param string $path
     * @return string
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

        $result =  DB::indexDirectory($path);

        return $result;
        //TODO: think again
     /*   foreach($result['directories'] as $dir){
            $fullPath = Helper::concatPath($dir->getPath(),$dir->getDirectoryName());
            DB::reScanDirectory($fullPath);
        }*/

    }

}
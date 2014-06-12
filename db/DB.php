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
                                    creationDate DATETIME,
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
                                        lastModification DATETIME,
                                        fileCount INT
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

    public static function isTablesExist(){
        $mysqli = DB::getDatabaseConnection();
        $result = $mysqli->query("SHOW TABLES LIKE 'users'");
        $exist = false;
        if ($result->num_rows > 0) {
            $exist = true;
        }

        $mysqli->close();
        return $exist;
    }

    private static function loadDirectory($dirName, $baseName, $mysqli){

        if(empty($baseName)){
            $baseName = $dirName;
            $dirName = "";
        }
        $dirName = Helper::toDirectoryPath($dirName);
        $baseName = Helper::toDirectoryPath($baseName);
        $directory = null;

        $stmt = $mysqli->prepare("SELECT id, lastModification, fileCount FROM directories WHERE path = ? AND directoryName = ?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('ss', $dirName, $baseName);
        $stmt->execute();
        $stmt->bind_result($dirID,  $dirLastMod, $fileCount);
        if($stmt->fetch()){
            $directory = new Directory($dirID, $dirName, $baseName, $dirLastMod, $fileCount, null);
        }


        $stmt->close();

        return $directory;
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

        $directory = new Directory($stmt->insert_id, $dirName, $baseName,0, null, null);
        $stmt ->close();
        return $directory;
    }



    public static function indexDirectory($path = "/")
    {

        set_time_limit(300); //set time limit for 5 mins
        $currentPath = $path;
        $path = Helper::toDirectoryPath($path);

        //is image folder already added?
        if (!Helper::isSubPath(Helper::getAbsoluteImageFolderPath(), Properties::$imageFolder)) {
            $path = Helper::concatPath(Helper::getAbsoluteImageFolderPath(), $path);
        }

        if (!is_dir($path)) {
            throw new \Exception('No such directory: ' . $path);
        }


        $mysqli = DB::getDatabaseConnection();

        //read current directory
        $path_parts = pathinfo($currentPath);
        $currentDirectory = DB::loadDirectory($path_parts['dirname'], $path_parts['basename'], $mysqli);
        if ($currentDirectory == null) {
            $currentDirectory = DB::saveDirectory($path_parts['dirname'], $path_parts['basename'], $mysqli);
        }

        $foundDirectories = array();
        $foundPhotos = array();
        $readyPhotos = array();

        /*Preload already indexed photos*/
        $stmt = $mysqli->prepare("SELECT
                                    p.fileName
                                    from photos p
                                    WHERE
                                    p.directory_id = ?");
        if ($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: " . $error);
        }
        $dirId = $currentDirectory->getId();
        $stmt->bind_param('i', $dirId);
        $stmt->execute();
        $stmt->bind_result($photoName);
        while ($stmt->fetch()) {
            $readyPhotos[$photoName] = true;
        }

        $stmt->close();
        $handle = opendir($path);
        $directoryFileCount = 0;
        date_default_timezone_set('UTC'); //set it if not set
        while (false !== ($value = readdir($handle))) {
            $directoryFileCount++;
            if ($value != "." && $value != "..") {
                $contentPath = Helper::concatPath($path, $value);
                //read directory
                if (is_dir($contentPath) == true) {
                    $directory = DB::loadDirectory($currentPath, $value, $mysqli);
                    if ($directory == null) {
                        $directory = DB::saveDirectory($currentPath, $value, $mysqli);
                    }
                    array_push($foundDirectories, Helper::concatPath($directory->getPath(), $directory->getDirectoryName()));
                    //read photo
                } else {
                    if (isset($readyPhotos[$value]))
                        continue;

                    list($width, $height, $type, $attr) = getimagesize($contentPath, $info);

                    if ($type != IMAGETYPE_JPEG && $type != IMAGETYPE_PNG && $type != IMAGETYPE_GIF)
                        continue;

                    //loading lightroom keywords
                    $keywords = array();
                    if (isset($info['APP13'])) {
                        $iptc = iptcparse($info['APP13']);

                        if (isset($iptc['2#025'])) {
                            $keywords = $iptc['2#025'];
                        }
                    }
                    $creationDate = date("Y-m-d H:i:s", filectime($contentPath));
                    $keywordsStr = implode(",", $keywords);

                    //saving image for later commit for efficiency
                    array_push($foundPhotos, array("fileName" => $value,
                        "width" => $width,
                        "height" => $height,
                        "creationDate" => $creationDate,
                        "keywords" => $keywordsStr));
                    /*
                                        $stmt = $mysqli->prepare("INSERT INTO photos (directory_id, fileName, width, height, creationDate, keywords) VALUES (?, ?, ?, ?, ?, ?)");

                                        if($stmt === false) {
                                            closedir($handle);
                                            $error = $mysqli->error;
                                            $mysqli->close();
                                            throw new \Exception("Error: ". $error);
                                        }
                                        $currentDirPath = $currentDirectory->getId();
                                        $stmt->bind_param('isiiss', $currentDirPath, $value, $width, $height, $creationDate, $keywordsStr);
                                        $stmt->execute();
                                        $stmt->close();*/

                }
            }

        }
        closedir($handle);

        if (count($foundPhotos) > 0) {
            //Inserting found photos to database
            $query = "INSERT INTO photos (directory_id, fileName, width, height, creationDate, keywords) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $currentDirPath = $currentDirectory->getId();

            $mysqli->query("START TRANSACTION");
            foreach ($foundPhotos as $photoData) {
                $stmt ->bind_param('isiiss', $currentDirPath, $photoData["fileName"], $photoData["width"], $photoData["height"], $photoData["creationDate"], $photoData["keywords"]);
                $stmt->execute();
            }
            $stmt->close();


            //update folder last update time
            $query = "UPDATE directories SET lastModification = NOW() WHERE ID = ?";
            $stmt = $mysqli->prepare($query);
            $currentDirPath = $currentDirectory->getId();

            $stmt ->bind_param('i', $currentDirPath);
            $stmt->execute();
            $stmt->close();

            $mysqli->query("COMMIT");
        }

        //update folder file fount
        if ($currentDirectory->getFileCount() != $directoryFileCount) {
            $query = "UPDATE directories SET fileCount = ? WHERE ID = ?";
            $stmt = $mysqli->prepare($query);
            $currentDirPath = $currentDirectory->getId();

            $stmt ->bind_param('ii', $directoryFileCount, $currentDirPath);
            $stmt->execute();
            $stmt->close();
        }


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
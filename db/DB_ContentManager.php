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
     * @param Directory $directory
     * @param Mysqli $mysqli
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
             array_push($photos, new Photo($id, $directoryPath, $fileName, $width, $height, null, null, $availableThumbnails));
         }
        $stmt->close();

        return $photos;
    }


    /**
     * @param string $path
     * @param null $lastModificationDate
     * @return array
     * @throws \Exception
     */
    public static function getDirectoryContent($path = DIRECTORY_SEPARATOR, $lastModificationDate = null){
        date_default_timezone_set('UTC'); //set it if not set
        /**
         * @var Directory[] $directories
         */
        $directories = array();
        $photos = array();
        $currentDirectory = null;
        $path_parts = pathinfo($path);

        $mysqli = DB::getDatabaseConnection();

        //load current directory
        $dirName = Helper::toDirectoryPath($path_parts['dirname']);
        $baseName = Helper::toDirectoryPath($path_parts['basename']);

        if(empty($baseName)){
            $baseName = $dirName;
            $dirName = "";
        }

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
            $currentDirectory = new Directory($dirID, $dirName, $baseName, $dirLastMod, $fileCount, null);
        }else{
            $currentDirectory =  new Directory(0, $dirName, $baseName, "NaN", 0, null);
        }


        $stmt->close();


        $noChange = false;
        //check if there was an update
        if($lastModificationDate != $currentDirectory->getLastModification()) {

            //load directories
            $stmt = $mysqli->prepare("SELECT d.ID, d.directoryName,  d.lastModification
                                    FROM directories d
                                    WHERE
                                    d.path = ?");
            if ($stmt === false) {
                $error = $mysqli->error;
                $mysqli->close();
                throw new \Exception("Error: " . $error);
            }
            $stmt->bind_param('s', $path);
            $stmt->execute();
            $stmt->bind_result($dirID, $baseName, $dirLastMod);
            while ($stmt->fetch()) {
                array_push($directories, new Directory($dirID, $path, $baseName, $dirLastMod, 0, null));
            }
            $stmt->close();

            //get sample photos for directories
            foreach ($directories as $dir) {
                $dir->setSamplePhotos(DB_ContentManager::loadSamplePhotosToDirectory($dir, $mysqli));
            }

            //load photos
            $stmt = $mysqli->prepare("SELECT p.ID, p.fileName, p.width, p.height, p.creationDate, p.keywords
                                    FROM directories d, photos p
                                    WHERE
                                    d.ID = ? AND
                                    d.ID = p.directory_id");
            if ($stmt === false) {
                $error = $mysqli->error;
                $mysqli->close();
                throw new \Exception("Error: " . $error);
            }


            $currentDirectoryID = $currentDirectory->getId();
            $stmt->bind_param('i', $currentDirectoryID);
            $stmt->execute();
            $stmt->bind_result($photoID, $fileName, $width, $height, $creationDate, $keywords);
            while ($stmt->fetch()) {
                $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                    Helper::relativeToImageDirectory(Helper::concatPath($path, $fileName)));
                array_push($photos, new Photo($photoID, $path, $fileName, $width, $height, explode(",", $keywords), strtotime($creationDate), $availableThumbnails));
            }
            $stmt->close();
        }else{//nothing changed
            $noChange = true;
        }

        $mysqli->close();


        $indexingNeeded = false;
        /*If its enabled, checks if new file was added tot the folder and its not in the db yet*/
        if(Properties::$enableOnTheFlyIndexing == true){

            $scanPath = $path;
            //is image folder already added?
            if(!Helper::isSubPath(Helper::getAbsoluteImageFolderPath(),Properties::$imageFolder)){
                $scanPath = Helper::concatPath(Helper::getAbsoluteImageFolderPath(),$scanPath);
            }

            if(!is_dir($scanPath)){
                throw new \Exception('No such directory: '.$scanPath);
            }

            $fileCount = 0;
            $handle = opendir($scanPath);
            while (false !== ($value = readdir($handle))) {
                $fileCount++;
            }
            closedir($handle);
            $indexingNeeded = $currentDirectory->getFileCount() != $fileCount;

        }

        return array("currentPath" => $path ,
                    "lastModificationDate" => $currentDirectory->getLastModification(),
                    "indexingNeeded" => $indexingNeeded,
                    "noChange" => $noChange,
                    "directories" => $directories ,
                    "photos" => $photos);
    }


    /**
     * @param $searchString
     * @return array
     * @throws Exception
     */
    public static function getSearchResult($searchString){
        date_default_timezone_set('UTC'); //set it if not set
        /**
         * @var Directory[] $directories
         */
        $SQLSearchText = '%' . $searchString . '%';

        $photos = array();
        $directories = array();

        $mysqli = DB::getDatabaseConnection();
        //look in keywords
        $stmt = $mysqli->prepare("SELECT
                                    p.ID, d.path, d.directoryName, p.fileName, p.width, p.height, p.creationDate, p.keywords
                                    from photos p, directories d
                                    WHERE
                                    UPPER(p.keywords) LIKE UPPER(?) AND
                                    d.ID = p.directory_id
                                    LIMIT 0,?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('si', $SQLSearchText,Properties::$maxSearchResultItems);
        $stmt->execute();
        $stmt->bind_result($photoID, $DirPath, $directoryName, $fileName, $width, $height, $creationDate, $keywords);
        while($stmt->fetch()){
            $path = Helper::concatPath($DirPath, $directoryName);
            $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                Helper::relativeToImageDirectory(Helper::concatPath($path, $fileName)));
            array_push($photos, new Photo($photoID, $path, $fileName, $width, $height,  explode(",", $keywords),strtotime($creationDate), $availableThumbnails));
        }

        $stmt->close();

        //load photos
        $stmt = $mysqli->prepare("SELECT
                                    p.ID, d.path, d.directoryName, p.fileName, p.width, p.height, p.creationDate, p.keywords
                                    from photos p, directories d
                                    WHERE
                                    UPPER(p.fileName) LIKE UPPER(?) AND
                                    d.ID = p.directory_id
                                    LIMIT 0,?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('si', $SQLSearchText, Properties::$maxSearchResultItems);
        $stmt->execute();
        $stmt->bind_result($photoID, $DirPath, $directoryName, $fileName, $width, $height, $creationDate, $keywords);
        while($stmt->fetch()){
            $path = Helper::concatPath($DirPath, $directoryName);
            $availableThumbnails = ThumbnailManager::getAvailableThumbnails(
                Helper::relativeToImageDirectory(Helper::concatPath($path, $fileName)));
            array_push($photos, new Photo($photoID, $path, $fileName, $width, $height, explode(",", $keywords), strtotime($creationDate), $availableThumbnails));
        }

        $stmt->close();

        //load directories
        $stmt = $mysqli->prepare("SELECT
                                    directories.ID, directories.path, directories.directoryName,  directories.lastModification
                                    FROM
                                    directories
                                    WHERE
                                    UPPER(directories.directoryName) LIKE UPPER(?)
                                    LIMIT 0,?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('si', $SQLSearchText,Properties::$maxSearchResultItems);
        $stmt->execute();
        $stmt->bind_result($dirID, $dirPath, $baseName, $dirLastMod);
        while($stmt->fetch()){
            array_push($directories, new Directory($dirID, $dirPath, $baseName, $dirLastMod,0, null));
        }
        //get sample photos for directories
        foreach($directories as $dir){
            $dir->setSamplePhotos(DB_ContentManager::loadSamplePhotosToDirectory($dir, $mysqli));
        }

        $stmt->close();
        $mysqli->close();

        /*Limit photos*/
        $tooMuchResults = false;
        $photosCount = count($photos);
        $directoriesCount = count($directories);
        if($photosCount >= Properties::$maxSearchResultItems || $directoriesCount >= Properties::$maxSearchResultItems){
            $tooMuchResults = true;
        }
        //Removing too much results
        if($photosCount > Properties::$maxSearchResultItems){
            $remove = $photosCount - Properties::$maxSearchResultItems;
            array_splice($photos, -$remove, $remove);
        }

        return array("searchString" => $searchString,
                        "tooMuchResults" => $tooMuchResults,
                        "directories" => $directories,
                        "photos" => $photos);

    }

    /**
     * @param string $keyword
     * @param $array
     * @return bool
     */
    private static function isKeywordAlreadyAdded($keyword,$array){
        foreach($array as $item){
            if($item['text'] == $keyword){
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * @param string $searchText
     * @param int $count
     * @return array
     * @throws Exception
     */
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

    private static function generateRandomUrlString($length){
        
        $charOnlySeed = str_split('abcdefghijklmnopqrstuvwxyz'
                                 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $seed = str_split('abcdefghijklmnopqrstuvwxyz'
                            .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                            .'0123456789-._~:/#[]@!$()*+,;='); // and any other characters
        shuffle($charOnlySeed); // probably optional since array_is randomized; this may be redundant
        shuffle($seed); // probably optional since array_is randomized; this may be redundant
        $rand = '';
        if($length >= 2){ //forcing to start and end with characters
            $rand .= $charOnlySeed[array_rand($charOnlySeed)];
            foreach (array_rand($seed, $length - 2) as $k) $rand .= $seed[$k];
            $rand .= $charOnlySeed[array_rand($charOnlySeed)];
            
        }else {
            foreach (array_rand($seed, $length) as $k) $rand .= $seed[$k];
        }
        return $rand;
        
    }

    /**
     * @param User $user
     * @param string $folder
     * @param int $validInterval
     * @param bool $isRecursive
     * @return string
     * @throws Exception
     */
    public static function shareFolder($user, $folder, $validInterval, $isRecursive)
    {

        $folder = Helper::toDirectoryPath($folder);
        $shareId = DB_ContentManager::generateRandomUrlString(6);
        $isRecursive = intval($isRecursive);
        $userId = $user->getId();

        if(!is_dir(Helper::getAbsoluteImageFolderPath($folder))){
            throw new \Exception("Error: '". Helper::getAbsoluteImageFolderPath($folder). "' is not a directory");
        }
        
        $mysqli = DB::getDatabaseConnection();
        
        $stmt = $mysqli->prepare("INSERT INTO sharing (user_id, share_id, path, recursive, validTime) VALUES (?, ?, ?, ? , NOW() + INTERVAL ? HOUR)");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('issis',$userId,$shareId, $folder, $isRecursive, $validInterval );
        $stmt->execute();

        $stmt->close();
        
        return $shareId;
    }
} 
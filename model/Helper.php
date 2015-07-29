<?php

namespace piGallery\model;

use piGallery\Properties;

require_once __DIR__."/../db/entities/Photo.php";
require_once __DIR__."/../config.php";

class Helper {

    public static function require_REQUEST($str){
        if(isset($_REQUEST[$str]) && !empty($_REQUEST[$str])){
            return $_REQUEST[$str];
        }
        die("Error: " .$str . " is required");
    }
    public static function get_REQUEST($str, $default){
        if(isset($_REQUEST[$str]) && !empty($_REQUEST[$str])){
            return $_REQUEST[$str];
        }
        return $default;
    }

    public static function getAbsoluteThumbnailFolderPath(){
        $documentRoot = Helper::concatPath(Helper::toDirectoryPath($_SERVER['DOCUMENT_ROOT']), Properties::$documentRoot);
        return Helper::concatPath($documentRoot,Properties::$thumbnailFolder);
    }

    public static function getAbsoluteImageFolderPath(){
        $documentRoot = Helper::concatPath(Helper::toDirectoryPath($_SERVER['DOCUMENT_ROOT']), Properties::$documentRoot);
        return Helper::concatPath($documentRoot,Properties::$imageFolder);
    }

    public static function relativeToDocumentRoot($absolute){
        $absolute = Helper::toDirectoryPath($absolute);
        $baseDir = Helper::toDirectoryPath($_SERVER['DOCUMENT_ROOT']);
        if(Helper::isSubPath($absolute,$baseDir)){
            return ltrim(str_replace($baseDir,"",$absolute),DIRECTORY_SEPARATOR);
        }
        return $absolute;
    }

    public static function relativeToImageDirectory($absolute){

        $absolute = Helper::toDirectoryPath($absolute);
        $baseDir = Helper::concatPath(Helper::toDirectoryPath($_SERVER['DOCUMENT_ROOT']), Properties::$documentRoot);
        $baseDir = Helper::concatPath($baseDir, Properties::$imageFolder);
        $baseDir = Helper::toDirectoryPath($baseDir);
        if(Helper::isSubPath($absolute,$baseDir)){
            return ltrim(str_replace($baseDir,"",$absolute),DIRECTORY_SEPARATOR);
        }
        return $absolute;
    }

    /**
     * Determines weather the subPath string is part of the path string
     * @param string $path
     * @param string $subPath
     * @return bool
     */
    public static function isSubPath($path, $subPath){
        if(empty($subPath))
            return false;
        $path = Helper::toDirectoryPath($path);
        $subPath = Helper::toDirectoryPath($subPath);

        if(strpos($path, $subPath) === 0 || 
            strpos($path, ".".DIRECTORY_SEPARATOR.$subPath) === 0 || 
            strpos(".".DIRECTORY_SEPARATOR.$path,  $subPath) === 0)
            
            return true;

        return false;
    }
    
    public static function isPathEqual($path1, $path2){

        $path1 = Helper::toDirectoryPath($path1);
        $path2 = Helper::toDirectoryPath($path2);
        return strpos($path1, $path2) === 0;
    }

    public static function toDirectoryPath($path){

        $search  = array("/",
                        "\\",
                         DIRECTORY_SEPARATOR.".".DIRECTORY_SEPARATOR);
        $replace = array(DIRECTORY_SEPARATOR,
                         DIRECTORY_SEPARATOR,
                         DIRECTORY_SEPARATOR);

        $path = str_replace($search, $replace,$path); 
        $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR,$path); //clean duplicated separators
        return $path;
    }

    /**
     * Converts directory path to url path
     * @param $path string
     * @return string
     */
    public static function toURLPath($path){
        return str_replace([".\\","\\","./"],["","/",""],$path);
    }

    /**
     * Concatenate 2 paths and organises directory separators
     * @param $path1
     * @param $path2
     * @return string
     */
    public static function concatPath($path1, $path2){

        $path = $path1. DIRECTORY_SEPARATOR.$path2;
        $path = Helper::toDirectoryPath($path);

     //   $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR,$path); //clean duplicated separators

        return $path;
    }


    public static function contentArrayToJSONable($array){
        if($array == null)
            return $array;

        reset($array);
        $first_key = key($array);
        $convertedPath = Helper::toURLPath($array[$first_key]);
        if (Properties::$enableUTF8Encode && $first_key != "searchString") {
            $convertedPath = utf8_encode($convertedPath );
        }
        $convertedDirectories =  $array['directories'];
        $convertedPhotos =  $array['photos'];

        foreach($convertedDirectories as $directory) {
            $directory->setPath(Helper::toURLPath($directory->getPath()));
            $directory->setDirectoryName(Helper::toURLPath($directory->getDirectoryName()));
            if (Properties::$enableUTF8Encode) {
                $directory->setPath(utf8_encode($directory->getPath()));
                $directory->setDirectoryName(utf8_encode($directory->getDirectoryName()));
            }
            if($directory->getSamplePhotos() != null){
                foreach($directory->getSamplePhotos() as $photo){
                    $photo->setPath(Helper::toURLPath($photo->getPath()));
                    if (Properties::$enableUTF8Encode) {
                        $photo->setPath(utf8_encode($photo->getPath()));
                    }
                }
            }
        }

        foreach($convertedPhotos as $photo){
            $photo->setPath(Helper::toURLPath($photo->getPath()));
            if (Properties::$enableUTF8Encode) {
                $photo->setPath(utf8_encode($photo->getPath()));
            }
        }

        $array_out = array($first_key => $convertedPath ,"directories" => $convertedDirectories , "photos" => $convertedPhotos);

        /*Extra params*/
        if(isset($array['lastModificationDate'])){
            $array_out['lastModificationDate'] = $array['lastModificationDate'];
        }
        if(isset($array['indexingNeeded'])){
            $array_out['indexingNeeded'] = $array['indexingNeeded'];
        }
        if(isset($array['noChange'])){
            $array_out['noChange'] = $array['noChange'];
        }
        if(isset($array['tooMuchResults'])){
            $array_out['tooMuchResults'] = $array['tooMuchResults'];
        }



        //convert to jsonable
        return  Helper::phpObjectArrayToJSONable($array_out);
    }


    public static function contentArrayToJSON($array){

        return  json_encode(Helper::contentArrayToJSONable($array));
    }

    public static function phpObjectArrayToJSONable($array){

        $JSON_array = array();

        foreach($array as $key => $value){
            if(is_array($value)){
                $tmp_array = array();
                foreach ($value as $row) {
                    if(is_object($row) && method_exists($row,'getJsonData')){
                        $row = $row->getJsonData();
                    }
                    $tmp_array[] =  $row;
                }
                $JSON_array[$key] = $tmp_array;
            }else{
                if(is_object($value) && method_exists($value,'getJsonData')){
                    $value = $value->getJsonData();
                }
                $JSON_array[$key] = $value;
            }

        }

        return  $JSON_array;
    }

    /**
     * Check if a client IP is in our Server subnet
     *
     * @param string|bool $client_ip
     * @param string|bool $server_ip
     * @return bool
     */
     public static function isClientInSameSubnet($client_ip = false, $server_ip = false)
     {
         if (!$client_ip)
             $client_ip = $_SERVER['REMOTE_ADDR'];
         if (!$server_ip)
             $server_ip = $_SERVER['SERVER_ADDR'];
         // Extract broadcast and netmask from ifconfig
         $read = false;
         $regs = array();
         if (($p = popen("ifconfig", "r"))) {
             $out = "";
             while (!feof($p))
                 $out .= fread($p, 1024);
             fclose($p);
             // This is because the php.net comment function does not
             // allow long lines.
             $match = "/^.*" . $server_ip;
             $match .= ".*Bcast:(\d{1,3}\.\d{1,3}i\.\d{1,3}\.\d{1,3}).*";
             $match .= "Mask:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/im";
             if (preg_match($match, $out, $regs)) {
                 $read = true;
             }
         }
        if(!$read){
             $trimmed = implode(".", array_slice(explode(".", $server_ip), 0, 2));
             $regs = array("", $trimmed.".0.255", "255.255.255.0");
        }
        $bcast = ip2long($regs[1]);
        $smask = ip2long($regs[2]);
        $ipadr = ip2long($client_ip);
        $nmask = $bcast & $smask;
        return (($ipadr & $smask) == ($nmask & $smask));
    }


    /**
     * @param string $filename
     * @return string
     */
    public static function imageToMime($filename) {

        $mime_types = array(
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
        );

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }

        return 'image/jpeg';
    }
} 
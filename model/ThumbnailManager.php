<?php

namespace piGallery\model;

use piGallery\db\entities\ThumbnailInfo;
use piGallery\Properties;

require_once __DIR__."/../config.php";
require_once __DIR__."/../db/entities/ThumbnailInfo.php";

class ThumbnailManager {



    private static function getThumbnailFileName($pathToImage, $size){
        return md5($pathToImage.$size);
    }

    private static function createThumbnail($pathToImage, $size){

       // Logger::v("ThumbnailManager::createThumbnail","creating thumbnail: " . $pathToImage);
        $pixelCount = $size * $size;

        $outFileName = ThumbnailManager::getThumbnailFileName($pathToImage,$size);

        // load image and get image size
        list($width, $height, $type) = @getimagesize($pathToImage);

        if($width < 1) return false;

        $scale = sqrt($pixelCount / ($width * $height));
        if($scale > 1) //do not scale up
            $scale = 1;

        $src_img = null;
        // find image type and create temp image and variable
        if ($type == IMAGETYPE_JPEG) {
            $src_img = @imagecreatefromjpeg($pathToImage);
        } else if ($type == IMAGETYPE_GIF) {
            $src_img = @imagecreatefromgif($pathToImage);
        } else if ($type == IMAGETYPE_PNG) {
            $src_img = @imagecreatefrompng($pathToImage );
        }
        if(!$src_img) return false;



        // calculate thumbnail size
        $new_width  = floor( $width  * $scale );
        $new_height = floor( $height * $scale );
        // create a new temporary image
        $tmp_img = imagecreatetruecolor( $new_width, $new_height );

        if($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG){
            // create temp image
            $tmp_img = imagecreatetruecolor($new_width, $new_height);
            $white = imagecolorallocate($tmp_img, 255, 255, 255);
            imagefill($tmp_img, 0, 0, $white);

         /*   // make the new temp image all transparent
            imagecolortransparent($tmp_img, $white);
            imagealphablending($tmp_img, false);
            imagesavealpha($tmp_img, true);*/
        }

        // copy and resize old image into new image

        if(Properties::$EnableThumbnailResample){
            imagecopyresampled($tmp_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height ); //better quality than simple resize
        }else{
            imagecopyresized( $tmp_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
        }


        // save thumbnail into a file
        imagejpeg( $tmp_img, Helper::concatPath(Helper::getAbsoluteThumbnailFolderPath(),$outFileName.".jpg"), Properties::$thumbnailJPEGQuality  );

        //freeup
        imagedestroy($tmp_img);
    }

    /**
     * Checks if the thumbnail to the given image exist
     * @param $absolutePathToImage
     * @param $size
     * @return bool
     */
    private static function isThumbnailExist($absolutePathToImage, $size){
        $fileName = ThumbnailManager::getThumbnailFileName($absolutePathToImage,$size);
        return file_exists(Helper::concatPath(Helper::getAbsoluteThumbnailFolderPath(),$fileName.".jpg"));
    }

    /**
     * @param $relativePathToImage
     * @return ThumbnailInfo[]
     */
    public static function getAvailableThumbnails($relativePathToImage){
        $absolutePathToImage = Helper::concatPath(Helper::getAbsoluteImageFolderPath(), $relativePathToImage);
        $availableThumbnails = array();
        foreach(Properties::$thumbnailSizes as $size){
            $exist = ThumbnailManager::isThumbnailExist($absolutePathToImage,$size);
            $availableThumbnails[]= new ThumbnailInfo($size,$exist);
        }

        return $availableThumbnails;
    }

    public static function requestThumbnail($pathToImage, $size){

        $pathToImage = Helper::concatPath(Helper::getAbsoluteImageFolderPath(), $pathToImage);
		

        if (!ThumbnailManager::isThumbnailExist($pathToImage, $size)){
            ThumbnailManager::createThumbnail($pathToImage,$size);
        }

        $fileName = ThumbnailManager::getThumbnailFileName($pathToImage,$size);
        $thumbnailPath = Helper::concatPath(Helper::getAbsoluteThumbnailFolderPath(),$fileName.".jpg");
		
        $image = file_get_contents($thumbnailPath);

        if($image !== false){ //touching files -> means it was used
            touch ($thumbnailPath );
        }

        return array("image" => $image, "filesSze" => filesize($thumbnailPath));
    }

} 
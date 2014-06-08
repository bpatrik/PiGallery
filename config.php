<?php
namespace piGallery;

require_once __DIR__."/db/entities/User.php";
require_once __DIR__."/db/entities/Role.php";

use piGallery\db\entities\Role;


class Properties{
    /* The language of the site, pick one from the lang directory.
     * If your language not exist, translate from eng :) */
    public static $language = "hun";

    /*The base directory relative to the document root (the www folder)*/
	public static $documentRoot  = "PiGallery";

    /*The image directory relative to the base folder (the www folder)*/
    public static $imageFolder = "./testimages";

    /*-----------Thumbnail settings--------------*/
    /*The thumbnail folder relative to the base folder. !IMPORTANT the folder must be writable! */
    public static $thumbnailFolder = "./thumbnails";
    /*The thumbnail sizes that the site generates automatically. (Thumbnail generation is a long process, give only 1 or 2 sizes only)*/
    public static $thumbnailSizes = array(200, 500);
    /*The JPEG quality of the thumbnail*/
    public static $thumbnailJPEGQuality = 75;
    /*Set true for resampling or false for resizing only. */
    public static $EnableThumbnailResample = true;


    /*Enables thumbnail and image caching for the browser, true is recommended.*/
    public static $enableImageCaching = false;

    /*Set it false, if the directory names don't appear properly*/
    public static $enableUTF8Encode = true;


    /*Enable the database usage*/
    public static $databaseEnabled = true;

    /*-------------Database settings----------*/
    /*if $databaseEnabled == true*/
    public static $databaseAddress = "localhost";
    public static $databseUserName = "root";
    public static $databsePassword = "root";
    public static $databseName = "pigallery";

    /*No-Database settings*/
    /*if $databaseEnabled == false*/
    /*Following roles are available:
     * - User -- code: 0
     * - Admin -- code: 1
     * */
    public static $users = array(
        array("userName" => "admin", "password" => "admin", "role" => Role::Admin)
    );

}
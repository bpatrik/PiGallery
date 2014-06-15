<?php
namespace piGallery;

require_once __DIR__."/db/entities/User.php";
require_once __DIR__."/db/entities/Role.php";

use piGallery\db\entities\Role;


class Properties{
    /* The language of the site, pick one from the lang directory.
     * If your language not exist, translate from eng :) */
    public static $language = "eng";

    /*The base directory relative to the document root (the www folder)
    Eg.: If the site folder is /var/www/PiGallery than set is 'PiGallery'*/
	public static $documentRoot  = "PiGallery";

    /*The image directory relative to the base folder (the www folder)
    Eg.: If the images folder is /var/www/PiGallery/testimages than set is './testimages'
    Note: it should be relative to the site root.
          If your folder is somewhere else, create a link to the images folder in your site root directory*/
    public static $imageFolder = "./testimages";

    /*-----------Thumbnail settings--------------*/
    /*The thumbnail folder relative to the base folder. !IMPORTANT the folder must be writable!
    Eg.: If the images folder is /var/www/PiGallery/thumbnails than set is './thumbnails'
    Note: it should be relative to the site root.
          If your folder is somewhere else, create a link to the thumbnail folder in your site root directory*/
    public static $thumbnailFolder = "./thumbnails";
    /*The thumbnail sizes that the site generates automatically. (Thumbnail generation is a long process, give only 1 or 2 sizes only)*/
    public static $thumbnailSizes = array(100,300, 500);
    /*The JPEG quality of the thumbnail*/
    public static $thumbnailJPEGQuality = 75;
    /*Set true for resampling or false for resizing only. (true: nice thumbnails, false: better performance)*/
    public static $EnableThumbnailResample = true;


    /*Enables thumbnail and image caching for the browser, true is recommended.*/
    public static $enableImageCaching = true;

    /*Set it true, if the directory names don't appear properly*/
    public static $enableUTF8Encode = true;


    /*Enable the database usage*/
    public static $databaseEnabled = true;

    /*-------------Database settings----------*/
    /*if $databaseEnabled == true*/
    public static $databaseAddress = "localhost";
    public static $databseUserName = "root";
    public static $databsePassword = "root";
    public static $databseName = "pigallery";

    /*If its true, the site will check at every directory open if indexing is needed or not.
     if need, the site will index the given folder automatically*/
    public static $enableOnTheFlyIndexing = true;

    /*No-Database settings*/
    /*if $databaseEnabled == false*/
    /*Following roles are available:
     * - User -- code: 0
     * - Admin -- code: 1
     * */
    public static $users = array(
        array("userName" => "admin", "password" => "admin", "role" => Role::Admin)
    );
    /*NOTE: these uses are not used in database mode, at database mode the default user is user:admin, pass:admin*/

}
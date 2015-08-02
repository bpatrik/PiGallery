<?php
namespace piGallery;

require_once __DIR__."/db/entities/User.php";
require_once __DIR__."/db/entities/Role.php";

use piGallery\db\entities\Role;


class Properties{
    /*Set it false if you edit it manually. If its true the setup page will show on loading the page*/
    public static $installerWizardEnabled = false;

    /* The language of the site, pick one from the lang directory.
     * If your language not exist, translate from eng :) */
    public static $language = "eng";

    /*The site url. This path leads to the index.php.
    Example:
        1) https://www.mysite.com/gallery/
        2) http://localhost/
    */
    public static $siteUrl = "http://localhost:8080/PiGallery/";
    
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
    Eg.: If the thumbnail folder is /var/www/PiGallery/thumbnails than set is './thumbnails'
    Note: it should be relative to the site root.
          If your folder is somewhere else, create a link to the thumbnail folder in your site root directory*/
    public static $thumbnailFolder = "./thumbnails";
    /*The thumbnail sizes that the site generates automatically. (Thumbnail generation is a long process, give only 1 or 2 sizes only)*/
    public static $thumbnailSizes = array(300, 500);
    /*The JPEG quality of the thumbnail*/
    public static $thumbnailJPEGQuality = 75;
    /*Set true for resampling or false for resizing only. (true: nice thumbnails, false: better performance)*/
    public static $EnableThumbnailResample = true;


    /*Enables thumbnail and image caching for the browser, true is recommended.*/
    public static $enableImageCaching = true;

    /*Set it true, if the directory names don't appear properly*/
    public static $enableUTF8Encode = true;


    /*Enable the database usage*/
    public static $databaseEnabled = false;

    /*-------------Database settings----------*/
    /*if $databaseEnabled == true*/
    public static $databaseAddress = "localhost";
    public static $databaseUserName = "root";
    public static $databasePassword = "root";
    public static $databaseName = "pigallery";

    /*If its true, the site will check at every directory open if indexing is needed or not.
     if need, the site will index the given folder automatically*/
    public static $enableOnTheFlyIndexing = true;

    /*If its true, a search box with autocomplete will appefar in the menu bar (only works in db-mode)*/
    public static $enableSearching = true;

    /*If its true, a sharing button will appear in the menu bar (only works in db-mode)*/
    public static $enableSharing = true;

    /*The max number of results, that a search can give. */
    public static $maxSearchResultItems = 500;

    /* Enabling Guest Login
        Only use it if the server is not put directly to the internet*/
    public static $GuestLoginAtLocalNetworkEnabled = false;

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

<?php
require_once __DIR__."/../model/Helper.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/../model/AuthenticationManager.php";
require_once __DIR__."/../db/entities/Role.php";
require_once __DIR__."/../db/DB_ContentManager.php";
require_once __DIR__."/../config.php";
require_once __DIR__."/../db/entities/AjaxError.php";
require_once __DIR__."/../lang/eng.php";

use piGallery\db\entities\Role;
use piGallery\db\entities\User;
use piGallery\model\AuthenticationManager;
use piGallery\Properties;

require_once __DIR__."/../db/entities/Role.php";

function home_base_url(){

    // first get http protocol if http or https
    $base_url = (isset($_SERVER['HTTPS']) &&  $_SERVER['HTTPS']!='off') ? 'https://' : 'http://';

    // get default website root directory
    $tmpURL = dirname(__FILE__);

    // when use dirname(__FILE__) will return value like this "C:\xampp\htdocs\my_website",
    //convert value to http url use string replace,
    // replace any backslashes to slash in this case use chr value "92"
    $tmpURL = str_replace(chr(92),'/',$tmpURL);

    // now replace any same string in $tmpURL value to null or ''
    // and will return value like /localhost/my_website/ or just /my_website/
    $tmpURL = str_replace($_SERVER['DOCUMENT_ROOT'],'',$tmpURL);

    // delete any slash character in first and last of value
    $tmpURL = ltrim($tmpURL,'/');
    $tmpURL = rtrim($tmpURL, '/');
    $tmpURL = rtrim($tmpURL, 'setup');
    $tmpURL = rtrim($tmpURL, '/');

    // now last steps  assign protocol in first value

    if ($tmpURL !== $_SERVER['HTTP_HOST']) {

        // if protocol its http then like this
        $base_url .= $_SERVER['HTTP_HOST'] . '/' . $tmpURL . '/';
    }else{

        // else if protocol is https
        $base_url .= $tmpURL.'/';
    }

    // give return value

    return $base_url;

}

function documentRoot(){

    // get default website root directory
    $tmpURL = dirname(__FILE__);

    // when use dirname(__FILE__) will return value like this "C:\xampp\htdocs\my_website",
    //convert value to http url use string replace,
    // replace any backslashes to slash in this case use chr value "92"
    $tmpURL = str_replace(chr(92),'/',$tmpURL);

    // now replace any same string in $tmpURL value to null or ''
    // and will return value like /localhost/my_website/ or just /my_website/
    $tmpURL = str_replace($_SERVER['DOCUMENT_ROOT'],'',$tmpURL);

    // delete any slash character in first and last of value
    $tmpURL = ltrim($tmpURL,'/');
    $tmpURL = rtrim($tmpURL, '/');

    $tmpURL = rtrim($tmpURL, 'setup');
    $tmpURL = rtrim($tmpURL, '/');



    return $tmpURL;
}
function isGD(){
    if (extension_loaded('gd') && function_exists('gd_info')) {
       return true;
    }
    else {
        return false;
    }
}
/**
 * @param int $role
 * @return null|User
 */
function authenticate($role = Role::User) {
    if(Properties::$installerWizardEnabled){
        return new User("Guest", null, Role::Admin);
    }
    /*Authenticating*/
    require_once __DIR__."/../model/AuthenticationManager.php";
    require_once __DIR__."/../db/entities/Role.php";

    /*Authentication need for images*/
    $user = AuthenticationManager::authenticate($role);
    if(is_null($user)){
        return null;
    }
    return $user;
}

if(authenticate(Role::Admin) == null){
    die("Enable installer wizard in the config.php or login as ADMIN");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Patrik Braun">
    <link rel="shortcut icon" href="../img/icon.png">

    <title>PiGallery installer wizard</title>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <link href="../css/override/pigallery.css" rel="stylesheet">
    <link href="css/setup.css" rel="stylesheet">
  </head>
<body>

<div id="gallerySite" >
    <!-- Fixed navbar -->
    <div class="navbar navbar-default navbar-fixed-top navbar-inverse" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="../index.php"><img src="../img/icon_inv.png" style="max-height: 26px; display: inline;"/><?php echo $LANG['site_name']; ?></a>
                <img class="pull-left pull-right" id="loading-sign" src="../img/loading.gif"/>

            </div>

            <div id="linkCountDown"></div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li id="galleryButton" class="active"><a href="#">Installer wizard</a></li>

                </ul>

            </div><!--/.nav-collapse -->
        </div>
    </div>


    <div id="alertsDiv"></div>

    <?php if(isGD() === false){ ?>
        <div class="container">
    <div class="row">
        <div class="col-sm-push-2 col-sm-8 alert alert-danger alert-dismissible" role="alert">
            <strong>Error!</strong> Can't find php-gd! This site is using php gd for generating thumbnails! Install it and enable it in php.ini!
        </div>
    </div>
        </div>
    <?php } ?>



    <form class="form-horizontal setup-panel" id="installerChooser">
        <fieldset>
            <!-- Form Name -->
            <legend>Choose installer mode (1/6)</legend>
            <!-- Button -->
            <div class="form-group">
                <div class="col-sm-push-4 col-sm-4">
                    <button id="typicalMode" class="col-sm-12 btn btn-success btn-lg" type="button">Typical install <span class="glyphicon glyphicon-chevron-right"></button>
                    <span class="help-block">Choose this, if you want an easy install with the typical settings</span>
                </div>
            </div>

            <!-- Button -->
            <div class="form-group">
                <div class="col-sm-push-4 col-sm-4">
                    <button id="customMode" class="col-sm-12 btn btn-primary btn-lg"  type="button">Custom install <span class="glyphicon glyphicon-chevron-right"></button>
                    <span class="help-block">Choose this, if you want to see all the available settings</span>
                </div>
            </div>

        </fieldset>
        </form>

     <form class="form-horizontal setup-panel" id="basicSettings" style="display: none">
        <fieldset>

            <!-- Form Name -->
            <legend>Basic Settings (2/6)</legend>

            <!-- Language settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="lang">Language</label>
                <div class="col-sm-4">
                    <select id="lang" name="lang" class="form-control">
                        <option value="eng" <?php if(Properties::$language == "eng") echo 'selected="selected"'; ?> >English</option>
                        <option value="hun" <?php if(Properties::$language == "hun") echo 'selected="selected"'; ?> >Hungarian - Magyar</option>
                    </select>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Language settings help"
                        data-content="The language of the site, pick one from the lang directory.<br/> If your language not exist, go to the lang folder and translate the eng.php :)">
                    <span class="glyphicon glyphicon-question-sign"></button>

            </div>

            <!-- Site url settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Site Url</label>
                <div class="col-sm-4">
                    <input id="siteUrl" name="siteUrl" placeholder="http://wwww.yoursite.com" class="form-control input-md" type="text" required="required"  value="<?php echo Properties::$siteUrl; ?>">
                    <span class="help-block">It seems that you should use this: "<?php echo home_base_url();?>"</span>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Site Url settings help"
                        data-content="The site url. This path leads to the index.php<br/>
                                            Example:<br/>
                                                1) https://www.mysite.com/gallery/<br/>
                                                2) http://localhost/">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>


            <!-- Doc root settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Document Root</label>
                <div class="col-sm-4">
                    <input id="documentRoot" name="documentRoot" placeholder="" class="form-control input-md" type="text" value="<?php echo Properties::$documentRoot; ?>">
                    <span class="help-block">It seems that you should use this: "<?php echo documentRoot();?>"</span>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Document Root settings help"
                        data-content="The base directory relative to the document root (the www folder) <br/>
                                        Eg.: If the site folder is /var/www/PiGallery than set it to 'PiGallery. If it is in the www folder, leave it empty">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>


            <!-- Image folder settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Image Folder</label>
                <div class="col-sm-4">
                    <input id="imageFolder" name="imageFolder" placeholder="./path_to_images" class="form-control input-md" type="text"  value="<?php echo Properties::$imageFolder; ?>">
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Image Folder settings help"
                        data-content="The image directory relative to the document root <br/>
                                       Eg.: If the images folder is /var/www/PiGallery/images than set it to './images'<br/>
                                       Important:<br/>
                                        - it has to be relative to the site root. If your folder is somewhere else, create a link to the images folder in your site root directory<br/>
                                        - web server need the right to read this folder">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>


            <!-- Thumbnail settings settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Thumbnail Folder</label>
                <div class="col-sm-4">
                    <input id="thumbnailFolder" name="thumbnailFolder" placeholder="./path_to_thumbnails" class="form-control input-md" type="text"   value="<?php echo Properties::$thumbnailFolder; ?>">
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Thumbnail Folder settings help"
                        data-content="The thumbnail folder relative to the document root. <br/>
                                        Eg.: If the thumbnail folder is /var/www/PiGallery/thumbnails than set is './thumbnails'<br/>
                                        Important: the folder must be readable and writable by the web server!<br/>
                                        Note: it has to be relative to the site root.<br/>
                                              If your folder is somewhere else, create a link to the thumbnail folder in your site root directory">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <!-- Enable UTF8 Encode settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Enable UTF8 text Conversion</label>
                <div class="col-sm-4">
                    <input id="enableUTF8Encode" name="enableUTF8Encode"  data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$enableUTF8Encode) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-warning" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Enable UTF8 Encode settings help"
                        data-content="Toggle it, if the directory names don't appear properly.">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>


            <div class="form-group" data-advanced-setup="true">
                <label class="col-sm-4 control-label" for="textinput">Automatic guest login at local network</label>
                <div class="col-sm-4">
                    <input id="GuestLoginAtLocalNetworkEnabled" name="GuestLoginAtLocalNetworkEnabled" data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$GuestLoginAtLocalNetworkEnabled) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="On the fly indexing settings help"
                        data-content="Enabling Guest Login: On a local network no password will be asked, and user will be logged in as GUEST<br/>
                            Only use it if the server is not put directly to the internet (Means: behind router/NAT)<br/>
                            Note: if you are using self signed certification for https, this feature might not work in every browser">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <div class="form-group">
                <div class="col-sm-push-1 col-sm-2">
                    <button id="backToInstallerChooser"  class="col-sm-12 btn btn-primary"  type="button"><span class="glyphicon glyphicon-chevron-left"> Back</button>
                </div>
                <div class="col-sm-push-6 col-sm-2">
                    <button id="validateBasicSettings"  class="col-sm-12 btn btn-success"  type="button">Validate & Next<span class="glyphicon glyphicon-chevron-right"></button>
                </div>
            </div>

        </fieldset>
        </form>

    <form class="form-horizontal  setup-panel" id="thumbnailSettings" style="display: none">
        <fieldset>


            <!-- Form Name -->
            <legend>Thumbnail Settings (3/6)</legend>

            <!-- Thumbnail Size settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Thumbnail Sizes</label>
                <div class="col-sm-4">
                    <input id="thumbnailSizes" name="thumbnailSizes"  class="form-control input-md" type="text" value="<?php echo  implode(', ',Properties::$thumbnailSizes); ?>">
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Thumbnail Size settings help"
                        data-content="The thumbnail sizes that the site generates automatically. <br/>
                                      Separate the values with coma! <br/>
                                      Note: Thumbnail generation is a long process, give 1 or 2 sizes only<br/>
                                      If you set 100 than thumbnails will have 100*100 pixels">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <!-- Thumbnail JPEG Quality settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Thumbnail JPEG Quality</label>
                <div class="col-sm-4">
                    <input id="thumbnailJPEGQuality" name="thumbnailJPEGQuality"  data-slider-id='thumbnailJPEGQualitySlider' style="width: 100%;"  type="text" data-slider-min="0" data-slider-max="100" data-slider-step="1" data-slider-value="<?php echo Properties::$thumbnailJPEGQuality; ?>"/>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Thumbnail JPEG Quality settings help"
                        data-content="The JPEG quality of the thumbnail">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>


            <!-- Thumbnail Image Resampling settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Thumbnail Image Resampling</label>
                <div class="col-sm-4">
                    <input id="EnableThumbnailResample" name="EnableThumbnailResample"  data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$EnableThumbnailResample) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Thumbnail Image Resampling settings help"
                        data-content="Set true for resampling or false for resizing only. (true: nice thumbnails, false: better performance)">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <!-- Thumbnail Image Resampling settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Thumbnail Image Caching</label>
                <div class="col-sm-4">
                    <input id="enableImageCaching" name="enableImageCaching"  data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$enableImageCaching) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Thumbnail Image Caching settings help"
                        data-content="Enables thumbnail and image caching for the browser, true is recommended.">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <div class="form-group">
                <div class="col-sm-push-1 col-sm-2">
                    <button id="backToBasicSettings"  class="col-sm-12 btn btn-primary"  type="button"><span class="glyphicon glyphicon-chevron-left"> Back</button>
                </div>
                <div class="col-sm-push-6 col-sm-2">
                    <button id="nextFromThumbnailSettings"  class="col-sm-12 btn btn-success"  type="button">Next<span class="glyphicon glyphicon-chevron-right"></button>
                </div>
            </div>

        </fieldset>
    </form>

    <form class="form-horizontal setup-panel" id="modeChooser" style="display: none">
        <fieldset>

            <!-- Form Name -->
            <legend>Choose database mode (4/6)</legend>

            <!-- Button -->
            <div class="form-group">
                <div class="col-sm-push-4 col-sm-4">
                    <button id="databaseMode"  class="col-sm-12 btn btn-success btn-lg">Database mode <span class="glyphicon glyphicon-chevron-right"></button>
                    <span class="help-block">In this mode, the images folder will be indexed to the database. Much faster than the non-DB mode and more features are supported (Searching with autocomplete, sharing) It is the recommended mode.</span>
                </div>
            </div>

            <!-- Button -->
            <div class="form-group">
                <div class="col-sm-push-4 col-sm-4">
                    <button id="nonDatabaseMode" class="col-sm-12 btn btn-primary btn-lg">Database free mode <span class="glyphicon glyphicon-chevron-right"></button>
                    <span class="help-block">In this mode, the site will read the hard disk at every navigation. It is a simple (no database installation needed), but a slower mode and some feature are not supported (Searching with autocomplete, sharing).</span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-push-1 col-sm-2">
                    <button id="backFromModeChooser"  class="col-sm-12 btn btn-primary"  type="button"><span class="glyphicon glyphicon-chevron-left"> Back</button>
                </div>
            </div>

        </fieldset>
    </form>

    <form class="form-horizontal setup-panel" id="databaseSettings" style="display: none">
        <fieldset>

            <!-- Form Name -->
            <legend>Database setup (5/6)</legend>

            <!-- Site url settings-->
            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Database address</label>
                <div class="col-sm-4">
                    <input id="databaseAddress" name="databaseAddress" placeholder="localhost" class="form-control input-md" type="text" value="<?php echo Properties::$databaseAddress; ?>">
                </div>

            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Database user name</label>
                <div class="col-sm-4">
                    <input id="databaseUserName" name="databaseUserName" placeholder="root" class="form-control input-md" type="text" value="<?php echo Properties::$databaseUserName; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Database password</label>
                <div class="col-sm-4">
                    <input id="databasePassword" name="databasePassword"   class="form-control input-md" type="password" value="<?php echo Properties::$databasePassword; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="textinput">Database name</label>
                <div class="col-sm-4">
                    <input id="databaseName" name="databaseName" placeholder="pigallery" class="form-control input-md" type="text" value="<?php echo Properties::$databaseName; ?>">
                </div>
            </div>


            <!-- On the fly indexing settings-->
            <div class="form-group"  data-advanced-setup="true">
                <label class="col-sm-4 control-label" for="textinput">On the fly indexing</label>
                <div class="col-sm-4">
                    <input id="enableOnTheFlyIndexing" name="enableOnTheFlyIndexing" data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$enableOnTheFlyIndexing) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="On the fly indexing settings help"
                        data-content="If its true, the site will check at every directory open if indexing is needed or not. if need, the site will index the given folder automatically.<br/>
                                        Note: if its disable, manually indexing is required every time a folder or an image was added to your image directory.">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>



            <div class="form-group"  data-advanced-setup="true">
                <label class="col-sm-4 control-label" for="textinput">Max Search result items</label>
                <div class="col-sm-4">
                    <input id="maxSearchResultItems" name="maxSearchResultItems" placeholder="500" class="form-control input-md" type="number" value="<?php echo Properties::$maxSearchResultItems; ?>">
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Max Search result items settings help"
                        data-content="The max number of results, that a search can give. ">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <!-- Searching settings-->
            <div class="form-group"  data-advanced-setup="true">
                <label class="col-sm-4 control-label" for="textinput">Searching</label>
                <div class="col-sm-4">
                    <input id="enableSearching" name="enableSearching" data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$enableSearching) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Search settings help"
                        data-content="If its true, a search box with autocomplete will appear in the menu bar (only works in db-mode)">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>

            <!-- Searching settings-->
            <div class="form-group"  data-advanced-setup="true">
                <label class="col-sm-4 control-label" for="textinput">Sharing</label>
                <div class="col-sm-4">
                    <input id="enableSharing" name="enableSharing" data-width="100%" data-toggle="toggle" data-on="Enabled" data-off="Disabled" type="checkbox"  <?php if(Properties::$enableSharing) echo 'checked="checked"'; ?>>
                </div>
                <button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="auto right" title="Share settings help"
                        data-content="If its true, a sharing button will appear in the menu bar (only works in db-mode)">
                    <span class="glyphicon glyphicon-question-sign"></button>
            </div>


            <div class="form-group">
                <div class="col-sm-push-1 col-sm-2">
                    <button id="backFromDatabaseSettings"  class="col-sm-12 btn btn-primary"  type="button"><span class="glyphicon glyphicon-chevron-left"> Back</button>
                </div>
                <div class="col-sm-push-6 col-sm-2">
                    <button id="validateDatabaseSettings"  class="col-sm-12 btn btn-success"  type="button">Validate & Next<span class="glyphicon glyphicon-chevron-right"></button>
                </div>
            </div>

        </fieldset>
    </form>

    <form class="form-horizontal setup-panel" id="addUsers" style="display: none">
        <fieldset>
            <!-- Form Name -->
            <legend>Add Users (6/6)</legend>


            <div class="form-group">
                <input id="adminUserID" name="adminUserID"  type="hidden">
                <div class="col-sm-push-3 col-sm-2">
                    <input id="adminUserName" placeholder="username" class="form-control input-md" type="text">
                </div>
                <div class="col-sm-push-3 col-sm-2">
                    <input id="adminPassword"  placeholder="password" class="form-control input-md" type="password">
                </div>
                <div class="col-sm-push-3 col-sm-2">
                    <select class="form-control" name="lang" disabled="disabled">
                        <option value="<?php echo \piGallery\db\entities\Role::User; ?>">User</option>
                        <option selected value="<?php echo \piGallery\db\entities\Role::Admin; ?>">Admin</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="display: none" id="userInfoPrototype">
                <input data-user="id" name="adminUserID"  type="hidden">
                <div class="col-sm-push-3 col-sm-2">
                    <input data-user="name" name="databaseName" placeholder="username" class="form-control input-md" type="text">
                </div>
                <div class="col-sm-push-3 col-sm-2">
                    <input data-user="password"  name="databaseName" placeholder="password" class="form-control input-md" type="password">
                </div>
                <div class="col-sm-push-3 col-sm-2">
                    <select data-user="role"  name="lang" class="form-control">
                        <option value="<?php echo \piGallery\db\entities\Role::User; ?>">User</option>
                        <option value="<?php echo \piGallery\db\entities\Role::Admin; ?>">Admin</option>
                    </select>
                </div>
                <div class="col-sm-push-3 col-sm-1">
                    <button  data-user="delete"   class="col-sm-12 btn btn-danger userDeleteButton"  type="button"> <span class="glyphicon glyphicon-trash"></span> </button>
                </div>
            </div>

            <!-- Button -->
            <div class="form-group" id="addNewUser-group">
                <div class="col-sm-push-5 col-sm-2">
                    <button id="addNewUser" name="addNewUser" class="col-sm-12 btn btn-danger" type="button"><span class="glyphicon glyphicon-plus-sign" ></span> Add User </button>
               </div>
            </div>


            <div class="form-group">
                <div class="col-sm-push-1 col-sm-2">
                    <button id="backFromAddUser"  class="col-sm-12 btn btn-primary"  type="button"><span class="glyphicon glyphicon-chevron-left"> Back</button>
                </div>
                <div class="col-sm-push-6 col-sm-2">
                    <button id="save"  class="col-sm-12 btn btn-success"  type="button">Save and exit</button>
                </div>
            </div>

        </fieldset>



    </form>




</div>


<script src="../js/lib/require.min.js" data-main="js/main.js"></script>
</body>
</html>
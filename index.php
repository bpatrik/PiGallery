<?php
require_once __DIR__."/config.php";
require_once __DIR__."/model/AuthenticationManager.php";
use piGallery\db\entities\Role;
use \piGallery\Properties;

require_once __DIR__."/lang/".Properties::$language.".php";



header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG['html_language_code']; ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="img/icon.png">

    <title><?php echo $LANG['site_name']; ?></title>


    <?php
        /*Preload directory content*/
        require_once __DIR__."/db/DB.php";
        require_once __DIR__."/db/DB_UserManager.php";
        require_once __DIR__."/db/DB_ContentManager.php";
        require_once __DIR__."/model/Helper.php";
        require_once __DIR__."/model/DirectoryScanner.php";
        require_once __DIR__."/model/NoDBUserManager.php";
        require_once __DIR__."/config.php";

        use \piGallery\model\Helper;
        use \piGallery\db\DB;
        use \piGallery\db\DB_ContentManager;

        /*Check if Table exist*/
        if(Properties::$databaseEnabled) {
            if (DB::isTablesExist() == false) {
                echo "db created";
                DB::recreateDatabase();
            }
        }


        $dir = Helper::get_REQUEST('dir','/');
        if (Properties::$enableUTF8Encode) {
            $dir = utf8_decode($dir);
        }
        $dir = Helper::toDirectoryPath($dir);
        $content = null;

        $user = \piGallery\model\AuthenticationManager::authenticate(Role::RemoteGuest);
      
        /*is logged in*/
        if($user != null){
            
            if($user->getRole() <= Role::RemoteGuest){ //check if it has right for watching the dir
                
                if($user->getPathRestriction()->isRecursive() == false){
                    $dir = $user->getPathRestriction()->getPath();                    
                }else{
                    if(Helper::isSubPath($dir, $user->getPathRestriction()->getPath()) === FALSE){
                        $dir = $user->getPathRestriction()->getPath();
                    }
                }
                
                $user->getPathRestriction()->setPath(Helper::toURLPath($user->getPathRestriction()->getPath()));
            }
            
            $user->setPassword(null);
            try{
                if(Properties::$databaseEnabled){

                    $content = \piGallery\db\DB_ContentManager::getDirectoryContent($dir);
                    if($content['indexingNeeded'] == true && Properties::$enableOnTheFlyIndexing){
                        DB::indexDirectory($dir);
                        $content = DB_ContentManager::getDirectoryContent($dir);
                    }

                }else{
                    $content = \piGallery\model\DirectoryScanner::getDirectoryContent($dir);
                }
                if($content != null && $user->getPathRestriction() != null && $user->getPathRestriction()->isRecursive() === FALSE){
                    $content['directories'] = array();
                }
            }catch (Exception $ex){
                $dir = "/";
            }
        }



    ?>

    <script language="JavaScript">
        var PiGallery = PiGallery || {};

        //Preloaded directory content
        PiGallery.currentPath = "<?php echo Helper::toURLPath($dir); ?>";
        PiGallery.preLoadedDirectoryContent= <?php echo ($content == null ?  "null" : Helper::contentArrayToJSON($content)); ?>;
        PiGallery.Supported = {
            DataBaseSettings : <?php echo Properties::$databaseEnabled == false ? "false" : "true"; ?>,
            Search : <?php echo Properties::$databaseEnabled == false ? "false" : "true"; ?>,
            Share : <?php echo Properties::$databaseEnabled == false ? "false" : "true"; ?>
        };
        PiGallery.documentRoot = "<?php echo Properties::$documentRoot; ?>";
        PiGallery.guestAtLocalNetworkEnabled = <?php  echo Properties::$GuestLoginAtLocalNetworkEnabled ? "true" : "false"; ?>;
        PiGallery.localServerUrl = "<?php if(Properties::$GuestLoginAtLocalNetworkEnabled) echo $_SERVER['SERVER_ADDR']; ?>";
        PiGallery.user =  <?php echo json_encode(is_null($user) ? null : $user->getJsonData()); ?>;
        PiGallery.LANG = <?php echo json_encode($LANG); ?>;
        PiGallery.shareLink = <?php $s = Helper::get_REQUEST("s",null);  echo $s != null ? '"'.$s.'"' : "null"; ?>;

    </script>



    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

<?php /*
    <!--jquerry ui-->
    <link rel="stylesheet" href="./css/ui-bootstrap/jquery-ui-1.10.3.custom.css">
    <!-- Bootstrap core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/signin.css" rel="stylesheet">
    
    <!-- bootstrap image gallery-->
    <link rel="stylesheet" href="css/blueimp-gallery.min.css">
    <!-- <link rel="stylesheet" href="http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css">-->
    <link rel="stylesheet" href="css/bootstrap-image-gallery.min.css">
    <link rel="stylesheet" href="css/bootstrap-slider.min.css">

    <!-- Own css-->

    <link href="./css/override/boostrap-override.css" rel="stylesheet">
    <link href="./css/override/gallery.css" rel="stylesheet">
*/?>

    <link href="./css/override/pigallery.css" rel="stylesheet">
</head>

<body>
<div id="signInSite" style="display: none;">
    <div class="container">
        <h1 class="signin-title"><img src="img/icon.png" /><?php echo $LANG['site_name']; ?></h1>

        <form class="form-signin" role="form" id="signinForm">
            <h2 class="form-signin-heading"><?php echo $LANG['PleaseSignIn']; ?></h2>
            <input id="userNameBox" type="text" class="form-control" placeholder="<?php echo $LANG['username']; ?>" required autofocus>
            <input id="passwordBox" type="password" class="form-control" placeholder="<?php echo $LANG['password']; ?>" required>
            <label class="checkbox">
                <input id="rememberMeBox" type="checkbox" value="remember-me"> <?php echo $LANG['rememberme']; ?>
            </label>
            <button id="loginButton" class="btn btn-lg btn-primary btn-block loginButton" type="submit"><?php echo $LANG['signin']; ?></button>
        </form>

    </div> <!-- /container -->
</div>


<div id="gallerySite" style="display: none;">
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
                <a class="navbar-brand" href="index.php<?php $s = Helper::get_REQUEST("s",null);  echo $s != null ? "?s=".$s : "";?>"><img src="img/icon_inv.png" style="max-height: 26px; display: inline;"/><?php echo $LANG['site_name']; ?></a>
                <img class="pull-left pull-right" id="loading-sign" src="img/loading.gif"/>

            </div>

            <div id="linkCountDown"></div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li id="galleryButton" class="active"><a href="#"><?php echo $LANG['gallery']; ?></a></li>

                    <li id="adminButton" 
                            <?php if(!\piGallery\Properties::$databaseEnabled || 
                                    $user == null || 
                                    $user->getRole() < Role::Admin) { ?> style=" display: none;"  <?php } ?>
                        >
                        <a href="#">Settings</a>
                    </li>
                    <!--<li><a href="#">Monitor</a></li> -->
                </ul>
                <ul id="menu" class="nav navbar-nav navbar-right">
                    <li><p class="navbar-text" id="userNameButton"><?php echo is_null($user) ? "" : $user->getUserName(); ?></p></li>
                    <li><a href="#" id="logOutButton" ><?php echo $LANG['logout']; ?></a></li>
                    <li><a href="#" id="signinButton" data-toggle="modal" data-target="#loginModal"><?php echo $LANG['signin']; ?></a></li>
                </ul>
                <?php if(Properties::$databaseEnabled == true) { ?>
                <form id="autocompleteForm" class="navbar-form navbar-right" role="search" >
                    <div class="form-group">
                        <input type="text" id="auto-complete-box"  class="form-control" placeholder="Search">
                    </div>
                    <button type="submit" id="search-button" class="btn btn-default"><?php echo $LANG['search']; ?></button>
                </form>
                <?php } ?>
            </div><!--/.nav-collapse -->
        </div>
    </div>


    <!-- Error, Warning and Info dialogs-->
    <div id="alertsDiv"></div>



    <!-- The Bootstrap Image Gallery lightbox, should be a child element of the document body -->
    <div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" >
        <!-- The container for the modal slides -->
        <div class="slides"></div>
        <!-- Controls for the borderless lightbox -->
        <h3 class="title"></h3>
        <a class="prev">‹</a>
        <a class="next">›</a>
        <a class="close">×</a>
        <a class="full-screen"><span class="glyphicon glyphicon-fullscreen"></span> </a>
        <a class="play-pause"></a>
        <ol class="indicator"></ol>
        <!-- The modal dialog, which will be used to wrap the lightbox content -->
        <div class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"></h4>
                    </div>
                    <div class="modal-body next"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left prev">
                            <i class="glyphicon glyphicon-chevron-left"></i>
                            Previous
                        </button>
                        <button type="button" class="btn btn-primary next">
                            Next
                            <i class="glyphicon glyphicon-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="gallery-container" class="container">
        <ol id="directory-path" class="breadcrumb">
        </ol>
        <div id="gallery">
            <div id="directory-gallery"  data-bind="template: { name: 'directoryList'}"  ></div>
            <hr/>
            <div id="photo-gallery"  data-bind="template: { name: 'photoList' }"  > </div>
        </div>
    </div> <!-- /container -->

    <div id="admin-container" class="container" style="display: none;">

        <div  id="adminUsers" class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo $LANG['admin_users']; ?></h3>
            </div>
            <div class="panel-body">
                <h4><?php echo $LANG['admin_users']; ?>:</h4>
                <table class="table table-condensed">
                <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo $LANG['admin_userName']; ?></th>
                    <th><?php echo $LANG['admin_role']; ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="adminUsersList">
                </tbody>
                </table>
                <hr/>
                    <form id="adminRegisterForm" class="form-inline" role="form">
                            <h4><?php echo $LANG['admin_addNewUser']; ?>:</h4>


                            <div class="form-group">
                                <label class="sr-only" for="adminRegisterUserName"><?php echo $LANG['admin_userName']; ?></label>
                                <input type="text" required="required" class="form-control" id="adminRegisterUserName" placeholder="<?php echo $LANG['admin_userName']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="sr-only" for="adminRegisterPassword"><?php echo $LANG['admin_password']; ?></label>
                                <input type="password" required="required" class="form-control" id="adminRegisterPassword" placeholder="<?php echo $LANG['admin_password']; ?>">
                            </div>

                            <div class="form-group">
                                <label class="sr-only" for="exampleInputPassword2"><?php echo $LANG['admin_role']; ?></label>
                                <select id="adminRegisterRole" name="role" class="form-control">
                                    <option value="2"><?php echo $LANG['admin_role_user']; ?></option>
                                    <option value="3"><?php echo $LANG['admin_role_admin']; ?></option>
                                </select>
                            </div>

                            <button id="adminAddUserButton" type="submit" class="btn btn-default btn-primary"><?php echo $LANG['admin_add']; ?></button>
                    </form>


            </div>
        </div>

        <div  id="adminPhotos" class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo $LANG['admin_photos']; ?></h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <button id="clearTableButton" type="button" class="btn btn-default btn-danger"><?php echo $LANG['admin_clearIndex']; ?></button>
                        <button id="indexPhotosButton" type="button" class="btn btn-default btn-success"><?php echo $LANG['admin_indexPhotos']; ?></button>
                    </div>
                    <div class="col-md-8">
                        <div id="indexingProgress" class="well well-sm">...</div>
                    </div>
                </div>
            </div>
        </div>


        <div  id="adminPhotos" class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo $LANG['admin_database']; ?></h3>
            </div>
            <div class="panel-body">
                <button id="resetDatabaseButton" type="button" class="btn btn-default btn-danger"><?php echo $LANG['admin_resetDatabase']; ?></button>
            </div>
        </div>
    </div> <!-- /container -->
</div>

<!-- login Modal for Guest users -->
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="loginModalLabel"><?php echo $LANG['site_name']; ?></h4>
            </div>
            <div class="modal-body">
                    <form class="form-signin" role="form" id="modalSigninForm">
                        <input id="modalUserNameBox" type="text" class="form-control" placeholder="<?php echo $LANG['username']; ?>" required autofocus>
                        <input id="modalPasswordBox" type="password" class="form-control" placeholder="<?php echo $LANG['password']; ?>" required>
                        <label class="checkbox">
                            <input id="modalRememberMeBox" type="checkbox" value="remember-me"> <?php echo $LANG['rememberme']; ?>
                        </label>
                    <button id="modalLoginButton" class="btn btn-lg btn-primary btn-block loginButton" type="submit"><?php echo $LANG['signin']; ?></button>
                    </form>
            </div>
        </div>
    </div>
</div>

<?php if(\piGallery\Properties::$databaseEnabled) { ?>
<!-- sharing Modal-->
<div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="shareModalLabel"><?php echo $LANG['share']; ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-10">
                        <input id="shareLink" name="shareLink" placeholder="link" class="form-control input-md" type="text" >
                    </div>
                    <div class="col-sm-2 pull-right">
                        <button id="copyButton" name="copyButton" data-clipboard-target="shareLink" class="btn btn-primary"><?php echo $LANG['copy']; ?></button>
                    </div>
                </div>
                <hr/>
                <div class="form-horizontal">
                    <div class="form-group" style="padding: 0 15px 0 15px;">

                        <div  style="display: inline;">
                            <label class="control-label"><?php echo $LANG['sharing']; ?>:</label>
                            <div class="form-control-static" id="sharingPath">/</div>
                        </div>

                        <label class="checkbox pull-right">
                            <input id="recursiveShareBox" type="checkbox" checked="true" value="remember-me"> <?php echo $LANG['recursive']; ?>
                        </label>


                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <?php echo $LANG['validFor']; ?>:
                        <p id="sliderText"></p>
                    </div>
                    <div class="col-sm-8">
                        <input  id="shareSlider" data-slider-id='shareSlider' type="text" data-slider-min="1" data-slider-max="108" data-slider-step="1" data-slider-value="53"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 col-sm-push-10">
                        <button id="updatebutton" name="updatebutton" class="btn btn-primary"><?php echo $LANG['update']; ?></button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
<?php } ?>

<script src="js/lib/require.min.js" data-main="js/main.js"></script>


</body>
</html>

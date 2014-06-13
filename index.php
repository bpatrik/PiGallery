<?php
require_once __DIR__."/config.php";
use \piGallery\Properties;
require_once __DIR__."/lang/".Properties::$language.".php";
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG['html_language_code']; ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../../assets/ico/favicon.ico">

    <title><?php echo $LANG['site_name']; ?></title>

    <!--jquerry ui-->
    <link rel="stylesheet" href="./css/ui-bootstrap/jquery-ui-1.10.3.custom.css">
    <!-- Bootstrap core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/signin.css" rel="stylesheet">


    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->


    <!-- bootstrap image gellery-->
    <link rel="stylesheet" href="css/blueimp-gallery.min.css">
   <!-- <link rel="stylesheet" href="http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css">-->
    <link rel="stylesheet" href="css/bootstrap-image-gallery.min.css">

    <!-- Own css-->

    <link href="./css/override/boostrap-override.css" rel="stylesheet">
    <link href="./css/override/galery.css" rel="stylesheet">

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
        use \piGallery\model\NoDBUserManager;

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

        $user = null;
        $jsonUser = json_encode(null);
        if(isset($_COOKIE["pigallery-sessionid"]) && !empty($_COOKIE["pigallery-sessionid"])){

            if(Properties::$databaseEnabled) {

                $user = \piGallery\db\DB_UserManager::loginWithSessionID($_COOKIE["pigallery-sessionid"]);
                if ($user != null) {
                    $user->setPassword(null);
                }
            }else{
                $user = NoDBUserManager::loginWithSessionID($_COOKIE["pigallery-sessionid"]);
                if ($user != null) {
                    $user->setPassword(null);
                }
            }
        }
        /*is logged in*/
        if($user != null){
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

            }catch (Exception $ex){
                $dir = "/";
            }

         $jsonUser = json_encode($user->getJsonData());
        }



    ?>

    <script language="JavaScript">
        var PiGallery = PiGallery || {};

        //Preloaded directory content
        PiGallery.currentPath = "<?php echo Helper::toURLPath($dir); ?>";
        PiGallery.preLoadedDirectoryContent= <?php echo ($content == null ?  "null" : Helper::contentArrayToJSON($content)); ?>;
        PiGallery.searchSupported = <?php echo Properties::$databaseEnabled == false ? "false" : "true"; ?>;
        PiGallery.documentRoot = "<?php echo Properties::$documentRoot; ?>";
        PiGallery.user =  <?php echo $jsonUser; ?>;
        PiGallery.LANG = <?php echo json_encode($LANG); ?>;

    </script>


</head>

<body>
<div id="signInSite" style="display: none;">
    <div class="container">
        <h1 class="signin-title"><?php echo $LANG['site_name']; ?></h1>

        <form class="form-signin" role="form" id="signinForm">
            <h2 class="form-signin-heading"><?php echo $LANG['PleaseSignIn']; ?></h2>
            <input id="userNameBox" type="text" class="form-control" placeholder="<?php echo $LANG['username']; ?>" required autofocus>
            <input id="passwordBox" type="password" class="form-control" placeholder="<?php echo $LANG['password']; ?>" required>
            <label class="checkbox">
                <input id="rememberMeBox" type="checkbox" value="remember-me"> <?php echo $LANG['rememberme']; ?>
            </label>
            <button id="loginButton" class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $LANG['signin']; ?></button>
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
                <a class="navbar-brand" href="index.php"><?php echo $LANG['site_name']; ?></a>
                <img id="loading-sign" src="img/loading.gif"/>
            </div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li id="galleryButton" class="active"><a href="#"><?php echo $LANG['gallery']; ?></a></li>
                    <?php if(\piGallery\Properties::$databaseEnabled) { ?>
                    <li id="adminButton"><a href="#">Admin</a></li>
                    <?php } ?>
                        <!--  <li><a href="#">Admin</a></li>
                    <li><a href="#">Monitor</a></li> -->
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <!-- Not supported yet
                    <?php if(\piGallery\Properties::$databaseEnabled) { ?>
                         <li><a href="#"><span class="glyphicon glyphicon-share-alt"> <?php echo $LANG['share']; ?></span></a></li>
                    <?php } ?>
                    -->
                    <li><a href="#" id="userNameButton">User</a></li>
                    <li><a href="#" id="logOutButton"><?php echo $LANG['logout']; ?></a></li>
                </ul>
                <?php if(\piGallery\Properties::$databaseEnabled) { ?>
                <form id="autocompleteForm" class="navbar-form navbar-right" role="search">
                    <div class="form-group">
                        <input type="text" id="auto-complete-box"  class="form-control" placeholder="Search">
                    </div>
                    <button type="submit" id="search-button" class="btn btn-default"><?php echo $LANG['search']; ?></button>
                </form>
                <?php } ?>
            </div><!--/.nav-collapse -->
        </div>
    </div>




    <!-- Error dialog-->
    <div id="alerts"></div>



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
                                    <option value="0"><?php echo $LANG['admin_role_user']; ?></option>
                                    <option value="1"><?php echo $LANG['admin_role_admin']; ?></option>
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

<script src="js/lib/require.min.js" data-main="js/main.js"></script>


</body>
</html>

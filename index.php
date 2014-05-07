<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../../assets/ico/favicon.ico">

    <title>PiGallery</title>

    <!--jquerry ui-->
    <link rel="stylesheet" href="./css/ui-bootstrap/jquery-ui-1.10.3.custom.css">
    <!-- Bootstrap core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">


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
        require_once __DIR__ ."./db/DB.php";
        require_once __DIR__ ."./model/Helper.php";

        use \piGallery\model\Helper;

        $dir =  Helper::get_REQUEST('dir','/');

        $content = \piGallery\db\DB::getDirectoryContent($dir);
    ?>

    <script language="JavaScript">
        var PiGallery = PiGallery || {};

        //Preloaded directory content
        PiGallery.preLoadedDirectoryContent= <?php echo Helper::contentArrayToJSON($content); ?>;

    </script>


</head>

<body>



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
            <a class="navbar-brand" href="index.php">PiGalery</a>
        </div>
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a href="#">Gallery</a></li>
                <li><a href="#">Admin</a></li>
                <li><a href="#">Monitor</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="../navbar/"><span class="glyphicon glyphicon-share-alt"> Share</span></a></li>
                <li><a href="../navbar/">bpatrik</a></li>
                <li><a href="./">Log out</a></li>
            </ul>
            <form class="navbar-form navbar-right" role="search">
                <div class="form-group">
                    <input type="text" id="auto-complete-box"  class="form-control" placeholder="Search">
                </div>
                <button type="submit" id="search-button" class="btn btn-default">Submit</button>
            </form>
        </div><!--/.nav-collapse -->
    </div>
</div>


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

<div class="container gallery-container">
    <ol id="directory-path" class="breadcrumb">
    </ol>
    <div id="gallery">

        <div id="directory-gallery" data-bind="foreach: directories">
            <div class="gallery-directory-wrapper" data-bind="style: { height: renderSize + 'px', width: renderSize   + 'px'  }" >
                <div class="gallery-directory-image" data-bind="style: { height: renderSize + 'px', width: renderSize   + 'px'  } , event: {mousemove : mousemoveHandler}" >
                    <a data-bind="attr: { href: '?dir='+ path + directoryName + '/', title: samplePhoto().fileName , 'data-path': path+directoryName+'/'}, event: {click: clickHandler}  " >
                        <img data-bind="attr: { src: samplePhoto().path + samplePhoto().fileName }, style: {width: width, height: height}">
                        <!-- <img data-bind="attr: { src: $root.getThumbnailUrl($element, samplePhoto(), height, width) }, style: {width: width, height: height}">-->
                    </a>
                </div>
                <div class="gallery-directory-description">
                    <span class="pull-left" data-bind="text: directoryName "> </span>
                    <br/>
                    <span class="pull-right" data-bind="text: 'Photos : ' + photoCount "> </span>
                </div>
            </div>
        </div>

        <hr/>

        <div id="photo-gallery"  data-bind="foreach: photos">

            <div class="gallery-image" data-bind="style: { height: renderHeight + 'px', maxWidth: renderWidth   + 'px', display:'none' }" >
                <a data-bind="attr: { href: path + fileName,  title: fileName  }" data-galxlery="">
                    <img onload="$(this).parent().parent().fadeIn();" data-bind="attr: { src: $root.getThumbnailUrl($element, $data, renderHeight, renderWidth) }, style: { height: renderHeight  + 'px',  width: renderWidth + 'px'  }"/>
                </a>
               <div class="gallery-image-description">
                   <span class="pull-left" data-bind="text: fileName " style="display: inline; position: absolute"> </span>

                    <div class="galley-image-keywords"  data-bind="foreach: keywords">
                        <a href="#" data-bind="text: '#' + $data, event: {click: $parent.keywordClickHandler}"> </a>,
                    </div>
                </div>
            </div>
        </div>

    </div>

</div> <!-- /container -->

<script src="js/require.min.js" data-main="js/main.js"></script>
<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster - ->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/knockout/3.1.0/knockout-min.js"></script>

<script src="js/lib/jquery-ui-1.10.4.min.js"></script>
<script src="js/lib/bootstrap.min.js"></script>


<!-- own js - ->
<script src="js/pigallery/GalleryRenderer.js"></script>
<script>
    $(document).ready(function(){
         $.getScript("js/ContentManager.js", function()
         {
            var contentManager = new PiGallery.ContentManager();
            var contentRenderer = new  PiGallery.GalleryRenderer($("#directory-path"),$("#gallery"),contentManager);
            contentRenderer.showContent(PiGallery.preLoadedDirectoryContent);

            $("#search-button").click(function(event) {
                event.preventDefault();
                     contentManager.getSearchResult($("#auto-complete-box").val(),contentRenderer );
            });
         });
    });
</script>

<!-- boostrap image gallery- ->
<script src="js/lib/jquery.blueimp-gallery.min.js"></script>
<!--<script src="http://blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js"></script>- ->
<script src="js/lib/bootstrap-image-gallery.min.js"></script>

<script>
    $(document).ready(function(){

        $('#blueimp-gallery').data('useBootstrapModal', false);
        var lb = null;

        document.getElementById('photo-gallery').onclick = function (event) {
            event.preventDefault();
            event = event || window.event;
            var target = event.target || event.srcElement,
                link = target.src ? target.parentNode : target,
                options = $.extend({}, $('#blueimp-gallery').data(),
                                        {index: link, event: event}),
                links = $('#photo-gallery [data-galxlery]');
            lb =blueimp.Gallery(links, options);
        };
        $('.full-screen').click(function (event) {
            event.preventDefault();
            lb.close();
            lb=null;
            $('#blueimp-gallery').data('fullScreen', true);
            lb = blueimp.Gallery($('#gallery [data-galxlery]'), $('#blueimp-gallery').data());
            $('#blueimp-gallery').data('fullScreen', false);
        });

        $.getScript("js/AutoComplete.js", function()
        {
           PiGallery.addAutoComplete($("#auto-complete-box"));
        });

    });
</script>-->
</body>
</html>

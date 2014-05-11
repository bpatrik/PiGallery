require.config({
    baseUrl:  ' js/lib', 
    paths: {
        PiGallery: '../pigallery/',
        // the left side is the module ID,
        // the right side is the path to
        // the jQuery file, relative to baseUrl.
        // Also, the path should NOT include
        // the '.js' file extension. This example
        // is using jQuery 1.9.0 located at
        // js/lib/jquery-1.9.0.js, relative to
        // the HTML page.
       // jquery: 'jquery-2.1.0.min',
        jquery_ui: 'jquery-ui-1.10.4_min',
        underscore: 'underscorejs-1.6.0.min',
        knockout: 'knockout-3.1.0-min',
        bootstrap: 'bootstrap.min',
        bootstrap_image_gallery: 'bootstrap-image-gallery',
        blueImpGallery: 'blueimp-gallery-indicator'
    },
    shim:  {
        'blueImpGallery' : {
            deps: ['jquery', 'blueimp-gallery-fullscreen']
        },
        "bootstrap": {
            deps: ["jquery"]
        },
        'jquery_blueimp-gallery_min': {
            deps: ["jquery"]

        },
        'underscore': {
            exports: '_'
        },
        'jquery.cookie': {
            deps: ['jquery']
        }

    }

});

PiGallery.showGallery = function(){
    require(['jquery', 'blueImpGallery', 'PiGallery/ContentManager', 'PiGallery/GalleryRenderer', "PiGallery/AutoComplete" ],
        function   ($,blueimpGallery,ContentManager, GalleryRenderer, AutoComplete) {
            $('#gallerySite').show();
            $('#signInSite').hide();

            var contentManager = new ContentManager();
            var galleryRenderer = new GalleryRenderer($("#directory-path"),$("#gallery"),contentManager);

            contentManager.storeContent(PiGallery.preLoadedDirectoryContent);
            galleryRenderer.showContent(PiGallery.preLoadedDirectoryContent);

            $("#search-button").click(function(event) {
                event.preventDefault();
                contentManager.getSearchResult($("#auto-complete-box").val(),contentRenderer );
            });


            /*---------------lightbox setup--------------------*/

          //   $('#blueimp-gallery').data('useBootstrapModal', false);
            PiGallery.lightbox = null;

            $('#photo-gallery').click(function (event) {
                event = event || window.event;
                var target = event.target || event.srcElement,
                    link = target.src ? target.parentNode : target,
                    options = $.extend({}, $('#blueimp-gallery').data(),
                        {index: link, event: event}),
                    links = $('#photo-gallery [data-galxlery]');

                $('.full-screen').show();
                PiGallery.lightbox = blueimpGallery(links, options);
                return false;
            });

            $('.full-screen').click(function () {
                if(PiGallery.lightbox.options.fullScreen == false){
                    PiGallery.lightbox.options.fullScreen = true;
                    PiGallery.lightbox.requestFullScreen(PiGallery.lightbox.container[0]);
                    $('.full-screen').hide();
                }
                return false;
            });


            if(PiGallery.searchSupported){
              new AutoComplete($("#auto-complete-box"));
            }
    });
}

PiGallery.showLogin = function(){
    require(['jquery', 'jquery.cookie'],  function   ($) {
            $('#gallerySite').hide();
            $('#signInSite').show();

        $('#loginButton').click(function() {
            $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "login",
                       userName: $('#userNameBox').val(),
                       password: $('#passwordBox').val(),
                       rememberMe: $('#rememberMeBox').attr('checked')},
                dataType: "json"
            }).done(function(result) {
                if(result.error == null){
                    if($('#rememberMeBox').attr('checked')){
                        $.cookie("pigallery-sessionid", result.data.sessionID, { expires : 30 });
                    }else{
                        $.cookie("pigallery-sessionid", result.data.sessionID, { expires : 1 });
                    }
                    PiGallery.showGallery();
                }else{
                    alert(result.error);
                }
            }).fail(function(errMsg) {
                console.log("Error during downloading singing in");
            });
            return false;
        });
    });
}

if(PiGallery.user != null){
    PiGallery.showGallery();
}else{
    PiGallery.showLogin();

}
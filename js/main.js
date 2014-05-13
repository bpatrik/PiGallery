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
        jquery: ['//code.jquery.com/jquery-2.1.1.min','jquery-2.1.1.min'],
        jquery_ui: ['//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min','jquery-ui-1.10.4_min'],
        underscore: ['//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min','underscorejs-1.6.0.min'],
        bootstrap: ['//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min','bootstrap.min'],
        blueImpGallery: 'blueimp-gallery-indicator',
        jquery_cookie: ['//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min', 'jquery.cookie']
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
        'jquery_cookie': {
            deps: ['jquery']
        },
        'jquery_ui':{
            deps: ['jquery']
        }

    }

});

PiGallery.showGallery = function(){
    require(['jquery', 'blueImpGallery', 'PiGallery/ContentManager', 'PiGallery/GalleryRenderer', "PiGallery/AutoComplete", 'jquery_cookie' ],
        function   ($,blueimpGallery,ContentManager, GalleryRenderer, AutoComplete) {
            $('#gallerySite').show();
            $('#signInSite').hide();
            $("#userNameButton").html(PiGallery.user.userName);

            var contentManager = new ContentManager();
            var galleryRenderer = new GalleryRenderer($("#directory-path"),$("#gallery"),contentManager);

            galleryRenderer.reset();
            contentManager.storeContent(PiGallery.preLoadedDirectoryContent);
            galleryRenderer.showContent(PiGallery.preLoadedDirectoryContent);

            $("#search-button").click(function(event) {
                event.preventDefault();
                contentManager.getSearchResult($("#auto-complete-box").val(),contentRenderer );
            });


            /*------------Log out button-------------------------*/
            $('#logOutButton').click(function(){
                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "logout",
                           sessionID: $.cookie("pigallery-sessionid") },
                    dataType: "json"
                }).done(function(result) {
                    if(result.error == null){
                        $.removeCookie("pigallery-sessionid");
                        PiGallery.user = null;
                        PiGallery.showLogin();
                    }else{
                        alert(result.error);
                    }
                }).fail(function(errMsg) {
                    console.log("Error during downloading singing in");
                });
                return false;
            });

            /*---------------light box setup--------------------*/

          //   $('#blueimp-gallery').data('useBootstrapModal', false);
            PiGallery.lightbox = null;

            $('#photo-gallery').click(function (event) {
                event = event || window.event;
                var target = event.target || event.srcElement,
                    link = target.src ? target.parentNode : target,
                    options = $.extend({}, $('#blueimp-gallery').data(),
                        {index: link, event: event, preloadRange: 1}),
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

            /*----------AutoComplete setup */
            if(PiGallery.searchSupported){
              new AutoComplete($("#auto-complete-box"));
            }
    });
}

PiGallery.showLogin = function(){
    require(['jquery', 'jquery_cookie'],  function   ($) {
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
                    $('#userNameBox').val(""),
                    $('#passwordBox').val(""),
                    PiGallery.user = result.data;
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
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
     /*   jquery: ['//code.jquery.com/jquery-2.1.1.min','jquery-2.1.1.min'],
        jquery_ui: ['//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min','jquery-ui-1.10.4_min'],
        underscore: ['//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min','underscorejs-1.6.0.min'],
        bootstrap: ['//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min','bootstrap.min'],
        jquery_cookie: ['//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min', 'jquery.cookie'],
*/
        jquery:  'jquery-2.1.1.min',
        jquery_ui: 'jquery-ui-1.10.4_min',
        underscore: 'underscorejs-1.6.0.min',
        bootstrap: 'bootstrap',
        jquery_cookie: 'jquery.cookie',
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
        'bootstrap-confirmation':{
            deps: ['jquery','bootstrap']
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



PiGallery.adminSiteInitDone = false;
PiGallery.gallerySiteInitDone = false;
PiGallery.loginSiteInitDone = false;

PiGallery.initSite = function(){
    require(['jquery'], function   ($) {

        $('#galleryButton').click(function () {
            PiGallery.showGallery();
        });
        if (PiGallery.user.role >= 1) {//is it an admin?
            $('#adminButton').show();
            $('#adminButton').click(function () {
                PiGallery.showAdminSite();
            });
        } else {
            $('#adminButton').hide();
        }
    });


};


PiGallery.initLogin = function(){
    require(['jquery', 'jquery_cookie'],  function   ($) {
        $('#gallerySite').hide();
        $('#signInSite').show();

        var showSignProgress = function(show){
            if(show == true){

                $('#userNameBox').attr("disabled", "disabled");
                $('#passwordBox').attr("disabled", "disabled");
                $('#loginButton').attr("disabled", "disabled");
                $('#loginButton').html(PiGallery.LANG.signinInProgress);

            }else{

                $('#userNameBox').removeAttr("disabled");
                $('#passwordBox').removeAttr("disabled");
                $('#loginButton').removeAttr("disabled");
                $('#loginButton').html(PiGallery.LANG.signin);
            }
        };

        $('#signinForm').submit(function(event) {

            showSignProgress(true);

            $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "login",
                    userName: $('#userNameBox').val(),
                    password: $('#passwordBox').val(),
                    rememberMe: $('#rememberMeBox').prop('checked')},
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
                    $("#userNameButton").html(PiGallery.user.userName);
                    PiGallery.showGallery();
                    showSignProgress(false);
                }else{
                    alert(result.error);
                    showSignProgress(false);
                }
            }).fail(function(errMsg) {
                console.log("Error during downloading singing in");
                showSignProgress(false);
            });
            return false;
        });

        PiGallery.loginSiteInitDone = true;

    });
};


PiGallery.initGallery = function(){
    require(['jquery', 'blueImpGallery', 'PiGallery/ContentManager', 'PiGallery/GalleryRenderer', "PiGallery/AutoComplete", 'jquery_cookie' ],
        function   ($,blueimpGallery,ContentManager, GalleryRenderer, AutoComplete) {


            PiGallery.initSite();

            $("#userNameButton").html(PiGallery.user.userName);

            var contentManager = new ContentManager();
            var galleryRenderer = new GalleryRenderer($("#directory-path"), $("#gallery"), contentManager);

            galleryRenderer.reset();
            if (PiGallery.preLoadedDirectoryContent != null) {
                contentManager.storeContent(PiGallery.preLoadedDirectoryContent);
                galleryRenderer.showContent(PiGallery.preLoadedDirectoryContent);
            }else{
                contentManager.getContent(PiGallery.currentPath, galleryRenderer);
            }

            $("#search-button").click(function(event) {
                event.preventDefault();
                contentManager.getSearchResult($("#auto-complete-box").val(),galleryRenderer );
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
                        PiGallery.logOut();
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


            PiGallery.gallerySiteInitDone = true;
        });
};

PiGallery.initAdminSite = function(){
    require(['PiGallery/AdminPage'], function   (AdminPage) {
        var adminPage = new AdminPage($('#admin-container'));
        adminPage.init();
        PiGallery.adminSiteInitDone = true;
    });
};

PiGallery.showAdminSite = function(){
    if(PiGallery.adminSiteInitDone == false){
        PiGallery.initAdminSite();
    }
    require(['jquery'], function   ($) {
        $('#gallerySite').show();
        $('#signInSite').hide();
        $('#gallery-container').hide();
        $('#admin-container').show();
        $('#autocompleteForm').hide();

        $('#galleryButton').removeClass("active");
        $('#adminButton').addClass("active");
    });
};


PiGallery.showGallery = function(){

    if(PiGallery.gallerySiteInitDone == false){
        PiGallery.initGallery();
    }
    require(['jquery'], function   ($) {
        $('#gallerySite').show();
        $('#signInSite').hide();
        $('#gallery-container').show();
        $('#admin-container').hide();
        $('#autocompleteForm').show();

        $('#galleryButton').addClass("active");
        $('#adminButton').removeClass("active");

    });
};

PiGallery.showLogin = function(){

    if(PiGallery.loginSiteInitDone == false){
        PiGallery.initLogin();
    }
    require(['jquery'], function   ($) {
        $('#gallerySite').hide();
        $('#signInSite').show();

    });
};

PiGallery.logOut = function(){
    $.removeCookie("pigallery-sessionid");
    PiGallery.user = null;
    PiGallery.showLogin();
};



PiGallery.showErrorMessage = function(str){
    $('#alerts').append('<div class="alert  alert-danger">' + str  + '</div>');

};


if(PiGallery.user != null){
    PiGallery.showGallery();
}else{
    PiGallery.showLogin();

}
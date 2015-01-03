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
        jquery:  'jquery-2.1.3.min',
        jquery_ui: 'jquery-ui.min',
        bootstrap: 'bootstrap.min',
        jquery_cookie: 'jquery.cookie',
        detectmobilebrowser_jquery: 'detectmobilebrowser_jquery',
        jquery_countdown: 'jquery.countdown.min',
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
        'jquery_cookie': {
            deps: ['jquery']
        },
        'jquery_ui':{
            deps: ['jquery']
        },
        'detectmobilebrowser_jquery':{
            deps: ['jquery']
        }

    }
   /* map: {
        // '*' means all modules will get 'jquery-private'
        // for their 'jquery' dependency.
        '*': { 'blueimp-helper': 'lib/blueimp-helper',
               'blueimp-gallery': 'lib/blueimp-gallery',
                'bootstrap-confirmation': 'lib/bootstrap-confirmation',
                jquery:  'lib/jquery-2.1.1.min'

        }
    }*/

});



PiGallery.adminSiteInitDone = false;
PiGallery.gallerySiteInitDone = false;
PiGallery.loginSiteInitDone = false;

PiGallery.initSite = function(){
    require(['jquery','bootstrap'], function   ($) {

        $('#galleryButton').click(function () {
            PiGallery.showGallery();
        });
        $('#adminButton').click(function () {
            PiGallery.showAdminSite();
        });
    });


};


PiGallery.initLogin = function(){
    require(['jquery', 'jquery_cookie', 'PiGallery/Enums'],  function   ($) {

        $('#gallerySite').hide();
        $('#signInSite').show();

        var showSignProgress = function (show) {
            var $loginButton = $('#loginButton');
            if (show == true) {

                $('#userNameBox').attr("disabled", "disabled");
                $('#passwordBox').attr("disabled", "disabled");
                $loginButton.attr("disabled", "disabled");
                $loginButton.html(PiGallery.LANG.signinInProgress);

            } else {

                $('#userNameBox').removeAttr("disabled");
                $('#passwordBox').removeAttr("disabled");
                $loginButton.removeAttr("disabled");
                $loginButton.html(PiGallery.LANG.signin);
            }
        };
        $('#signinForm').submit(function () {

            showSignProgress(true);

            $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "login",
                    userName: $('#userNameBox').val(),
                    password: $('#passwordBox').val(),
                    rememberMe: $('#rememberMeBox').prop('checked')},
                dataType: "json"
            }).done(function (result) {
                if (result.error == null) {
                    if ($('#rememberMeBox').attr('checked')) {
                        $.cookie("pigallery-sessionid", result.data.sessionID, { expires: 30 });
                    } else {
                        $.cookie("pigallery-sessionid", result.data.sessionID, { expires: 1 });
                    }
                    $('#userNameBox').val("");
                    $('#passwordBox').val("");
                    PiGallery.user = result.data;
                    $("#userNameButton").html(PiGallery.user.userName);
                    PiGallery.showGallery();
                    showSignProgress(false);
                } else {
                    if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                        PiGallery.logOut();
                        return;
                    }
                    alert(result.error);
                    showSignProgress(false);
                }
            }).fail(function () {
                console.log("Error during downloading singing in");
                showSignProgress(false);
            });
            return false;
        });

        //try local logining in
        if ((!PiGallery.user || PiGallery.user == null) && PiGallery.guestAtLocalNetwrokEnbaled === true) {
            var url = "//"+PiGallery.localServerUrl + "/" + PiGallery.documentRoot ;
            $.ajax({
                url: url+ "/localtest.php?callback=?",
                dataType: "jsonp",
                jsonpCallback: 'jsonCallback'
            }).done(function () {
                window.location = url;
            });
        }

        PiGallery.loginSiteInitDone = true;

    });
};


PiGallery.initModalLogin = function(galleryRenderer){
    require(['jquery', 'jquery_cookie', 'PiGallery/Enums'],  function   ($) {

        var showSignProgress = function(show){
            var $loginButton = $('#modalLoginButton');
            if(show == true){

                $('#modalUserNameBox').attr("disabled", "disabled");
                $('#modalPasswordBox').attr("disabled", "disabled");
                $loginButton.attr("disabled", "disabled");
                $loginButton.html(PiGallery.LANG.signinInProgress);

            }else{

                $('#modalUserNameBox').removeAttr("disabled");
                $('#modalPasswordBox').removeAttr("disabled");
                $loginButton.removeAttr("disabled");
                $loginButton.html(PiGallery.LANG.signin);
            }
        };
        $('#modalSigninForm').submit(function () {

            showSignProgress(true);

            $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "login",
                    userName: $('#modalUserNameBox').val(),
                    password: $('#modalPasswordBox').val(),
                    rememberMe: $('#modalRememberMeBox').prop('checked')},
                dataType: "json"
            }).done(function (result) {
                if (result.error == null) {
                    if ($('#rememberMeBox').attr('checked')) {
                        $.cookie("pigallery-sessionid", result.data.sessionID, { expires: 30 });
                    } else {
                        $.cookie("pigallery-sessionid", result.data.sessionID, { expires: 1 });
                    }
                    $('#modalUserNameBox').val("");
                    $('#modalPasswordBox').val("");
                    PiGallery.user = result.data;
                    $("#userNameButton").html(PiGallery.user.userName);
                    PiGallery.showGallery();
                    $("#loginModal").modal("hide");
                    showSignProgress(false);
                    galleryRenderer.refresh();
                } else {
                    if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                        PiGallery.logOut();
                        return;
                    }
                    alert(result.error);
                    showSignProgress(false);
                }
            }).fail(function () {
                console.log("Error during downloading singing in");
                showSignProgress(false);
            });
            return false;
        });



        PiGallery.loginSiteInitDone = true;

    });
};

PiGallery.initGallery = function(){
    require(['jquery', 'blueImpGallery', 'PiGallery/ContentManager', 'PiGallery/GalleryRenderer', "PiGallery/AutoComplete", 'jquery_cookie', 'PiGallery/Enums' ],
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
                galleryRenderer.showContent(contentManager.getContent(PiGallery.currentPath, galleryRenderer));
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
                        {index: link, event: event, preloadRange: 1, startSlideshow: true}),
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

            /*Init modal login for guests*/
            PiGallery.initModalLogin(galleryRenderer);
            
            /*Init sharing*/
            var $shareButton = $("#shareButton"),
                $shareModal = $("#shareModal");
            if($shareModal.length != 0 && $shareButton.length != 0){
                
                $shareButton.click(function(){

                    $("#loading-sign").css("opacity",1);
                    $.ajax({
                        type: "POST",
                        url: "model/AJAXfacade.php",
                        data: {method: "share",
                               dir: galleryRenderer.directoryContent.currentPath },
                        dataType: "json"
                    }).done(function(result) {
                        if (result.error == null) {
                            console.log(result.data);
                            $("#loading-sign").css("opacity", 0);
                            $shareModal.modal('show');
                            $("#shareLink").val(result.data.link);
                        }else{
                            if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                                PiGallery.logOut();
                                return;
                            }
                            PiGallery.showErrorMessage(result.error);
                        }
                    }).fail(function() {
                        console.log("Error during sharing");
                        $("#loading-sign").css("opacity",0);
                    });
                });
                
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
    require(['jquery','jquery_countdown', 'PiGallery/Enums'], function   ($) {
        var $adminButton = $("#adminButton");
        
        
        
        if(PiGallery.user.role >= PiGallery.enums.Roles.Admin){
            $adminButton.show();
        }else{
            $adminButton.hide();
        }
        
        if(PiGallery.user.role <= PiGallery.enums.Roles.LocalGuest){
            $("#logOutButton").hide();
            $("#signinButton").show();
        }else{
            $("#logOutButton").show();
            $("#signinButton").hide();
        }
        
        if(PiGallery.user.role <= PiGallery.enums.Roles.RemoteGuest){
            $("#autocompleteForm").hide();           
        }else if(PiGallery.searchSupported){
            $("#autocompleteForm").show();
        }
        
        if(PiGallery.user.pathRestriction && PiGallery.user.pathRestriction.validTime){
            $('#linkCountDown').show();
            $('#linkCountDown').countdown(Date.parse(PiGallery.user.pathRestriction.validTime.replace(' ', 'T')), function(event) {
                $(this).html(event.strftime('Link valid: %-D days %H:%M:%S'));
            });
        }else{
            $('#linkCountDown').hide();
        }
        
        if(PiGallery.user.role <= PiGallery.enums.Roles.User){
            $("#shareButton").hide();
        }else{
            $("#shareButton").show();
        }

        $adminButton.removeClass("active");
        $('#gallerySite').show();
        $('#signInSite').hide();
        $('#gallery-container').show();
        $('#admin-container').hide();

        $('#galleryButton').addClass("active");

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
    $('#alertsDiv').append('<div class="alert  alert-danger">' + str  + '</div>');

};
PiGallery.showInfoMessage = function(str){
    $('#alertsDiv').append('<div class="alert  alert-info">' + str  + '</div>');

};
PiGallery.showWarningMessage = function(str){
    $('#alertsDiv').append('<div class="alert  alert-warning">' + str  + '</div>');

};


PiGallery.hideMessages = function(){
    $('#alertsDiv').empty();
};


if(PiGallery.user != null){
    PiGallery.showGallery();
}else{
    PiGallery.showLogin();

}
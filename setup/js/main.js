require.config({
    baseUrl:  './js/',
    paths: {
        PiGallery: './../../js/pigallery',
        
     // CDN fallbacks
      /*  jquery: ['//code.jquery.com/jquery-2.1.3.min','lib/jquery-2.1.3.min'],
        jquery_ui: ['//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min','jquery-ui-1.10.4_min'],
        underscore: ['//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min','underscorejs-1.6.0.min'],
        bootstrap: ['//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min','bootstrap.min'],
        jquery_cookie: ['//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min', 'jquery.cookie'],
*/
        jquery:  '../../js/lib/jquery-2.1.3.min',
        toggle:  '../../js/lib/bootstrap-toggle.min',
        jquery_ui: '../../js/lib/jquery-ui.min',
        bootstrap: '../../js/lib/bootstrap.min',
        bootstrapSlider: '../../js/lib/bootstrap-slider.min',
        jquery_cookie: '../../js/lib/jquery.cookie',
        detectmobilebrowser_jquery: '../../js/lib/detectmobilebrowser_jquery',
        jquery_countdown: '../../js/lib/jquery.countdown.min',
        blueImpGallery: '../../js/lib/blueimp-gallery-indicator',
        zeroClipboard: '../../js/lib/ZeroClipboard.min'
    },
    shim:  {
        'blueImpGallery' : {
            deps: ['jquery']
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

    },
    map: {
        // '*' means all modules will get 'jquery-private'
        // for their 'jquery' dependency.
        '*': { 'blueimp-helper': '../../js/lib/blueimp-helper',
            'blueimp-gallery': '../../js/lib/blueimp-gallery',
            'blueimp-gallery-fullscreen': '../../js/lib/blueimp-gallery-fullscreen',
                'bootstrap-confirmation': '../../js/lib/bootstrap-confirmation'

        }
    }

});
var PiGallery = PiGallery || {};


require(['jquery','bootstrap','toggle' ,'jquery_ui', 'bootstrapSlider','PiGallery/Enums' ], function   ($) {
    "use strict";


    var TypicalMode = 0, CustomMode = 1;
    var installerMode = TypicalMode;
    var UseDatabase = 0, NoDatabase = 1;
    var databaseMode = NoDatabase;
    var $currentPanel = $( "#installerChooser" );

    function showPanel($panel, reversed){
        if(!reversed) {
            $currentPanel.hide("slide", {direction: "left"});
            $panel.show("slide", {direction: "right"});
        }else{

            $currentPanel.hide("slide", {direction: "right"});
            $panel.show("slide", {direction: "left"});
        }

        $currentPanel = $panel;
    }

    $(function () {
        $('[data-toggle="popover"]').popover();
    });
    $('#thumbnailJPEGQuality').slider({
        formatter: function(value) {
            return 'Quality: ' + value;
        }
    });


    $('#typicalMode').click(function(){
        installerMode = TypicalMode;
        $('[data-advanced-setup="true"]').hide();
        showPanel($( "#basicSettings" ));
        return false;
    });

    $('#customMode').click(function(){
        installerMode = CustomMode;
        $('[data-advanced-setup="true"]').show();
        showPanel($( "#basicSettings" ));
        return false;
    });

    $('#backToInstallerChooser').click(function(){
        showPanel($( "#installerChooser" ), true);
        return false;
    });


    $('#validateBasicSettings').click(function(){

        $.ajax({
            type: "POST",
            url: "setupAJAXfacade.php",
            data: {method: "validateImageAndThumbnailFolder", documentRoot: $("#documentRoot").val(), imageFolder: $("#imageFolder").val(),thumbnailFolder: $("#thumbnailFolder").val()},
            dataType: "json"
        }).done(function(result) {
            if(result.error != null){
                PiGallery.showErrorMessage(result.error.message);
            }else  {
                if(installerMode == CustomMode){
                    showPanel($( "#thumbnailSettings" ) );
                }else{
                    showPanel($( "#modeChooser" ));
                }
            }

        }).fail(function() {
            PiGallery.showErrorMessage("Error during validating basic settings");
        });


        return false;
    });

    $('#backToBasicSettings').click(function(){
        showPanel($( "#basicSettings" ), true);
        return false;
    });

    $('#nextFromThumbnailSettings').click(function(){
        showPanel($( "#modeChooser" ));
        return false;
    });

    $('#backFromModeChooser').click(function(){
        if(installerMode == CustomMode){
            showPanel($( "#thumbnailSettings" ), true);

        }else{
            showPanel($( "#basicSettings" ), true);
        }
        return false;
    });

    $('#nonDatabaseMode').click(function(){
        databaseMode = NoDatabase;
        showAddUserPanel();
        return false;
    });
    $('#databaseMode').click(function(){
        databaseMode = UseDatabase;
        showPanel($( "#databaseSettings" ));
        return false;
    });


    $('#backFromDatabaseSettings').click(function(){
        showPanel($( "#modeChooser" ), true);
        return false;
    });
    $('#validateDatabaseSettings').click(function(){
        $.ajax({
            type: "POST",
            url: "setupAJAXfacade.php",
            data: {method: "validateDataBaseSettings",
                databaseAddress: $("#databaseAddress").val(),
                databaseUserName: $("#databaseUserName").val(),
                databasePassword: $("#databasePassword").val(),
                databaseName: $("#databaseName").val()},
            dataType: "json"
        }).done(function(result) {

            if(result.error != null){
                PiGallery.showErrorMessage(result.error.message);
            }else {
                showAddUserPanel();
            }

        }).fail(function() {
            PiGallery.showErrorMessage("Error during validating data base settings");
        });
        return false;
    });


    $('#backFromAddUser').click(function(){
        if(databaseMode == NoDatabase){
            showPanel($( "#modeChooser" ), true);

        }else{
            showPanel($( "#databaseSettings" ), true);
        }
        return false;
    });

    $("#addNewUser").click(function(){
        $("#userInfoPrototype").clone()
            .insertBefore("#addNewUser-group")
            .addClass("userInfos")
            .show()
            .find('.userDeleteButton').click(function(){
                $(this).parent().parent().remove();
        });
    });


    PiGallery.showErrorMessage = function(str){
        $('#alertsDiv').append('<div class="alert  alert-danger">' + str  + '</div>');

    };

    function showAddUserPanel(){

        $.ajax({
            type: "POST",
            url: "setupAJAXfacade.php",
            data: {method: "getUsersList",
                databaseMode: databaseMode == UseDatabase ? "UseDatabase" : "NoDatabase",
                databaseAddress: $("#databaseAddress").val(),
                databaseUserName: $("#databaseUserName").val(),
                databasePassword: $("#databasePassword").val(),
                databaseName: $("#databaseName").val()},
            dataType: "json"
        }).done(function(result) {
            if (result.error != null) {
                PiGallery.showErrorMessage(result.error.message);
            } else {
                for (var i = 0; i < result.data.length; i++) {
                    if(result.data[i].role == PiGallery.enums.Roles.Admin){
                        if(databaseMode == UseDatabase) {
                            $('#adminUserID').val(result.data[i].id);
                        }
                        $("#adminUserName").val(result.data[i].userName);
                        $("#adminPassword").val(result.data[i].password);
                        result.data.splice(i, 1);

                        break;
                    }
                }
                $(".userInfos").remove();
                for (var i = 0; i < result.data.length; i++) {
                    var $newDiv = $("#userInfoPrototype").clone().insertBefore("#addNewUser-group");
                    $newDiv.addClass("userInfos");
                    if(databaseMode == UseDatabase) {
                        $newDiv.find('[data-user="id"]').val(result.data[i].id);
                    }
                    $newDiv.find('[data-user="name"]').val(result.data[i].userName);
                    $newDiv.find('[data-user="password"]').val(result.data[i].password);
                    $newDiv.find('[data-user="role"]').val(result.data[i].role);

                    if(databaseMode == UseDatabase) {
                        $newDiv.find('[data-user="name"]').attr("disabled","disabled");
                        $newDiv.find('[data-user="password"]').attr("disabled","disabled");
                        $newDiv.find('[data-user="role"]').attr("disabled","disabled");
                    }
                    $newDiv.show().find('.userDeleteButton').click(function(){
                        $(this).parent().parent().remove();
                    });
                }

                showPanel($("#addUsers"));
            }
        }).fail(function() {
            PiGallery.showErrorMessage("Error during validating data base settings");
        });
    }


    $("#save").click(function(){


        var $users = [];
        $users.push({
            userName: $("#adminUserName").val(),
            password: $("#adminPassword").val(),
            role: PiGallery.enums.Roles.Admin,
            id:$("#adminUserID").val()
        });
        $( ".userInfos" ).each(function(  ) {

            $users.push({
                userName: $( this ).find('[data-user="name"]').val(),
                password: $( this ).find('[data-user="password"]').val(),
                role: $( this ).find('[data-user="role"]').val(),
                id: $( this ).find('[data-user="id"]').val()
            });

        });
        var properties ={

              $language: $("#lang").val(),
              $siteUrl: $("#siteUrl").val(),
              $documentRoot: $("#documentRoot").val(),
             $imageFolder: $("#imageFolder").val(),

            $thumbnailFolder: $("#thumbnailFolder").val(),
            $thumbnailSizes: $("#thumbnailSizes").val(),
            $thumbnailJPEGQuality: $("#thumbnailJPEGQuality").val(),
            $EnableThumbnailResample:$("#EnableThumbnailResample").is(':checked'),
            $enableImageCaching  : $("#enableImageCaching").is(':checked'),

            $enableUTF8Encode  : $("#enableUTF8Encode").is(':checked'),

            $databaseEnabled  : databaseMode == UseDatabase,

            $databaseAddress  : $("#databaseAddress").val(),
            $databaseUserName  :$("#databaseUserName").val(),
            $databasePassword  : $("#databasePassword").val(),
            $databaseName  : $("#databaseName").val(),
            $enableSearching : $("#enableSearching").is(':checked'),

            $enableSharing : $("#enableSharing").is(':checked'),

            $enableOnTheFlyIndexing  : $("#enableOnTheFlyIndexing").is(':checked'),

            $maxSearchResultItems  : $("#maxSearchResultItems").val(),

            $GuestLoginAtLocalNetworkEnabled  : $("#GuestLoginAtLocalNetworkEnabled").is(':checked'),

            $users: $users
        };
        
        
        $.ajax({
            type: "POST",
            url: "setupAJAXfacade.php",
            data: {method: "saveSettings",
                properties: JSON.stringify(properties)},
            dataType: "json"
        }).done(function(result) {
            if (result.error != null) {
                PiGallery.showErrorMessage(result.error.message);
            } else {
                window.location = "../index.php";
            }
        }).fail(function() {
            PiGallery.showErrorMessage("Error during validating data base settings");
        });
    });
});
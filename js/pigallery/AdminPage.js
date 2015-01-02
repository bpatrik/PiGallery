var PiGallery = PiGallery || {};

define(['jquery', 'bootstrap-confirmation', 'PiGallery/Enums'], function($) {

    return function AdminPage($AdminPageDiv) {
        this.init = function(){

            //Reset database button
            $AdminPageDiv.find('#resetDatabaseButton').confirmation({
                singleton: true,
                popout: true,
                btnOkLabel: "Yes",
                onConfirm: function () {
                    $.ajax({
                        type: "POST",
                        url: "model/AJAXfacade.php",
                        data: {method: "recreateDatabase"},
                        dataType: "json"
                    }).done(function (result) {
                        if (result.error == null) {
                            PiGallery.logOut();
                        } else {
                            if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                                PiGallery.logOut();
                                return;
                            }
                            alert(result.error);
                        }
                    }).fail(function (errMsg) {
                        console.log("Error during resetting db");
                    });
                    return false;
                }
            });

            //Clear Photos Table
            $AdminPageDiv.find('#clearTableButton').confirmation({
                singleton: true,
                popout: true,
                btnOkLabel: "Yes",
                onConfirm: function () {
                    $.ajax({
                        type: "POST",
                        url: "model/AJAXfacade.php",
                        data: {method: "clearGalleryDatabase"},
                        dataType: "json"
                    }).done(function (result) {
                        if (result.error == null) {
                            $AdminPageDiv.find('#indexingProgress').html("Indexes cleared");
                        } else {
                            if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                                PiGallery.logOut();
                                return;
                            }
                            alert(result.error);
                        }
                    }).fail(function (errMsg) {
                        console.log("Error during clearing tables");
                    });
                    return false;
                }
            });



            $AdminPageDiv.find('#indexPhotosButton').click(indexPhotosButtonClickHandler);

            var userDeleteHandler = function (event, element) {
                var userId = element.data("userid");
                element.attr("disabled", "disabled");
                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "deleteUser", id: userId},
                    dataType: "json"
                }).done(function (result) {
                    if (result.error == null) {
                        updateUserList();
                    } else {
                        if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                            PiGallery.logOut();
                            return;
                        }
                        alert(result.error);
                    }
                }).fail(function (errMsg) {
                    console.log("Error during delete user");
                });

                return false;
            };

            var roleFromInt = function (role) {
                switch (role) {
                    case PiGallery.enums.Roles.RemoteGuest:
                        return "Guest";
                    case PiGallery.enums.Roles.LocalGuest:
                        return "Guest";
                    case PiGallery.enums.Roles.User:
                        return "User";
                    case PiGallery.enums.Roles.Admin:
                        return "Admin";
                    default:
                        return "N/A";
                }
            };
            var updateUserList = function () {
                //show users
                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "getUsersList"},
                    dataType: "json"
                }).done(function (result) {
                    if (result.error == null) {
                        var $adminUsersList = $('#adminUsersList'),
                            i;
                        $adminUsersList.empty();
                        for (i = 0; i < result.data.users.length; i++) {

                            var $deleteButton = $('<span>');

                            if (PiGallery.user.userName != result.data.users[i].userName) {//don't allow self deleting
                                $deleteButton = $('<button>', {"class": "btn btn-default btn-danger",
                                    "data-userId": result.data.users[i].id}).html('Delete')
                                    .confirmation({
                                        singleton: true,
                                        popout: true,
                                        btnOkLabel: "Yes",
                                        onConfirm: userDeleteHandler});

                            }

                            $adminUsersList.append(
                                $('<tr>').append(
                                    $('<td>').html(i),
                                    $('<td>').html(result.data.users[i].userName),
                                    $('<td>').html(roleFromInt(result.data.users[i].role)),
                                    $('<td>').append($deleteButton)
                                )
                            );
                        }
                    } else {
                        if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                            PiGallery.logOut();
                            return;
                        }
                        alert(result.error);
                    }
                }).fail(function () {
                    console.log("Error during loading userlist");
                });
            };

                $AdminPageDiv.find('#adminRegisterForm').submit(function(){
                $AdminPageDiv.find('#adminAddUserButton').attr("disabled", "disabled");
                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "registerUser",
                        userName: $AdminPageDiv.find('#adminRegisterUserName').val(),
                        password: $AdminPageDiv.find('#adminRegisterPassword').val(),
                        role: $AdminPageDiv.find('#adminRegisterRole').val()},
                    dataType: "json"
                }).done(function (result) {
                    if (result.error == null) {
                        updateUserList();
                    } else {
                        if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                            PiGallery.logOut();
                            return;
                        }
                        alert(result.error);
                    }
                    $AdminPageDiv.find('#adminAddUserButton').removeAttr("disabled");

                }).fail(function () {
                    $('#adminAddUserButton').removeAttr("disabled");
                    console.log("Error during registering");
                });
                return false;
            });

            updateUserList();

        };


        var directoriesToIndex = [];
        var lastDirectory = "";
        var retryCount = 0;
        var indexDirectory = function (directory) {
            console.log(directoriesToIndex);
            var indexNextDirectory = function() {
                if (directoriesToIndex.length > 0) {
                    lastDirectory = directoriesToIndex.pop();
                    retryCount = 0;
                    indexDirectory(lastDirectory);
                } else {
                    $AdminPageDiv.find('#indexingProgress').html("Indexing done.");
                    $AdminPageDiv.find('#indexPhotosButton').removeAttr("disabled");
                    $AdminPageDiv.find('#indexPhotosButton').html(PiGallery.LANG.admin_indexPhotos);
                }
            };

            $AdminPageDiv.find('#indexingProgress').html("Indexing: \"" + directory + "\" (" + directoriesToIndex.length + " left)")

            $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "indexDirectory", dir: directory},
                dataType: "json"
            }).done(function (result) {
                if (result.error == null) {
                    directoriesToIndex = directoriesToIndex.concat(result.data.foundDirectories);
                    indexNextDirectory();
                } else {
                    if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                        PiGallery.logOut();
                        return;
                    }
                    alert(result.error);
                }
            }).fail(function () {
                console.log("Error during indexing directories");
                if(retryCount < 3){
                    PiGallery.showWarningMessage("Error during indexing directory: '" +lastDirectory + "' (Possibly php timeout). Retrying...");
                    retryCount++;
                    indexDirectory(lastDirectory);
                }else{
                    PiGallery.showErrorMessage("Error during indexing directory: '" +lastDirectory + "' (Possibly php timeout). Skipping...");
                    indexNextDirectory();
                }
            });
        };

        var indexPhotosButtonClickHandler = function(){
            $AdminPageDiv.find('#indexPhotosButton').attr("disabled", "disabled");
            $AdminPageDiv.find('#indexPhotosButton').html(PiGallery.LANG.admin_indexing);
            retryCount = 0;
            directoriesToIndex = [];
            indexDirectory("/");
        };



    }

});
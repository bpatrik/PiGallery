var PiGallery = PiGallery || {};

define(['jquery', 'bootstrap-confirmation'], function($) {

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
                        } else {
                            alert(result.error);
                        }
                    }).fail(function (errMsg) {
                        console.log("Error during clearing tables");
                    });
                    return false;
                }
            });

            var directoriesToIndex = [];
            var indexDirectory = function (directory) {
                $AdminPageDiv.find('#indexingProgress').html("Indexing: \"" + directory + "\" (" + directoriesToIndex.length + " left)")
                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "indexDirectory", dir: directory},
                    dataType: "json"
                }).done(function (result) {
                    if (result.error == null) {
                        directoriesToIndex = directoriesToIndex.concat(result.data.foundDirectories);
                        if (directoriesToIndex.length > 0) {
                            indexDirectory(directoriesToIndex.pop())
                        } else {
                            $AdminPageDiv.find('#indexingProgress').html("Indexing done.");
                        }
                    } else {
                        alert(result.error);
                    }
                }).fail(function (errMsg) {
                    console.log("Error during indexind directories");
                });
            };

            $AdminPageDiv.find('#indexPhotosButton').click(function () {
                indexDirectory("/");
            });

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
                        alert(result.error);
                    }
                }).fail(function (errMsg) {
                    console.log("Error during delete user");
                });

                return false;
            };

            var rolefromInt = function (role) {
                switch (role) {
                    case 0:
                        return "User";
                    case 1:
                        return "Admin";
                    default:
                        return "User";
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
                                    $('<td>').html(rolefromInt(result.data.users[i].role)),
                                    $('<td>').append($deleteButton)
                                )
                            );
                        }
                    } else {
                        alert(result.error);
                    }
                }).fail(function (errMsg) {
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
                        alert(result.error);
                    }
                    $AdminPageDiv.find('#adminAddUserButton').removeAttr("disabled");

                }).fail(function (errMsg) {
                    $('#adminAddUserButton').removeAttr("disabled");
                    console.log("Error during registering");
                });
                return false;
            });

            updateUserList();

        }

    }

});
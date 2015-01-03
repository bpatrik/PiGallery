var PiGallery = PiGallery || {};
define( function () {
    "use strict";
    PiGallery.enums = {
        Roles: {
            RemoteGuest: 10,
            LocalGuest: 11,
            User: 20,
            Admin: 30
        },
        AjaxErrors: {
            GENERAL_ERROR: 10,
            AUTHENTICATION_FAIL: 20
        }

    };
});
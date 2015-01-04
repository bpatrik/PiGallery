define([],function () {
    "use strict";
    return function SharingManager(galleryRenderer) {

        var _sharingModule = null;
        var _galleryRenderer = galleryRenderer;
        var _init = function() {
            require(["PiGallery/SharingModule" ], function (SharingModule) {
                _sharingModule = new SharingModule(_galleryRenderer);
                _sharingModule.show();
            });
        };

        this.show = function(){
            if(!PiGallery.Supported.Share)
                return;
            
            if(_sharingModule === null){
                _init();                
            }else{
                _sharingModule.show();                
            }
        };
        
        this.hide = function(){
            if(_sharingModule !== null){
                _sharingModule.hide();
            }
        }
    }
});
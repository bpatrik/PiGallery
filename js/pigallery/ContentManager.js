define(["jquery", 'PiGallery/Enums'], function ($) {
    "use strict";



   return function ContentManager() {


        this.lastXhr = null;
        var that = this;

        var indexFolder = function(path, galleryRenderer){
            return $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "indexDirectoryAndGetContent", dir: path },
                    dataType: "json"
                }).done(function(result) {
                    if(result.error != null){
                        if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                            PiGallery.logOut();
                            that.lastXhr = null;
                            return;
                        }
                        PiGallery.showErrorMessage(result.error.message);
                    }else if(result.data != null) { //if the directory content changed comparing to the cached one
                        that.storeContent(result.data);
                        galleryRenderer.showContent(result.data);
                    }
                    that.lastXhr = null;
                    $("#loading-sign").css("opacity",0);

                }).fail(function() {
                    PiGallery.showErrorMessage("Error during indexing folder");
                    that.lastXhr = null;
                    $("#loading-sign").css("opacity",0);
                });
        };

        this.getContent = function(path, galleryRenderer){
            var cachedContent = getLocalStoredContent(path),
                lastModificationDate = null;

            //check if last modification date is available
            if(cachedContent.lastModificationDate){
                lastModificationDate = cachedContent.lastModificationDate;
            }

            if (that.lastXhr  && that.lastXhr.readyState != 4){
                that.lastXhr.abort();
                that.lastXhr = null;
            }

            var ajaxArray = {method: "getContent", dir: path, lastModificationDate: lastModificationDate};
            if(PiGallery.shareLink){
                ajaxArray.s=PiGallery.shareLink;
            }
            
            that.lastXhr =
                $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: ajaxArray,
                dataType: "json"
            }).done(function(result) {
                    if(result.error != null){
                        if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                            PiGallery.logOut();
                            that.lastXhr = null;
                            return;
                        }
                        PiGallery.showErrorMessage(result.error.message);
                    }else if(result.data != null && result.data.noChange == false) { //if the directory content changed comparing to the cached one
                        that.storeContent(result.data);
                        galleryRenderer.showContent(result.data);

                    }
                    if(result.data != null && result.data.indexingNeeded && result.data.indexingNeeded == true){
                        that.lastXhr = indexFolder(path, galleryRenderer);
                    }else{
                        that.lastXhr = null;
                        $("#loading-sign").css("opacity",0);
                    }

            }).fail(function() {
                    PiGallery.showErrorMessage("Error during downloading directory content");
                    that.lastXhr = null;
                    $("#loading-sign").css("opacity",0);
            });
            $("#loading-sign").css("opacity",1);
            return cachedContent;
        };

        var getLocalStoredContent = function(path){
            var storedContent = null;
            if(window.sessionStorage) {
                storedContent = JSON.parse(window.sessionStorage.getItem("PiGallery:Content:" + path));
            }
            if(storedContent == null){
                return {currentPath: path, lastModificationDate: null, directories: [], photos: []};
            }
            return storedContent;

        };

        this.storeContent = function(content){
            if(window.sessionStorage) {
                 window.sessionStorage.setItem("PiGallery:Content:"+content.currentPath, JSON.stringify(content));
            }
        };


        this.getSearchResult = function(searchString, galleryRenderer){
            if(PiGallery.user && PiGallery.user.role <= PiGallery.enums.Roles.RemoteGuest){ //remote guest not allowed to search
                return;
            }
            if (that.lastXhr  && that.lastXhr.readyState != 4){
                that.lastXhr.abort();
                that.lastXhr = null;
            }
            var ajaxArray = {method: "search", searchString: searchString};

            that.lastXhr =
            $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: ajaxArray,
                dataType: "json"
            }).done(function(result) {
                if(result.error == null){
                    galleryRenderer.showSearchResult(result.data);
                }else{
                    if(result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL){
                        PiGallery.logOut();
                        that.lastXhr = null;
                        return;
                    }
                    PiGallery.showErrorMessage(result.error.message);
                }
                that.lastXhr = null;
                $("#loading-sign").css("opacity",0);

            }).fail(function() {
                console.log("Error during downloading search result content");
                $("#loading-sign").css("opacity",0);
                that.lastXhr = null;
            });
            $("#loading-sign").css("opacity",1);
        };

   };
});

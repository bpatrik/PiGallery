define(["jquery"], function ($) {
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
                        PiGallery.showErrorMessage(result.error);
                    }else if(result.data != null) { //if the directory content changed comparing to the cached one
                        that.storeContent(result.data);
                        galleryRenderer.showContent(result.data);
                    }
                    that.lastXhr = null;
                    $("#loading-sign").css("opacity",0);

                }).fail(function(errMsg) {
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

            that.lastXhr =
                $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "getContent", dir: path, lastModificationDate: lastModificationDate},
                dataType: "json"
            }).done(function(result) {
                    if(result.error != null){
                        PiGallery.showErrorMessage(result.error);
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

            }).fail(function(errMsg) {
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
            console.log("invoking");

                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "search", searchString: searchString},
                    dataType: "json"
                }).done(function(result) {
                    if(result.error == null){
                        galleryRenderer.showSearchResult(result.data);
                    }else{
                        console.log(result.error);
                    }

                }).fail(function(errMsg) {
                    console.log("Error during downloading search result content");
                });
        };

   };
});

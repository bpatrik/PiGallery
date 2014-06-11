define(["jquery"], function ($) {
    "use strict";



   return function ContentManager() {


        this.lastXhr = null;
        var that = this;
        this.getContent = function(path, galleryRenderer){

            if (this.lastXhr  && this.lastXhr.readyState != 4){
                this.lastXhr.abort();
                this.lastXhr = null;
            }

            this.lastXhr =
                $.ajax({
                type: "POST",
                url: "model/AJAXfacade.php",
                data: {method: "getContent", dir: path},
                dataType: "json"
            }).done(function(result) {
                    if(result.error != null){
                        PiGallery.showErrorMessage(result.error);
                    }else if(result.data != null){
                        that.storeContent(result.data);
                        galleryRenderer.showContent(result.data);
                    }
                    that.lastXhr = null;
                    $("#loading-sign").css("opacity",0);

            }).fail(function(errMsg) {
                    PiGallery.showErrorMessage("Error during downloading directory content");
                    that.lastXhr = null;
                    $("#loading-sign").css("opacity",0);
            });
            $("#loading-sign").css("opacity",1);
            return getLocalStoredContent(path);
        };

        var getLocalStoredContent = function(path){
            var storedContent =  JSON.parse(window.sessionStorage.getItem("PiGallery:Content:"+path));
            if(storedContent == null){
                return {currentPath: path, directories: [], photos: []};
            }
            return storedContent;

        };

        this.storeContent = function(content){

            window.sessionStorage.setItem("PiGallery:Content:"+content.currentPath, JSON.stringify(content));
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

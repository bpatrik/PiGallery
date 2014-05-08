var PiGallery = PiGallery || {};

define(["jquery"], function ($) {
    "use strict";
   return function ContentManager() {
        this.lastXhr = null;

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
            }).done(function(data) {
                    console.log("done");
                    this.lastXhr = null;
                    storeContent(data);
                    galleryRenderer.showContent(data);

            }).fail(function(errMsg) {
                    console.log("Error during downloading directory content");
            });
            console.log("Ret");
            return getLocalStoredContent(path);
        };

        var getLocalStoredContent = function(path){
            var storedContent =  JSON.parse(window.sessionStorage.getItem("PiGallery:Content:"+path));
            if(storedContent == null){
                return {currentPath: path, directories: [], photos: []};
            }
            return storedContent;

        };

        var storeContent = function(content){

            window.sessionStorage.setItem("PiGallery:Content:"+content.currentPath, JSON.stringify(content));
        };


        this.getSearchResult = function(searchString, galleryRenderer){
            console.log("invoking");

                $.ajax({
                    type: "POST",
                    url: "model/AJAXfacade.php",
                    data: {method: "search", searchString: searchString},
                    dataType: "json"
                }).done(function(data) {
                    console.log("done");
                    galleryRenderer.showSearchResult(data);

                }).fail(function(errMsg) {
                    console.log("Error during downloading search result content");
                });
        };

   };
});

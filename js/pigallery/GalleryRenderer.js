define(["jquery", "underscore", "PiGallery/ThumbnailManager",  "PiGallery/DirectoryRenderer", "PiGallery/PhotoRenderer" ], function ($, _, ThumbnailManager,  DirectoryRenderer, PhotoRenderer) {
    "use strict";
    return function GalleryRenderer(directoryPathOl, galleryDiv, ContentManager){

        /*Image gallery*/
        var $galleryDiv = $(galleryDiv),
            $directoryGalleryDiv = $galleryDiv.find("#directory-gallery"),
            $photoGalleryDiv = $galleryDiv.find("#photo-gallery"),
            $directoryPathOl = $(directoryPathOl),

            that = this,

            contentManager = ContentManager,
            thumbnailManager  = new ThumbnailManager(),
            directoryRenderer = new DirectoryRenderer($directoryGalleryDiv, thumbnailManager, this),
            photoRenderer = new PhotoRenderer($photoGalleryDiv, thumbnailManager, this);

        /*-----------Constructor code------------*/

        this.reset = function () {
            $directoryGalleryDiv.empty();
            $photoGalleryDiv.empty();
        }
        /*-------Functions-------------*/
        var saveHistory = function(url){
            if(history.pushState && history.replaceState) {
                window.history.pushState({path: that.directoryContent.currentPath }, document.title, url);
            }
        };

        this.changeContent = function(path, url){
            that.showContent(contentManager.getContent(path, that));
            saveHistory(url);
        };

        this.searchFor = function(path, url){
            that.showContent(contentManager.getSearchResult(path, that));
            saveHistory(url);
        };

        window.onpopstate = function(event){
            var path = event.state ? event.state.path : "/";
            that.showContent(contentManager.getContent(path,that));
        };


        this.showSearchResult = function (searchContent) {

            $directoryGalleryDiv.empty();
            $photoGalleryDiv.empty();

            this.showSearchedText(searchContent.searchString);
            directoryRenderer.showDirectories(searchContent.directories);
            photoRenderer.showImages(searchContent.photos);
        };



        /**
         * Show the given content
         * If the directory not changed, updates the content
         * @param directoryContent
         */
        this.showContent = function (directoryContent) {

            var newPhotos = directoryContent.photos,
                newDirectories = directoryContent.directories;

            //check directory change
            if (this.directoryContent) {
                if (this.directoryContent.currentPath !== directoryContent.currentPath) {//directory changed, empty gallery
                    $directoryGalleryDiv.empty();
                    $photoGalleryDiv.empty();
                } else { //dir name remained

                    //filter already shown photos and dirs
                    for (var i = 0; i < this.directoryContent.photos.length; i++ ){
                        var oldPhoto = this.directoryContent.photos[i];

                        for( var j = 0; j <newPhotos.length; j++){
                            var newPhoto = newPhotos[j];

                            if(oldPhoto.fileName == newPhoto.fileName && oldPhoto.path == newPhoto.path){
                                newPhotos.splice(j,1);
                             }
                        }
                    }


                    for( var i = 0; i < this.directoryContent.directories.length; i++ ){
                        var oldDir = this.directoryContent.directories[i];

                        for( var j = 0; j <newDirectories.length; j++){
                            var newDir = newDirectories[j];

                            if(oldDir.directoryName == newDir.directoryName && oldDir.path == newDir.path){
                                newDirectories.splice(j,1);
                            }
                        }
                    }

                }
            }

            this.directoryContent = directoryContent;

            this.showPath(directoryContent.currentPath);
            directoryRenderer.showDirectories(newDirectories);
            photoRenderer.showImages(newPhotos);
        }

        this.showSearchedText = function (text) {
            $directoryPathOl.empty();
            $directoryPathOl.append($("<li>").html("Showing search results for: " + text));
        };
        /**
         * Show the given path at the path row
         * @param path
         */
        this.showPath = function(path){
            $directoryPathOl.empty();

            var dirs = path.split("/");
            //removing empty strings
            for(var i = 0; i < dirs.length; i++){
                if(!dirs[i]  || 0 === dirs[i].length){
                    dirs.splice(i,1);
                    i--;
                }
            }
            var actualPath = "";

            var dirClickHandler = function(event){
                event.preventDefault();
                var path = $(event.target).closest("a").data("path"),
                    url = $(event.target).closest("a").attr('href');
                that.changeContent(path,url);
                return false;

            }

            /*Show alias for root directory*/
            var $li = null;
            if(0 == dirs.length ){ //is it the root directory?
                $li = $("<li>").html("Images");
            }else{
                $li = $("<li>").append(
                    $("<a>",{href: "index.php?dir=/", "data-path": "/"})
                        .html("Images")
                        .click(dirClickHandler));

            }

            $directoryPathOl.append($li);

            for(var i = 0; i < dirs.length; i++){
                actualPath += dirs[i]+"/";
                if(i == dirs.length - 1 ){//is it the current directory?
                    $li = $("<li>").html(dirs[i]);
                }else{ //add link to parent directories
                    $li = $("<li>").append(
                        $("<a>",{href: "index.php?dir="+actualPath, "data-path": actualPath})
                            .html(dirs[i])
                            .click(dirClickHandler)
                    );
                }

                //show il
                $directoryPathOl.append($li);
            }


        }

    };
});


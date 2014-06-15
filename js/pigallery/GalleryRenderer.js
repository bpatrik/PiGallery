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
            contentManager.getSearchResult(path, that);
            saveHistory(url);
        };

        /*-------History handler-----------*/
        window.onpopstate = function(event){
            var path = event.state ? event.state.path : "/";
            that.showContent(contentManager.getContent(path,that));
        };


        this.showSearchResult = function (searchContent) {
            PiGallery.hideMessages();

            $directoryGalleryDiv.empty();
            $photoGalleryDiv.empty();
            thumbnailManager.clearQueue();

            this.showSearchedText(searchContent.searchString);
            directoryRenderer.showDirectories(searchContent.directories);
            photoRenderer.showImages(searchContent.photos);

            if(searchContent.tooMuchResults == true){
                console.log(PiGallery);
                console.log(PiGallery.showInfoMessage);
                PiGallery.showInfoMessage(PiGallery.LANG.tooMuchResults);
            }
        };



        /**
         * Show the given content
         * If the directory not changed, updates the content
         * @param directoryContent
         */
        this.showContent = function (directoryContent) {
            PiGallery.hideMessages();

            var newPhotos = directoryContent.photos,
                newDirectories = directoryContent.directories;

            //check directory change
            if (this.directoryContent) {
                if (this.directoryContent.currentPath !== directoryContent.currentPath) {//directory changed, empty gallery
                    $directoryGalleryDiv.empty();
                    $photoGalleryDiv.empty();
                    thumbnailManager.clearQueue();
                } else { //dir name remained
                    var i,j;

                    /*cloning array is needed*/
                    newPhotos = directoryContent.photos.slice();
                    newDirectories = directoryContent.directories.slice();

                    //filter already shown photos and dirs
                    for (i = 0; i < this.directoryContent.photos.length; i++ ){
                        var oldPhoto = this.directoryContent.photos[i];

                        for( j = 0; j <newPhotos.length; j++){
                            var newPhoto = newPhotos[j];

                            if(oldPhoto.fileName == newPhoto.fileName && oldPhoto.path == newPhoto.path){
                                newPhotos.splice(j,1);
                                break;
                             }
                        }
                    }


                    for( i = 0; i < this.directoryContent.directories.length; i++ ){
                        var oldDir = this.directoryContent.directories[i];

                        for( j = 0; j <newDirectories.length; j++){
                            var newDir = newDirectories[j];

                            if(oldDir.directoryName == newDir.directoryName && oldDir.path == newDir.path){
                                newDirectories.splice(j,1);
                                break;
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
            $directoryPathOl.append($("<li>").html(PiGallery.LANG.searchingfor +" "+ text));
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
            var actualPath = "/";

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
                $li = $("<li>").html(PiGallery.LANG.images);
            }else{
                $li = $("<li>").append(
                    $("<a>",{href: "index.php?dir=/", "data-path": "/"})
                        .html(PiGallery.LANG.images)
                        .click(dirClickHandler));

            }

            $directoryPathOl.append($li);

            for(var i = 0; i < dirs.length; i++){
                actualPath += dirs[i] ;
                if(i == dirs.length - 1 ){//is it the current directory?
                    $li = $("<li>").html(dirs[i]);
                }else{ //add link to parent directories
                    $li = $("<li>").append(
                        $("<a>",{href: "index.php?dir="+actualPath, "data-path": actualPath})
                            .html(dirs[i])
                            .click(dirClickHandler)
                    );
                }
                actualPath += "/";

                //show il
                $directoryPathOl.append($li);
            }


        }

    };
});


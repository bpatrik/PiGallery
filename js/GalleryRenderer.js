define(["jquery","knockout", "ThumbnailManager"], function($, ko, ThumbnailManager) {
    "use strict";
    return function GalleryRenderer(directoryPathOl, galleryDiv, ContentManager){

        /*Image gallery*/
        var TARGET_COL_COUNT = 3;

        var MIN_ROW_COUNT = 2;
        var MAX_ROW_COUNT = 4;
        /*directory gallery*/
        var TARGET_DIR_COL_COUNT = 5;

        var IMAGE_MARGIN = 5;


        var $galleryDiv = $(galleryDiv);
        var $directoryGalleryDiv = $galleryDiv.find("#directory-gallery");
        var $photoGalleryDiv = $galleryDiv.find("#photo-gallery");
        var $directoryPathOl = $(directoryPathOl);

        var that = this;

        var directoryContent = null;
        var contentManager = ContentManager;
        var thumbnailManager  = new ThumbnailManager();


        // View-model for the KnockoutJS data binding.
        var koViewModel = {
            photos: ko.observableArray(),
            getThumbnailUrl: function(img, photo, imageWidth, imageHeight){
               return thumbnailManager.createThumbnailURL(img,  photo, imageWidth, imageHeight);
            },
            directories: ko.observableArray()
        };
        ko.applyBindings(koViewModel);



        this.showSearchResult = function(searchContent){

            $directoryGalleryDiv.empty();
            $photoGalleryDiv.empty();

            this.showSearchedText(searchContent.searchString);
            this.showDirectories(searchContent.directories);
            this.showImages(searchContent.photos);
        }

        this.showSearchedText = function(text){
            $directoryPathOl.empty();
            $directoryPathOl.append($("<li>").html("Showing results for: " + text));
        }

        /**
         * Show the given conent
         * If the directory not changed, updates the content
         * @param directoryContent
         */
        this.showContent = function(directoryContent){

            var newPhotos = directoryContent.photos;
            var newDirectories = directoryContent.directories;

            //check directory change
            if (this.directoryContent) {
                if(this.directoryContent.currentPath != directoryContent.currentPath){//directory changed, empty gallery
                    $directoryGalleryDiv.empty();
                    $photoGalleryDiv.empty();
                }else{ //dir name remained

                    //filter already shown photos and dirs
                    for( var i = 0; i < this.directoryContent.photos.length; i++ ){
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
            this.showDirectories(newDirectories);
            this.showImages(newPhotos);
        }

        /**
         * Show the given path at the path row
         * @param path
         */
        this.showPath = function(path){
            $directoryPathOl.empty();

            var dirs = path.split("/");
            //removing empty strings
            for(var i = 0; i < dirs.length; i++){
                if(!dirs[i]  || 0 === dirs[i].length)
                    dirs.splice(i,1);
            }



            var actualPath = "";
            for(var i = 0; i < dirs.length; i++){
                actualPath += dirs[i]+"/";

                var dirClickHandler = function(event){
                    event.preventDefault();
                    var path = $(event.target).closest("a").data("path");
                    that.showContent(contentManager.getContent(path,that));

                }

                var $li = null;
                if(i == dirs.length - 1 ){//is it the current directory?
                    $li = $("<li>").html(dirs[i]);
                }else{ //add link to parent directories
                    $li = $("<li>").append(
                        $("<a>",{href: "?dir="+actualPath, "data-path": actualPath})
                            .html(dirs[i])
                            .click(dirClickHandler)
                    );
                }

                //show is
                $directoryPathOl.append($li);
            }


        }

        this.showDirectories = function(directories){

            var rowHeight = ($directoryGalleryDiv.parent().width() -(IMAGE_MARGIN*2*TARGET_DIR_COL_COUNT)) / ((TARGET_DIR_COL_COUNT) ); //TODO: make phone friendly

            for(var i = 0; i < directories.length; i++){

                var directory = directories[i];
                var side = directory.samplePhotos[0].width < directory.samplePhotos[0].height ? "width" : "height";

                var dirClickHandler = function(data, event){
                    event.preventDefault();
                    var path = $(event.target).closest("a").data("path");
                    that.showContent(contentManager.getContent(path,that));

                }
                directory.mousemoveHandler = function(data, event ) {
                    if( Date.now() - that.lastUpdate < 500)
                        return;
                    that.lastUpdate = Date.now();
                    data.samplePhotoId++;
                    if(data.samplePhotoId >= data.samplePhotos.length)
                        data.samplePhotoId = 0 ;

                    data.samplePhoto(data.samplePhotos[data.samplePhotoId]);

                    if(data.samplePhotos[data.samplePhotoId].width < data.samplePhotos[data.samplePhotoId].height){
                        data.height("auto");
                        data.width("100%");
                    }else{
                        data.height("100%");
                        data.width("auto");
                    }
                   // ko.mapping.fromJS(data.samplePhotos[data.samplePhotoId], data.samplePhoto );

    /*
                    var $targetDiv = $(event.target).closest(".gallery-directory-image");
                    var $img = $targetDiv.find('img');

                    var directory = directories[$targetDiv.data("directoryId")];
                    var dirCounter = $targetDiv.data("dirCounter");
                    dirCounter++;
                    if(dirCounter >= directory.samplePhotos.length)
                        dirCounter = 0;
                    $targetDiv.data("dirCounter",dirCounter);

                    var side = directory.samplePhotos[dirCounter].width < directory.samplePhotos[dirCounter].height ? "width" : "height";
                    var src =  directory.samplePhotos[dirCounter].path + directory.samplePhotos[dirCounter].fileName;
                    $img.css("width","auto").css("height","auto").css(side,"100%").attr("src",src);*/
                };

                directory.renderSize = rowHeight;
                directory.clickHandler = dirClickHandler;
            //    directory.samplePhoto = ko.mapping.fromJS( directory.samplePhotos[0]);
                directory.samplePhoto = ko.observable( directory.samplePhotos[0]);
                directory.samplePhotoId = 0;
                if(directory.samplePhotos[0].width < directory.samplePhotos[0].height){
                    directory.height = ko.observable("auto");
                    directory.width = ko.observable("100%");
                }else{
                    directory.height =ko.observable( "100%");
                    directory.width = ko.observable("auto");
                }
              //  koViewModel.directories.push(ko.mapping.fromJS(directory));
                koViewModel.directories.push(directory);

         /*       var $imgDiv = $('<div>').append(
                    $('<a>' ,{href:"?dir="+ directory.path+directory.directoryName+"/", title: directory.samplePhotos[0].fileName , "data-path": directory.path+directory.directoryName+"/"}).append(
                        $('<img>', {id: directory.samplePhotos[0].fileName, src: directory.samplePhotos[0].path + directory.samplePhotos[0].fileName}).css(side,"100%")
                    ).click(dirClickHandler)
                ).addClass("gallery-directory-image").height(rowHeight).width(rowHeight).data("dirCounter","0").data("directoryId",i);


                //update picture on mouse move
                var lastUpdate = 0;
                $imgDiv.mousemove(function( event ) {
                    if( Date.now() -lastUpdate < 500)
                        return;
                    lastUpdate = Date.now();

                    var $targetDiv = $(event.target).closest(".gallery-directory-image");
                    var $img = $targetDiv.find('img');

                    var directory = directories[$targetDiv.data("directoryId")];
                    var dirCounter = $targetDiv.data("dirCounter");
                    dirCounter++;
                    if(dirCounter >= directory.samplePhotos.length)
                        dirCounter = 0;
                    $targetDiv.data("dirCounter",dirCounter);

                    var side = directory.samplePhotos[dirCounter].width < directory.samplePhotos[dirCounter].height ? "width" : "height";
                    var src =  directory.samplePhotos[dirCounter].path + directory.samplePhotos[dirCounter].fileName;
                    $img.css("width","auto").css("height","auto").css(side,"100%").attr("src",src);
                });

                $directoryGalleryDiv.append(
                    $('<div>').append(
                            $imgDiv,
                            $('<div>').addClass("gallery-directory-description")
                        ).addClass("gallery-directory-wrapper")
                        .height(rowHeight).width(rowHeight)
                );*/
            }
        }


        var calcPhotoRowHeight = function(photoRow){
            var width = 0;
            for(var i = 0; i < photoRow.length; i++){
                width += ((photoRow[i].width) / (photoRow[i].height)); //summing up aspect ratios
            }
            var height = ($photoGalleryDiv.width() - photoRow.length * (IMAGE_MARGIN * 2) - 1) / width; //cant be equal -> width-1
            return  height +(IMAGE_MARGIN * 2);
        }


        this.showImages = function(photos){

            var minRowHeight = $photoGalleryDiv.parent().height() / MAX_ROW_COUNT; //TODO make phone friendly
            var maxRowHeight = $photoGalleryDiv.parent().height() / MIN_ROW_COUNT;

            for (var i=0 ; i < photos.length; i++) {

                //get the next 3 photos
                var photoRow = [photos[i]];

                for(var j = 0; j < TARGET_COL_COUNT - 1; j++){
                    i++;
                    if(i  >= photos.length){
                        break;
                    }
                    photoRow.push(photos[i]);
                }

                while(calcPhotoRowHeight(photoRow) > maxRowHeight){ //row too high -> add more images
                    if(i+1  >= photos.length){
                        break;
                    }
                    i++;
                    photoRow.push(photos[i]);
                }
                while(calcPhotoRowHeight(photoRow) < minRowHeight){ //roo too small -> remove images
                    if(photoRow.length == 1)
                        break;
                    if(i-1  < 0){
                        break;
                    }
                    i--;
                    photoRow.pop();
                }

                var rowHeight = calcPhotoRowHeight(photoRow) ;

                if(rowHeight > maxRowHeight) rowHeight = maxRowHeight;
                if(rowHeight < minRowHeight) rowHeight = minRowHeight;

                var imageHeight = rowHeight - (IMAGE_MARGIN * 2);

                var keywordClickHandler = function(data, event){
                    event.preventDefault();
                    event.stopPropagation();
                    contentManager.getSearchResult(data,that);
                }

                for(var j = 0; j < photoRow.length; j++){
                    var photo = photoRow[j];
                    var imageWidth = imageHeight * (photo.width / photo.height);

                    photo.renderHeight = imageHeight;
                    photo.renderWidth = imageWidth;
                    photo.keywordClickHandler = keywordClickHandler;

                    koViewModel.photos.push(photo);
                }

                //add images to div
           /*     for(var j = 0; j < photoRow.length; j++){

                    var photo = photoRow[j];
                    var $keywordsDiv = $('<div>').addClass("galley-image-keywords");
                    for(keyword in photo.keywords){
                        $keywordsDiv.append(
                             $('<a>', {href: "#"}).html("#" + photo.keywords[keyword]),
                             ", "
                        );
                    }

                    var imageWidth = imageHeight * (photo.width / photo.height);
                    //add image to div
                    $photoGalleryDiv.append(
                            $('<div>').append(
                                $('<a>' ,{href:photo.path + photo.fileName, title: photo.fileName, "data-galxlery":""}).append(
                                   thumbnailManager.createThumbnail(photo,imageWidth, imageHeight)
                                ),
                                $('<div>').append($keywordsDiv).addClass("gallery-image-description")
                        ).addClass("gallery-image")
                                .height(imageHeight)
                                .css("max-width",imageWidth)
                                .css("display","none")
                    );
                }
    */

            }

            $photoGalleryDiv.append($("<br/>"));
            //Fading in photos
            var delayTime = 0;
            $photoGalleryDiv.children("div:hidden").each(function(){
                $(this).delay( delayTime ).fadeIn();
               delayTime+=50;
             });

        }

    };
});


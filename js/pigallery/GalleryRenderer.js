define(["jquery",  "PiGallery/ThumbnailManager", "underscore" ], function ($, ThumbnailManager, _) {
    "use strict";
    return function GalleryRenderer(directoryPathOl, galleryDiv, ContentManager){

        /*Image gallery*/
        var TARGET_COL_COUNT = 3,
            MIN_ROW_COUNT = 2,
            MAX_ROW_COUNT = 4,

        /*directory gallery*/
            TARGET_DIR_COL_COUNT = 5,
            IMAGE_MARGIN = 5,
            IMAGE_DESCRIPTION_PADDING = 5,

            $galleryDiv = $(galleryDiv),
            $directoryGalleryDiv = $galleryDiv.find("#directory-gallery"),
            $photoGalleryDiv = $galleryDiv.find("#photo-gallery"),
            $directoryPathOl = $(directoryPathOl),

            that = this,

            directoryContent = null,
            contentManager = ContentManager,
            thumbnailManager  = new ThumbnailManager();

            window.onpopstate = function(event){
                var path = event.state ? event.state.path : "/";
                that.showContent(contentManager.getContent(path,that));
            }

        var saveHistory = function(url){
            if(history.pushState && history.replaceState) {
                window.history.pushState({path: that.directoryContent.currentPath }, document.title, url);
            }
        }

        this.showSearchResult = function (searchContent) {

            $directoryGalleryDiv.empty();
            $photoGalleryDiv.empty();

            this.showSearchedText(searchContent.searchString);
            this.showDirectories(searchContent.directories);
            this.showImages(searchContent.photos);
        };

        this.showSearchedText = function (text) {
            $directoryPathOl.empty();
            $directoryPathOl.append($("<li>").html("Showing search results for: " + text));
        };

        /**
         * Show the given conent
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
                if(!dirs[i]  || 0 === dirs[i].length){
                    dirs.splice(i,1);
                    i--;
                }
            }
            var actualPath = "";

            var dirClickHandler = function(event){
                event.preventDefault();
                var path = $(event.target).closest("a").data("path");
                that.showContent(contentManager.getContent(path,that));
                saveHistory( $(event.target).closest("a").attr('href'));
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

        this.showDirectories = function(directories){

            var rowHeight = ($directoryGalleryDiv.parent().width() -(IMAGE_MARGIN*2*TARGET_DIR_COL_COUNT)) / ((TARGET_DIR_COL_COUNT) ); //TODO: make phone friendly



            var getScaledHeight = function (width, height, scaledWidth){
                return height * (scaledWidth / width);
            }
            var getScaledWidth = function (width, height, scaledHeight){
                return width * (scaledHeight / height);
            }


            for(var i = 0; i < directories.length; i++){

                var directory = directories[i];


              var calcPhotoDimension = function(photo){
                  var width, height;
                  if(photo.width < photo.height){
                      height = getScaledHeight(directory.samplePhotos[0].width, directory.samplePhotos[0].height, rowHeight);
                      width = rowHeight;
                  }else{
                      height = rowHeight;
                      width = getScaledWidth(directory.samplePhotos[0].width, directory.samplePhotos[0].height, rowHeight);
                  }
                  return {height: height, width:width};
              }

              var directoryClickHandler = function ( event) {
                    var path = $(event.target).closest("a").data("path");
                    that.showContent(contentManager.getContent(path, that));
                    saveHistory( $(event.target).closest("a").attr('href'));
                    return false;
               };
                //update picture on mouse move
               var mouseMoveHandler = function( event ) {
                   var $targetDiv = $(event.target).closest(".gallery-directory-image");
                   var lastUpdate = $targetDiv.data("lastUpdate");

                    if( Date.now() -lastUpdate < 500)
                        return;
                    lastUpdate = Date.now();

                    var $img = $targetDiv.find('img');

                    var directory = directories[$targetDiv.data("directoryId")];
                    var dirCounter = $targetDiv.data("dirCounter");
                    dirCounter++;
                    if(dirCounter >= directory.samplePhotos.length)
                        dirCounter = 0;
                    $targetDiv.data("dirCounter",dirCounter);

                      $img.fadeOut(400, function () {
                          var dimension = calcPhotoDimension(directory.samplePhotos[dirCounter]);
                          thumbnailManager.loadThumbnailToDiv($img , directory.samplePhotos[dirCounter],dimension.width, dimension.height);
                          $img.fadeIn();
                       });
                };

             var $samplePhoto = null;

              if(directory.samplePhotos.length > 0){
                  var dimension = calcPhotoDimension(directory.samplePhotos[0]);
                  $samplePhoto = thumbnailManager.createThumbnail(directory.samplePhotos[0],dimension.width, dimension.height);
                  $samplePhoto.mousemove(mouseMoveHandler);
               }else{
                  $samplePhoto = $('<img>', {src: 'img/gallery-icon.jpg'}).width('100%');
              }


             var $imgDiv = $('<div>').append(
                 $('<a>' ,{href:"index.php?dir="+ directory.path+directory.directoryName+"/", title: directory.directoryName , "data-path": directory.path+directory.directoryName+"/"}).append(
                 $samplePhoto
                 ).click(directoryClickHandler)
             ).addClass("gallery-directory-image").height(rowHeight).width(rowHeight).data("dirCounter","0").data("directoryId",i).data("lastUpdate",Date.now());


             $directoryGalleryDiv.append(
                 $('<div>').append(
                     $imgDiv,
                     $('<div>').append(
                         $('<span>').html(directory.directoryName).addClass('pull-left')
                     ).addClass("gallery-directory-description")
                 ).addClass("gallery-directory-wrapper")
                 .height(rowHeight).width(rowHeight)
             );
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
            var minRowHeight = $photoGalleryDiv.parent().parent().parent().height() / MAX_ROW_COUNT; //TODO make phone friendly
            var maxRowHeight = $photoGalleryDiv.parent().parent().parent().height() / MIN_ROW_COUNT;

            var keywordClickHandler= function (event) {
                event.preventDefault();
                event.stopPropagation();
                contentManager.getSearchResult($(event.target).closest('a').data("keyword"), that);
            };

            var  imageMouseOverHandler= function ( event) {
                var $imgDiv = $(event.target).closest(".gallery-image"),
                    $descDiv = $imgDiv.find(".gallery-image-description"),
                    $keywordsDiv = $descDiv.find(".galley-image-keywords"),
                    $filenameDiv = $descDiv.find("span"),
                    height = $keywordsDiv.height() + $filenameDiv.height() + IMAGE_DESCRIPTION_PADDING * 2;

                $descDiv.css({height: height, "bottom": height + "px"});

            };
            var imageMouseOutHandler= function (event) {
                var $imgDiv = $(event.target).closest(".gallery-image");
                var $descDiv = $imgDiv.find(".gallery-image-description");
                var $filenameDiv = $descDiv.find("span");
                var height =   $filenameDiv.height() + IMAGE_DESCRIPTION_PADDING * 2;
                $descDiv.css({height: height, "bottom": height + "px"});
            };


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



                //add images to div
                 for(var j = 0; j < photoRow.length; j++){
                     var photo = photoRow[j];

                     /*rendering keywords*/
                     var $keywordsDiv = $('<div>').addClass("galley-image-keywords");

                     if(PiGallery.searchSupported){
                         _.each(photo.keywords, function(keyword){
                             $keywordsDiv.append(
                             $('<a>', {href: "#"}).html("#" + keyword).click(keywordClickHandler),", ");
                         });
                     }else{
                         _.each(photo.keywords, function(keyword){
                             $keywordsDiv.append(
                                 $('<span>').html("#" + keyword),", ");
                         });
                     }

                     /*rednering imgae description div*/
                     var $imageDescriptioDiv =  $('<div>').append(
                                                  $keywordsDiv,
                                                  $("<span>").html(photo.fileName).addClass("pull-left").addClass("image-name")
                                                ).addClass("gallery-image-description");


                     var imageWidth = imageHeight * (photo.width / photo.height);
                     //add image to div
                     $photoGalleryDiv.append(
                         $('<div>').append(
                                 $('<a>' ,{href:"image.php?path="+ photo.path + photo.fileName, title: photo.fileName, "data-galxlery":""}).append(
                                     thumbnailManager.createThumbnail(photo,imageWidth, imageHeight)
                                 ),
                                 $imageDescriptioDiv
                             )
                             .addClass("gallery-image")
                             .height(imageHeight)
                             .width(imageWidth)
                             .css("display","none")
                             .mouseover(imageMouseOverHandler)
                             .mouseout(imageMouseOutHandler)
                         );
                 }

                //Fading in photos
                var delayTime = 0;
                $photoGalleryDiv.children("div:hidden").each(function(){
                    $(this).delay( delayTime ).fadeIn();
                    delayTime+=50;
                });


            }



        }

    };
});


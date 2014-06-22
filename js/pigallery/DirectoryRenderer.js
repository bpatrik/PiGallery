define(["jquery",  "underscore", "PiGallery/ThumbnailManager", "detectmobilebrowser_jquery" ], function ($,   _) {
    "use strict";
    return function DirectoryRenderer($directoryGalleryDiv, thumbnailManager, galleryRenderer) {


        /*Variables*/

        /*directory gallery*/
        var TARGET_DIR_COL_COUNT = 5,
            IMAGE_MARGIN = 5;

        var that = this;
        var imageSize = ($directoryGalleryDiv.parent().width() - (IMAGE_MARGIN * 2 * TARGET_DIR_COL_COUNT)) / ((TARGET_DIR_COL_COUNT) ); //TODO: make phone friendly



        /*-----------Helper functions----------------*/
        var getScaledHeight = function (width, height, scaledWidth) {
            return height * (scaledWidth / width);
        };
        var getScaledWidth = function (width, height, scaledHeight) {
            return width * (scaledHeight / height);
        };

        var calcPhotoDimension = function (photo) {
            var width, height;
            if (photo.width < photo.height) {
                height = getScaledHeight(photo.width, photo.height, imageSize);
                width = imageSize;
            } else {
                height = imageSize;
                width = getScaledWidth(photo.width, photo.height, imageSize);
            }
            return {height: height, width: width};
        };

        var calcDirectoryImageSize = function(){
            var screenHeight = $directoryGalleryDiv.parent().parent().parent().height(),
                screenWidth =  $directoryGalleryDiv.parent().width(),
                smallerSide = screenHeight < screenWidth ? screenHeight : screenWidth;

            var maxSize = smallerSide - IMAGE_MARGIN * 2;
            var colCount = 1;
            if($.browser.mobile){
                if(screenWidth < 768){ //in case of phones
                    colCount = 1;
                }else if(screenWidth < 992){
                    colCount = 2;
                }else if(screenWidth < 1200){
                    colCount = 3;
                }else{
                    colCount = 4;
                }
            }else {
                if(screenWidth < 768){ //in case of phones
                    colCount = 2;
                }else if(screenWidth < 992){
                    colCount = 4;
                }else if(screenWidth < 1400){
                    colCount = 5;
                }else{
                    colCount = 6;
                }
            }

            var size = (screenWidth - (IMAGE_MARGIN * 2 * colCount)) / ((colCount) );

            if(size > maxSize){
                size = maxSize;
            }

            return size;


        };
        /*-----------Event Handlers--------------*/

        var directoryClickHandler = function (event) {
            var path = $(event.target).closest("a").data("path"),
                url = $(event.target).closest("a").attr('href');
            galleryRenderer.changeContent(path, url);
            return false;
        };



        this.showDirectories = function (directories) {
            imageSize = calcDirectoryImageSize();

            //sort directories
            directories.sort(function(a, b){
                if ( a.directoryName.toLowerCase() < b.directoryName.toLowerCase() )
                    return -1;
                if ( a.directoryName.toLowerCase() > b.directoryName.toLowerCase() )
                    return 1;
                return 0;
            });

            //update picture on mouse move
            var mouseMoveHandler = function (event) {
                var $targetDiv = $(event.target).closest(".gallery-directory-image");
                var lastUpdate = $targetDiv.data("lastUpdate");

                if (Date.now() - lastUpdate < 500)
                    return;
                $targetDiv.data("lastUpdate",Date.now());

                var $img = $targetDiv.find('img');

                var directory = directories[$targetDiv.data("directoryId")];
                var dirCounter = $targetDiv.data("dirCounter");
                dirCounter++;
                if (dirCounter >= directory.samplePhotos.length)
                    dirCounter = 0;
                $targetDiv.data("dirCounter", dirCounter);

                $img.fadeOut(400, function () {
                    var dimension = calcPhotoDimension(directory.samplePhotos[dirCounter]);
                    thumbnailManager.loadThumbnailToDiv($img, directory.samplePhotos[dirCounter], dimension.width, dimension.height);
                    $img.fadeIn();
                });
            };

            for (var i = 0; i < directories.length; i++) {

                var directory = directories[i];

                //Rendering sample photo
                var $samplePhoto = null;

                if (directory.samplePhotos.length > 0) {
                    var dimension = calcPhotoDimension(directory.samplePhotos[0]);
                    $samplePhoto = thumbnailManager.createThumbnail(directory.samplePhotos[0], dimension.width, dimension.height);
                    $samplePhoto.mousemove(mouseMoveHandler);
                } else {
                    //randomizing no-image gallery icon
                    var galleyIconID = (((directory.directoryName.charCodeAt(0) + directory.directoryName.charCodeAt(directory.directoryName.length - 1) + directory.directoryName.length) % 4) + 1);
                    $samplePhoto = $('<img>', {src: 'img/gallery-icon_' + galleyIconID + '.jpg'}).width('100%');
                }

                //Rendering image Div
                var $imgDiv = $('<div>').append(
                    $('<a>', {href: "index.php?dir=" + (directory.path == "/" ? "" : directory.path) + "/"  + directory.directoryName, title: directory.directoryName, "data-path": (directory.path == "/" ? "" : directory.path) + "/" + directory.directoryName}).append(
                        $samplePhoto
                    ).click(directoryClickHandler)
                ).addClass("gallery-directory-image").height(imageSize).width(imageSize).data("dirCounter", "0").data("directoryId", i).data("lastUpdate", Date.now());

                //Appending to DOM
                $directoryGalleryDiv.append(
                    $('<div>').append(
                        $imgDiv,
                        $('<div>').append(
                            $('<span>').html(directory.directoryName).addClass('pull-left')
                        ).addClass("gallery-directory-description")
                    ).addClass("gallery-directory-wrapper")
                        .height(imageSize).width(imageSize)
                );
            }
        }
    }
});
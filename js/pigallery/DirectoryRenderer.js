define(["jquery",  "underscore", "PiGallery/ThumbnailManager" ], function ($,   _) {
    "use strict";
    return function DirectoryRenderer($directoryGalleryDiv, thumbnailManager, galleryRenderer) {


        /*Variables*/

        /*directory gallery*/
        var TARGET_DIR_COL_COUNT = 5,
            IMAGE_MARGIN = 5;

        var that = this;
        var rowHeight = ($directoryGalleryDiv.parent().width() - (IMAGE_MARGIN * 2 * TARGET_DIR_COL_COUNT)) / ((TARGET_DIR_COL_COUNT) ); //TODO: make phone friendly



        /*-----------Helper functions----------------*/
        var getScaledHeight = function (width, height, scaledWidth) {
            return height * (scaledWidth / width);
        }
        var getScaledWidth = function (width, height, scaledHeight) {
            return width * (scaledHeight / height);
        }

        var calcPhotoDimension = function (photo) {
            var width, height;
            if (photo.width < photo.height) {
                height = getScaledHeight(photo.width, photo.height, rowHeight);
                width = rowHeight;
            } else {
                height = rowHeight;
                width = getScaledWidth(photo.width, photo.height, rowHeight);
            }
            return {height: height, width: width};
        }
        /*-----------Event Handlers--------------*/

        var directoryClickHandler = function (event) {
            var path = $(event.target).closest("a").data("path"),
                url = $(event.target).closest("a").attr('href');
            galleryRenderer.changeContent(path, url);
            return false;
        };



        this.showDirectories = function (directories) {

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

                //Rendering smaple photo
                var $samplePhoto = null;

                if (directory.samplePhotos.length > 0) {
                    var dimension = calcPhotoDimension(directory.samplePhotos[0]);
                    $samplePhoto = thumbnailManager.createThumbnail(directory.samplePhotos[0], dimension.width, dimension.height);
                    $samplePhoto.mousemove(mouseMoveHandler);
                } else {
                    $samplePhoto = $('<img>', {src: 'img/gallery-icon.jpg'}).width('100%');
                }

                //Rendering image Div
                var $imgDiv = $('<div>').append(
                    $('<a>', {href: "index.php?dir=" + directory.path + directory.directoryName + "/", title: directory.directoryName, "data-path": directory.path + directory.directoryName + "/"}).append(
                        $samplePhoto
                    ).click(directoryClickHandler)
                ).addClass("gallery-directory-image").height(rowHeight).width(rowHeight).data("dirCounter", "0").data("directoryId", i).data("lastUpdate", Date.now());

                //Appending to DOM
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
    }
});
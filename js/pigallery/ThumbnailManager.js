
define(["jquery"], function ($) {
    "use strict";

    return function ThumbnailManager() {


        var thumbnailQueue = [];
        var loadingInProgress = false;

        this.createThumbnailURL = function (img, photo, width, height) {
            //find the best size
            var foundThumbnailInfo = photo.availableThumbnails[0];
            for(var i = 0; i < photo.availableThumbnails.length; i++){
                var thumbnailInfo = photo.availableThumbnails[i];
                if(thumbnailInfo.size * thumbnailInfo.size >= width* height){
                    foundThumbnailInfo = thumbnailInfo;
                    break;
                }
            }

            var thumbnailPath = "thumbnail.php?image=" + photo.path + photo.fileName + "&size=" + foundThumbnailInfo.size;
            var $img = $(img);

            if(foundThumbnailInfo.available == true){
                return thumbnailPath;
            }else{
                queuUpThumbanil($img, thumbnailPath);
                return "img/loading.gif";
             //   return $img;
            }
        }

        var queuUpThumbanil = function($image, thumbnailPath){
            thumbnailQueue.push({$image : $image , path: thumbnailPath});
            loadThumbnails();
        }

        var loadThumbnails = function(){
            if(loadingInProgress || thumbnailQueue.length == 0)
                return;

           loadingInProgress = true;
            var thumbnailInfo = thumbnailQueue.shift();
            var thumbnail = new Image();
            console.log("loading: " + thumbnailInfo.path);
            thumbnail.src = thumbnailInfo.path;
            thumbnail.onload = function() {
                console.log("ready: " + thumbnailInfo.path);

                thumbnailInfo.$image.parent().parent().fadeOut(400, function(){
                    thumbnailInfo.$image.attr("src", thumbnailInfo.path);
                    thumbnailInfo.$image.parent().parent().fadeIn();
                });
                loadingInProgress = false;
                loadThumbnails();
            }
            thumbnail.onerror = function(){
                console.log("error: " + thumbnailInfo.path);
                thumbnailInfo.$image.attr("src", "images/noPreview.png");
                loadingInProgress = false;
                loadThumbnails();
            }

        }

    };
});

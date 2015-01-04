
define(["jquery"], function ($) {
    "use strict";

    return function ThumbnailManager() {


        var thumbnailQueue = [];
        var loadingInProgress = false;

        var calcThumbnailSize = function(photo, width, height){
            var i, imgSize = width * height;
            //find the best size and an alternative if the best not ready yet

            photo.availableThumbnails.sort(function(a,b){
                return a.size - b.size;
            });

            var ThumbnailInfos = {
                best : null,
                available : null
                };

            //finding the smallest available
            for(i = 0; i < photo.availableThumbnails.length; i++) {
                if (photo.availableThumbnails[i].available == true) {
                    ThumbnailInfos.available = photo.availableThumbnails[i];
                    break;
                }
            }

            //finding the best one (the closese to the needed size)

            ThumbnailInfos.best = photo.availableThumbnails[0];
            for(i = 1; i < photo.availableThumbnails.length; i++) {
                if(Math.abs((photo.availableThumbnails[i].size * photo.availableThumbnails[i].size) - (imgSize)) <
                    Math.abs((ThumbnailInfos.best.size * ThumbnailInfos.best.size) - (imgSize))
                    )
                {
                    ThumbnailInfos.best = photo.availableThumbnails[i];
                }
            }


           return ThumbnailInfos;
        };

        this.createThumbnail = function(photo,width,height){

            //find the best size
            var ThumbnailInfos = calcThumbnailSize(photo,width,height);
            var thumbnailPath = "thumbnail.php?image=" + photo.path + "/" + photo.fileName + "&size=" + ThumbnailInfos.best.size  + (PiGallery.shareLink == null ? "" : ("&s="+PiGallery.shareLink));
            if(ThumbnailInfos.best.available == true){
                return $('<img>', { src: thumbnailPath, height: height, width: width});
            }else{
                var $img = null;
                //put an alternative thumbnail there if available
                if(ThumbnailInfos.available != null){
                    $img = $('<img>', { src: "thumbnail.php?image=" + photo.path + "/" + photo.fileName + "&size=" + ThumbnailInfos.available.size  + (PiGallery.shareLink == null ? "" : ("&s="+PiGallery.shareLink)),
                                        height: height, width: width});
                }else{
                    $img = $('<img>', { src: "img/loading.gif", height: height, width: width});
                }

                queuUpThumbnail($img, thumbnailPath);
                return $img;
            }
        };

        this.loadThumbnailToDiv = function($img,photo, width, height){

            //find the best size
            var ThumbnailInfos = calcThumbnailSize(photo,width,height);

            var thumbnailPath = "thumbnail.php?image=" + photo.path + "/" + photo.fileName + "&size=" + ThumbnailInfos.best.size + (PiGallery.shareLink == null ? "" : ("&s="+PiGallery.shareLink));
            $img.css({height: height, width: width});
            if(ThumbnailInfos.best.available == true){
                $img.attr("src", thumbnailPath);
            }else{
                if(ThumbnailInfos.available != null){
                    $img.attr("src", "thumbnail.php?image=" + photo.path + "/" + photo.fileName + "&size=" + ThumbnailInfos.available.size + (PiGallery.shareLink == null ? "" : ("&s="+PiGallery.shareLink)));
                }else{
                    $img.attr("src", "img/loading.gif");
                }
                queuUpThumbnail($img, thumbnailPath);
            }
        };
/*
        this.createThumbnailURL = function (imgID, photo, width, height, readyCallback) {
            console.log("get thumbnail " + "thumbnail.php?image=" + photo.path + photo.fileName);
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


            if(foundThumbnailInfo.available == true){
                return thumbnailPath;
            }else{
             //   queuUpThumbnail(imgID, thumbnailPath, readyCallback);
                return "img/loading.gif";
             //   return $img;
            }
        }
*/

        var queuUpThumbnail = function($image, thumbnailPath){
            thumbnailQueue.push({$image : $image , path: thumbnailPath});
            loadThumbnails();
        };

        var loadThumbnails = function(){
            if(loadingInProgress || thumbnailQueue.length == 0)
                return;

            loadingInProgress = true;
            var thumbnailInfo = thumbnailQueue.shift();
            var thumbnail = new Image();
            thumbnail.src = thumbnailInfo.path;
            thumbnail.onload = function() {
                thumbnailInfo.$image.parent().parent().fadeOut(400, function(){
                    thumbnailInfo.$image.attr("src", thumbnailInfo.path);
                    thumbnailInfo.$image.parent().parent().fadeIn();
                });
                loadingInProgress = false;
                loadThumbnails();
            };
            
            thumbnail.onerror = function(){
                console.log("error: " + thumbnailInfo.path);
                thumbnailInfo.$image.attr("src", "img/noPreview.png");
                loadingInProgress = false;
                loadThumbnails();
            }

        };

        this.clearQueue = function (){
            thumbnailQueue.length = 0;
        }


    };
});

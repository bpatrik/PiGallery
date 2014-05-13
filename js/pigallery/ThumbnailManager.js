
define(["jquery", "underscore"], function ($, _) {
    "use strict";

    return function ThumbnailManager() {


        var thumbnailQueue = [];
        var loadingInProgress = false;
        var that = this;

        var calcThumbanilSize = function(photo, width, height){

            //find the best size
            var foundThumbnailInfo = photo.availableThumbnails[0];
            for(var i = 0; i < photo.availableThumbnails.length; i++){
                var thumbnailInfo = photo.availableThumbnails[i];
                if(thumbnailInfo.size * thumbnailInfo.size >= width* height){
                    foundThumbnailInfo = thumbnailInfo;
                    break;
                }
            }
            return foundThumbnailInfo;
        }

        this.createThumbnail = function(photo,width,height){

            //find the best size
            var foundThumbnailInfo = calcThumbanilSize(photo,width,height);

            var thumbnailPath = "thumbnail.php?image=" + photo.path + photo.fileName + "&size=" + foundThumbnailInfo.size;
            if(foundThumbnailInfo.available == true){
                return $('<img>', { src: thumbnailPath, height: height, width: width});
            }else{
                var $img = $('<img>', { src: "img/loading.gif", height: height, width: width});
                queuUpThumbanil($img, thumbnailPath);
                return $img;
            }
        }

        this.loadThumbnailToDiv = function($img,photo, width, height){

            //find the best size
            var foundThumbnailInfo = calcThumbanilSize(photo,width,height);

            var thumbnailPath = "thumbnail.php?image=" + photo.path + photo.fileName + "&size=" + foundThumbnailInfo.size;
            $img.css({height: height, width: width});
            if(foundThumbnailInfo.available == true){
                $img.attr("src", thumbnailPath);
            }else{
                $img.attr("src", "img/loading.gif");
                queuUpThumbanil($img, thumbnailPath);
            }
        }

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
             //   queuUpThumbanil(imgID, thumbnailPath, readyCallback);
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
                thumbnailInfo.$image.attr("src", "img/noPreview.png");
                loadingInProgress = false;
                loadThumbnails();
            }

        }

        this.clearQueue = function (){
            thumbnailQueue.length = 0;
        }

/*
        var queuUpThumbanil = function(imageID, thumbnailPath, readyCallback){
            var contains = _.find(thumbnailQueue, function(data){ return data.thumbnailPath == thumbnailPath; }) || _.find(FinishedThumbnailQueue, function(data){ return data.thumbnailPath == thumbnailPath; });
            if(!contains){
                console.log("    queUp: " + thumbnailPath + " #"+thumbnailQueue.length);
                thumbnailQueue.push({imageID : imageID , path: thumbnailPath, readyCallback: readyCallback});
             //   loadThumbnails();
            }
        }

        this.loadThumbnails = function(){
            if(loadingInProgress || thumbnailQueue.length == 0)
                return;

            loadingInProgress = true;
            var thumbnailInfo = _.first(thumbnailQueue);
            var thumbnail = new Image();
            console.log("    loading: " + thumbnailInfo.path);
            thumbnail.src = thumbnailInfo.path;
            thumbnail.onload = function() {
                console.log("    ready: " + thumbnailInfo.path);
                var $image =  $('#'+thumbnailInfo.imageID);
                var $parent = null;
                if($image.parents('div.gallery-directory-wrapper').length){
                    $parent = $($image.parents('div.gallery-directory-wrapper')[0]);
                }else{
                    console.log($image.parents('div.gallery-image').length);
                     $parent =   $($image.parents('div.gallery-image')[0]);
                }
                console.log($image);
                console.log($parent);
                $parent.fadeOut(400, function(){
                    $image.attr("src", thumbnailInfo.path);
                    $parent.fadeIn();
                });
                loadingInProgress = false;
                thumbnailQueue.shift();
                FinishedThumbnailQueue.push(thumbnailInfo);
                that.loadThumbnails();
            }
            thumbnail.onerror = function(){
                console.log("    error: " + thumbnailInfo.path);
                thumbnailInfo.$image.attr("src", "img/noPreview.png");
                loadingInProgress = false;
                that.loadThumbnails();
            }

        }*/

    };
});

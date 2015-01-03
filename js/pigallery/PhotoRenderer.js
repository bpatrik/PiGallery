define(["jquery",  "underscore", "PiGallery/ThumbnailManager" ], function ($,   _) {
    "use strict";
    return function PhotoRenderer($photoGalleryDiv, thumbnailManager, galleryRenderer) {

        /*-------Variables, constants------*/
        /*Image gallery*/
        var TARGET_COL_COUNT = 3,
            MIN_ROW_COUNT = 2,
            MAX_ROW_COUNT = 4,

        /*directory gallery*/
            IMAGE_MARGIN = 5,
            IMAGE_DESCRIPTION_PADDING = 5;

        var minRowHeight = $photoGalleryDiv.parent().parent().parent().height() / MAX_ROW_COUNT; //TODO make phone friendly
        var maxRowHeight = $photoGalleryDiv.parent().parent().parent().height() / MIN_ROW_COUNT;



        this.showImages = function(photos){
            var i, j;
            //sort directories
            photos.sort(function(a, b){
                return  a.creationDate - b.creationDate;
            });

            for (i = 0 ; i < photos.length; i++) {
                //get the next 3 photos
                var photoRow = [photos[i]];

                for(j = 0; j < TARGET_COL_COUNT - 1; j++){
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
                for(j = 0; j < photoRow.length; j++){
                    var photo = photoRow[j];

                    /*rendering keywords*/
                    var $keywordsDiv = $('<div>').addClass("galley-image-keywords");

                    if(PiGallery.searchSupported && PiGallery.user.role > PiGallery.enums.Roles.RemoteGuest){
                        _.each(photo.keywords, function(keyword) {

                            if (keyword != "") {
                                $keywordsDiv.append(
                                    $('<a>', {href: "#", "data-keyword": keyword}).html("#" + keyword).click(keywordClickHandler), ", ");
                            }
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
                            $('<a>' ,{href:"image.php?path="+ photo.path + "/" + photo.fileName + (PiGallery.shareLink == null ? "" : ("&s="+PiGallery.shareLink)),
                                        title: photo.fileName,
                                        "data-galxlery":""}
                                ).append(
                                      thumbnailManager.createThumbnail(photo,imageWidth, imageHeight).data({"origWidth": photo.width, "origHeight": photo.height})
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



        };



        /*---------Helper functions------------*/

        var calcPhotoRowHeight = function(photoRow){
            var width = 0;
            for(var i = 0; i < photoRow.length; i++){
                width += ((photoRow[i].width) / (photoRow[i].height)); //summing up aspect ratios
            }
            var height = ($photoGalleryDiv.width() - photoRow.length * (IMAGE_MARGIN * 2) - 1) / width; //cant be equal -> width-1

            return  height +(IMAGE_MARGIN * 2);
        };

        /*-----------Event handlers-----------*/
        var keywordClickHandler = function (event) {
            var keyword = $(event.target).closest("a").data("keyword"),
                url = $(event.target).closest("a").attr('href');
            galleryRenderer.searchFor(keyword, url);
            return false;
        };

        var  imageMouseOverHandler = function ( event) {
            var $imgDiv = $(event.target).closest(".gallery-image"),
                $descDiv = $imgDiv.find(".gallery-image-description"),
                $keywordsDiv = $descDiv.find(".galley-image-keywords"),
                $filenameDiv = $descDiv.find("span"),
                height = $keywordsDiv.height() + $filenameDiv.height() + IMAGE_DESCRIPTION_PADDING * 2;

            $descDiv.css({height: height, "bottom": height + "px"});

        };
        var imageMouseOutHandler = function (event) {
            var $imgDiv = $(event.target).closest(".gallery-image");
            var $descDiv = $imgDiv.find(".gallery-image-description");
            var $filenameDiv = $descDiv.find("span");
            var height =   $filenameDiv.height() + IMAGE_DESCRIPTION_PADDING * 2;
            $descDiv.css({height: height, "bottom": height + "px"});
        };


    }
});
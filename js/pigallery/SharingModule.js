define(["jquery", 'zeroClipboard', 'bootstrapSlider' ], function ($,ZeroClipboard) {
    "use strict";
    return function SharingModule(galleryRenderer) {

        var $shareModal = $("#shareModal"),
            $shareButton = null;

        this.init = function() {

            if (PiGallery.Supported.Share === true) {
                
                //append button To Dom
                $("#menu.nav").prepend(
                    $("<li>").append(
                     $("<button>", {type: "button", "class":"btn btn-default navbar-btn btn-link", id: "shareButton"}).append(
                         $("<span>", {"class": "glyphicon glyphicon-share-alt"})," "+PiGallery.LANG.share
                         
                     )
                    )                    
                );
                $shareButton = $("#shareButton");

                /*Variable declarations*/
                var $shareSlider = $('#shareSlider'),
                    $copyButton = $("#copyButton"),                    
                    slider = $shareSlider.slider({tooltip: "hide"}),//initializing slider

                /*Helper function declarations*/
                    printSliderValue = function (value) {
                        if (value < 24) { //till 24 //hourly
                            $("#sliderText").html(value + " " + PiGallery.LANG.hours);
                        } else if (value - 24 < 29 + 15) { // till 24 + 30 + 15= 68 //daily
                            $("#sliderText").html((value - 23) + " " + PiGallery.LANG.days);
                        } else if (value - 68 < 21) { //half monthly
                            $("#sliderText").html((value - 65) / 2 + " " + PiGallery.LANG.months);
                        } else if (value - 91 < 17) { //half year
                            $("#sliderText").html((value - 87) / 2 + " " + PiGallery.LANG.years);
                        } else {
                            $("#sliderText").html(PiGallery.LANG.infinite);
                        }
                    },
                    getHoursFromSliderValue = function (value) {
                        if (value < 24) { //till 24 //hourly
                            return value;
                        } else if (value - 24 < 29 + 15) { // till 24 + 30 + 15= 68 //daily
                            return (value - 23) * 24;
                        } else if (value - 68 < 21) { //half monthly
                            return (value - 65) / 2 * 30 * 24;
                        } else if (value - 91 < 17) { //half year
                            return (value - 87) / 2 * 365 * 24;
                        } else {
                            return 365 * 24 * 200;
                        }
                    },
                    getSliderValueFromHours = function (hours) {
                        if (hours < 24) { //till 24 //hourly
                            return hours;
                        } else if ((hours / 24 + 23) - 24 < 29 + 15) { // till 24 + 30 + 15= 68 //daily
                            return (hours / 24 + 23);
                        } else if ((hours / (30 * 24) * 2 + 65) - 68 < 21) { //half monthly
                            return (hours / (30 * 24) * 2 + 65);
                        } else if ((hours / (365 * 24) * 2 + 87) - 91 < 17) { //half year
                            return (hours / (365 * 24) * 2 + 87);
                        } else {
                            return 108;
                        }
                    },
                    getShareLink = function () {
                        var path = galleryRenderer.directoryContent.currentPath,
                            isRecursive = $("#recursiveShareBox").is(":checked"),
                            validInterval = getHoursFromSliderValue(slider.slider('getValue'));
                        if (PiGallery.shareLink &&
                            PiGallery.shareLink.path == path &&
                            PiGallery.shareLink.isRecursive == isRecursive &&
                            PiGallery.shareLink.validInterval == validInterval) {//path didn't changed
                            $shareModal.modal('show');
                        } else {
                            var ajaxArray = {
                                method: "share",
                                dir: path,
                                isRecursive: isRecursive,
                                validInterval: validInterval
                            };
                            if (PiGallery.shareLink) {
                                ajaxArray.currentShareId = PiGallery.shareLink.shareId;

                            }


                            $("#loading-sign").css("opacity", 1);
                            $.ajax({
                                type: "POST",
                                url: "model/AJAXfacade.php",
                                data: ajaxArray,
                                dataType: "json"
                            }).done(function (result) {
                                if (result.error == null) {
                                    PiGallery.shareLink = result.data;
                                    var $shareLink = $("#shareLink");
                                    //set values
                                    $shareLink.val(PiGallery.shareLink.link);
                                    $("#sharingPath").html(PiGallery.shareLink.path);
                                    $("#recursiveShareBox").prop('checked', PiGallery.shareLink.isRecursive);
                                    slider.slider('setValue', getSliderValueFromHours(PiGallery.shareLink.validInterval));
                                    printSliderValue(slider.slider('getValue'));

                                    //set controlls
                                    $shareLink.removeAttr("disabled");
                                    $("#copyButton").removeAttr("disabled");
                                    $("#updatebutton").attr("disabled", "disabled");

                                    $("#loading-sign").css("opacity", 0);
                                    $shareModal.modal('show');
                                } else {
                                    if (result.error.code == PiGallery.enums.AjaxErrors.AUTHENTICATION_FAIL) {
                                        PiGallery.logOut();
                                        return;
                                    }
                                    PiGallery.showErrorMessage(result.error);
                                }
                            }).fail(function () {
                                console.log("Error during sharing");
                                $("#loading-sign").css("opacity", 0);
                            });
                        }
                    };


                $shareSlider.on("slide", function (slideEvt) {
                    printSliderValue(slideEvt.value);
                    $("#shareLink").attr("disabled", "disabled");
                    $("#copyButton").attr("disabled", "disabled");
                    $("#updatebutton").removeAttr("disabled");
                });
                $("#recursiveShareBox").click(function () {
                    $("#shareLink").attr("disabled", "disabled");
                    $("#copyButton").attr("disabled", "disabled");
                    $("#updatebutton").removeAttr("disabled");
                });
                printSliderValue(slider.slider('getValue'));


                $shareButton.click(function () {
                    getShareLink();
                });
                $("#updatebutton").click(function () {
                    getShareLink();
                });

                //initalizing copy button
                $("#shareLink").parent().removeClass("col-sm-10").addClass("col-sm-12");
                $copyButton.hide();
                ZeroClipboard.config({cacheBust: false,  swfPath: "js/lib/ZeroClipboard.swf"});

                var client = new ZeroClipboard($copyButton);
                client.on("ready", function () {
                    $("#copyButton").show();
                    $("#shareLink").parent().removeClass("col-sm-12").addClass("col-sm-10");
                });
            }
        };

        this.init();
        
        this.show = function(){
            if(PiGallery.Supported.Share === true){
                $shareButton.show();
            }
        };

        this.hide = function(){
            if(PiGallery.Supported.Share === true){
                $shareButton.hide();
            }
        };

    }
});
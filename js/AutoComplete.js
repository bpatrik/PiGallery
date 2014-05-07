var PiGallery = PiGallery || {};

define(["jquery", "jquery_ui"], function($) {
    "use strict";
    return function addAutoComplete(selector) {

        var SetRenderMode = function (acInstance) {
            acInstance._renderMenu = function (ul, items) {
                var self = this;
                if (items[0].noResult) {
                    // No result
                    var emptyHtml =
                        "<li class='ui-menu-item'>" +
                            "<div class='clearfix'>" +
                            "<div class='pull-left'>" +
                            "<h4 class='autocomplete error'>no result</h4>" +
                            "</div>" +
                            "</div>" +
                            "</li>";

                    return $(emptyHtml).appendTo(ul);
                } else {
                    $.each(items, function (index, item) {
                        self._renderItem(ul, item);
                    });

                }
            };
            acInstance._renderItem = function (ul, item) {
                var text = "<a>" +
                    "<div class='autocomplete-item'>" +
                    (
                        item.type == "photo" ? "<span class='autocomplete-type glyphicon glyphicon-picture'></span>" :
                            item.type == "dir" ? "<span class='autocomplete-type glyphicon glyphicon-folder-open'></span>" :
                                item.type == "keyword" ? "<span class='autocomplete-type'>#</span>" :"<span class='autocomplete-type'>&nbsp;</span>")+
                    "<span class='autocomplete-title'>" + item.text + "</span>" +
                    "</div>" +
                    "</a>";
                var element = $("<li></li>").data("ui-autocomplete-item", item).append($(text));
                return $(element).appendTo(ul);
            };
        }

        this.init = function() {
                var acInstance = $(selector).autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "model/AJAXfacade.php",
                        dataType: "json",
                        data: {
                            method: "autoComplete",
                            count: 5,
                            searchText: request.term
                        }
                    }).done( function (data) {
                        response($.map(data, function (item) {
                            return item;
                        }));
                    }).fail( function (error) {
                        console.log("Error: cant get auto complete data from server");
                    })
                    ;
                },
                minLength: 2,
                response: function (event, ui) {
                    if (ui.content.length == 0) {
                        //Put a dummy element to the result, to call _renderMenu, there will be shown the no result
                        ui.content.push({ noResult: true });
                    } else {
                        $.each(ui.content, function (index, item) {
                            item.value = item.text;
                        });
                    }
                }
            }).data("ui-autocomplete");

            SetRenderMode(acInstance);
        };
        this.init();

    };
});

require.config({
    baseUrl: '/js/',
    paths: {
        // the left side is the module ID,
        // the right side is the path to
        // the jQuery file, relative to baseUrl.
        // Also, the path should NOT include
        // the '.js' file extension. This example
        // is using jQuery 1.9.0 located at
        // js/lib/jquery-1.9.0.js, relative to
        // the HTML page.
       // jquery: 'jquery-2.1.0.min',
        jquery_ui: 'jquery-ui-1.10.4_min',
        knockout: 'knockout-3.1.0-min',
        bootstrap: 'bootstrap.min',
        bootstrap_image_gallery: 'bootstrap-image-gallery',
        blueImpGallery: 'blueimp-gallery-indicator'
    },
    shim:  {
        'blueImpGallery' : {
            deps: ['jquery', 'blueimp-gallery-fullscreen']
        },
        "bootstrap": {
            deps: ["jquery"]
        },
        'jquery_blueimp-gallery_min': {
            deps: ["jquery"]

        }

    }

});
requirejs(['jquery' ,'blueImpGallery','ContentManager', 'GalleryRenderer',"AutoComplete", 'bootstrap'],
    function   ($,blueimpGallery,ContentManager, GalleryRenderer, AutoComplete) {
        var contentManager = new ContentManager();
        var contentRenderer = new GalleryRenderer($("#directory-path"),$("#gallery"),contentManager);

        contentRenderer.showContent(PiGallery.preLoadedDirectoryContent);

        $("#search-button").click(function(event) {
            event.preventDefault();
            contentManager.getSearchResult($("#auto-complete-box").val(),contentRenderer );
        });


        /*---------------lightbox setup--------------------*/

      //   $('#blueimp-gallery').data('useBootstrapModal', false);
        var lb = null;

        $('#photo-gallery').click(function (event) {
            event = event || window.event;
            var target = event.target || event.srcElement,
                link = target.src ? target.parentNode : target,
                options = $.extend({}, $('#blueimp-gallery').data(),
                    {index: link, event: event}),
                links = $('#photo-gallery [data-galxlery]');
            lb = blueimpGallery(links, options);
            return false;
        });
        $('.full-screen').click(function () {
            lb.close();
            lb = null;
            $('#blueimp-gallery').data('fullScreen', true);
            lb = blueimpGallery($('#gallery [data-galxlery]'), $('#blueimp-gallery').data());
            $('#blueimp-gallery').data('fullScreen', false);
            return false;
        });

        new AutoComplete($("#auto-complete-box"));

});

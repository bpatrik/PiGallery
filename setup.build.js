/*
 * This is an example build file that demonstrates how to use the build system for
 * require.js.
 *
 * THIS BUILD FILE WILL NOT WORK. It is referencing paths that probably
 * do not exist on your machine. Just use it as a guide.
 *
 *
 */

({
    appDir: "./setup",

    //By default all the configuration for optimization happens from the command
    //line or by properties in the config file, and configuration that was
    //passed to requirejs as part of the app's runtime "main" JS file is *not*
    //considered. However, if you prefer the "main" JS file configuration
    //to be read for the build so that you do not have to duplicate the values
    //in a separate configuration, set this property to the location of that
    //main JS file. The first requirejs({}), require({}), requirejs.config({}),
    //or require.config({}) call found in that file will be used.
    //As of 2.1.10, mainConfigFile can be an array of values, with the last
    //value's config take precedence over previous values in the array.
   // mainConfigFile: '../main.js',


    //The directory path to save the output. If not specified, then
    //the path will default to be a directory called "build" as a sibling
    //to the build file. All relative paths are relative to the build file.
    dir: "../pigallery-setup-built/setup",

    optimizeCss: 'standard',


    //How to optimize all the JS files in the build output directory.
    //Right now only the following values
    //are supported:
    //- "uglify": (default) uses UglifyJS to minify the code.
    //- "uglify2": in version 2.1.2+. Uses UglifyJS2.
    //- "closure": uses Google's Closure Compiler in simple optimization
    //mode to minify the code. Only available if running the optimizer using
    //Java.
    //- "closure.keepLines": Same as closure option, but keeps line returns
    //in the minified files.
    //- "none": no minification will be done.
    // optimize: "none",
     optimize: "uglify2",
    logLevel: 3,
    mainConfigFile : "setup/js/main.js",

    modules: [
        {
            name: "main",
            excludeShallow: [
                "PiGallery/AdminPage",
                "PiGallery/AutoComplete",
                "PiGallery/GalleryRenderer",
                "PiGallery/SharingModule",,
                "jquery_countdown"
            ]
        }

        
    ],
  /*  skipDirOptimize: false,

    normalizeDirDefines: "skip",  
    inlineText: false,
    useStrict: true,
    skipModuleInsertion: true,
    optimizeAllPluginResources: true,
    findNestedDependencies: false,*/
    removeCombined: true,
    preserveLicenseComments: false

    //removeCombined: true,
   // optimizeAllPluginResources: false,
   /// findNestedDependencies: true



})

PiGallery 1.0
=========

This is a lightweight online photo gallery design for the Raspberry Pi, but can be used with any other device.
The aim of the project is to create a photo gallery that is optimised for low-powered servers, but with a rich client side.

Features:
--------

* The site has 2 modes: Database (Mysql) mode and a database-less mode.
* Authentication
  * Support for no-authentication/Guest mode at local network
* Recursive directory scan.
* On the fly thumbnail generation with cache(It works one threaded only. Won't use multiple thread on Pi2 either).
* On the fly directory indexing (for database mode).
* Nice lightbox for image preview (using blueimp* gallery).
* Supports image keywords (like lightroom keywords).
* sharing function (database mode only)
* Search in file names, directory names, keywords (db-mode only).
* Designed for low* resource serves (weak CPU, low bandwidth)
* Translation support (English, Hungarian)
* Nice Installer to configure the site.

[Change log](changelog.md)

Installation:
--------

*  Install PHP, and a webserver (Apache, Nginx) on your device and a (optional) database server (mysql, mariadb)
*  Download the latest (it contains minfied files) [release (v1.0)](release/pigallery_1.0.zip) from github, extract  it in your www directory (or in a subdirectory)
*  Go to the web page, it will automatically redirect you to a setup page, or open `config.php` and edit the configurations according to the comments.
*  If you're using the database mode, be sure that the database you set in the `config.php` exists'
*  Login with user: admin, pass: admin (or whatever you set in config.php)
*  In database mode, click on admin panel and click index photos,
   or enable on the fly indexing in the config.php (but in this case the manual indexing will still be at the admin panel)

Notes:
--------

* Site is using mysqli for accessing database. It should be enabled in `php.ini`.
* For thumbnail generation, `php-gd` is needed.
* Webserver's user needs read and write rights for the thumbnail folder and read rights for the images folder.
* For best performance don't store much photos in a directory (best is under 200-300)
* At large directories, PHP timeout may occur; if this is the case, increase the timeout in `php.ini`.
* For faster thumbnail generating user less thumbnail sizes (eg.: only one, with a low value such as `150`)
  and disable image resampling in `config.php`

Screenshots:
--------
![Screen](screen2.jpg?raw=true)

[![Install](install.jpg?raw=true =250x)](install.jpg?raw=true =250x)
[![Login](login.jpg?raw=true =250x)](login.jpg?raw=true =250x)
[![Screen](screen.jpg?raw=true =250x)](screen.jpg?raw=true =250x)
[![Screen](screen3.jpg?raw=true =250x)](screen3.jpg?raw=true =250x)
[![Screen](lightbox.jpg?raw=true =250x)](lightbox.jpg?raw=true =250x)

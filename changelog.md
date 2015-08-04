v1.0 (2015.08.04)
--------------------

* Installer wizard implemented
   * Automatically checks some basic requirements (php-gd, mysqli, image folder read right, thumbnail folder read/write rights)
* Switchable sharing and searching features
* some bug fixed (better error messages, db-less mode folder download bug fixed)

v0.99.1 (2015.01.06)
-----------------------
* Sharing feature added
* Added support for no-authentication/Guest mode at local network
* bug fixes

v0.99.1 (2014.06.13)
-----------------------
* The site has 2 modes: Database (Mysql) mode and a database-less mode.
* Authentication
* Recursive directory scan.
* On the fly thumbnail generation with cache(It works one threaded only. Won't use multiple thread on Pi2 either).
* On the fly directory indexing (for database mode).
* Nice lightbox for image preview (using blueimp* gallery).
* Supports image keywords (like lightroom keywords). 
* Search in file names, directory names, keywords (db-mode only).
* Designed for low* resource serves (weak CPU, low bandwidth)
* Translation support (English, Hungarian) 
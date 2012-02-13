Welcome to Gimlé Core v4
========================

This is the first Release Candidate.

Gimlé Core v4 is the base in the Gimlé system.

The project focus is speed and flexibility.


Features
--------
* Coding and build standards.
* Easy to extend and build anything on.
* Standards to ease moving between different enviroments.
* Enviroment setup:
  - Enviriomet (Development, test, Pre-production and production).
  - Run mode (Web or CLI)
  - error reporting.
  - time-zone.
  - character encoding.


Requirements
------------
php 5.3 / 5.4


Recomended file structure.
--------------------------
extensions/
gimle4/
|-- init.php (The core init file).
|-- config.ini (Global enviroment specifig settings).
sites/
|-- sitename/
|   |-- public/
|   |   |-- index.php
|   |-- config.php (Project specific settings).
|   |-- config.ini (Enviroment specifig settings).


Optional file structure for single sites.
-----------------------------------------
sitename/
|-- public/
|   |-- index.php
|-- init.php (The core init file).
|-- config.php (Project specific settings).
|-- config.ini (Enviroment specifig settings).


Installation
------------
Download or checkout from reposirtory to a non-public folder.

To start using Gimlé Core, first create a config.ini file one level up from your public directory containing:
core = "/absoulute/path/to/gimle/core/"

you can add this to the very beginning of your index.php file.

php 5.4:
--------
define('SITE_DIR', substr(__DIR__, 0, strrpos(__DIR__, DIRECTORY_SEPARATOR) + 1));
require parse_ini_file(SITE_DIR . 'config.ini')['core'] . 'init.php';
--------

php 5.3:
--------
define('SITE_DIR', substr(__DIR__, 0, strrpos(__DIR__, DIRECTORY_SEPARATOR) + 1));
$ini = parse_ini_file(SITE_DIR . 'config.ini');
require $ini['core'] . 'init.php';
unset($ini);
--------


FAQ
---
Q: Will the use of Gimlé Core limit me?
A: It will not limit the kind of projects you can build. But as with all code, it might limit the number of ways to get there.

Q: Is there much overhead added?
A: No. One of the ideas behind it, is to reduce overhead to a minimum.

Q: Is this a MVC Framework?
A: No. But this is a good platform to build any kind of framework you want on. A MVC extension might be on the table for later on.


Further reading
---------------
http://gimlé.org/documentation/v4/


----

irc: #Gimle @ freenode
homepage: http://gimlé.org/

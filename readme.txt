Welcome to Gimlé Core v4
========================

!!! Project is in pre alpha development, do not use in production !!!

Gimlé Core v4 is a mix of an advanced bootstrapper and a basic framework.
It's easy extendable for any kind of project.

The project focus is speed and flexability.


Features
--------
* Coding standard and build standards.
* Enviroment setup. (Stuff like time-zone, character encoding and such).
* MySQL handler.
* Language and translation utilities.
* Let you use php in css and javascript files, and file concatination for fewer http requests.
* Advanced var dumper.
* Character encoding helper.
* Strings, array and other var manipulation tools.
* Easy to extend and build anything on.


Requirements
------------
php 5.4


Recomended file structure
-------------------------

extensions/
gimle4/
sites/
|-- sitename/
|   |-- cli/ (Command line interface scripts).
|   |-- lib/
|   |   |-- namespace/ (Classes and traits).
|   |-- public/
|   |   |-- index.php
|   |-- config.php (Project specific settings).
|   |-- config.ini (Enviroment specifig settings).


Installation
------------
Download or checkout from reposirtory to a non-public folder.

To start using Gimlé Core, first create a config.ini file one level up from your public directory containing:
core = "/absoulute/path/to/gimle/core/"

you can add this to the very beginning of your index.php file.

define('SITE_DIR', substr(__DIR__, 0, strrpos(__DIR__, DIRECTORY_SEPARATOR) + 1));
require parse_ini_file(SITE_DIR . 'config.ini')['core'] . 'init.php';


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

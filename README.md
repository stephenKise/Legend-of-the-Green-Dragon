# About LotGD
Legend of the Green Dragon (LotGD) is a text-based RPG originally developed by Eric Stevens and JT Traub, a direct conversion from Seth Able's Legend of the Red Dragon (LoRD). The original core gameplay is still playable [at LotGD.net](http://lotgd.net). The project maintainer moved to the DragonPrime Development Team, where it was updated further. DragonPrime ceased in September 2019, with 1.1.2 DragonPrime Edition being the final release.

The original game concept centers around advancing from levels 1 to 15, slay the dragon, and repeat. The game hosts a clan system for players to cooperate together, allows for roleplay through a chat and mailing system, and an underworld if you perish along your journey. A 'New Day' system is the main game loop, where players regenerate their energy and gain new buffs to reinvigorate their characters. The game server offers a suite of web-based personalization via translator tools, an expansive module system for feature add-ons, permission system for superusers, and editors to manage most aspects of the base game.


## Further Development
Modern versions of PHP and MySQL have rendered version 1.1.2 DragonPrime Edition uninstallable. The project is being updated to support future versions of both, as well as expanding to more **modern** practices while providing documentation for upgrading. Versions beyond 1.1.2 DragonPrime Edition will become very opinionated, such as requiring a cache directory, reworking how rendered pages are stored, and strict types. An example of this project can be found at [lotgd.io](http://lotgd.io).


## Installation
#### Requirements
- PHP 8.4.0+
- MySQL 8.0.4+
- Your choice of web server
#### Process
Installing LotGD is broken down in four major parts, unchanged since version 1.1.2 DragonPrime Edition:
- Create a MySQL database for LotGD.
- Clone the respository
- Create a cache directory with proper permissions inside the repo.
- Navigate to your `/installer.php` for a graphical installation.
#### Example
For this example, we are using Ubuntu 24.04 LTS, with nginx 1.27.4, PHP 8.4.3 and MySQL 8.0.4 preconfigured:
```bash
# Create your database, using your {user}, {password} and {db}.
mysql -u {user} -p{password} -e 'CREATE DATABASE {db};'

# Clone the project. For this example, to a /var/www/html/lotgd.io directory
git clone git@github.com:stephenKise/Legend-of-the-Green-Dragon.git /var/www/html/lotgd.io

# Create a cache directory, name it whatever you wish.
cd /var/www/html/lotgd.io && mkdir cache 

# Ensure the proper read/write permissions are set to the cache folder
sudo chown -R www-data:www-data ./cache/

# Navigate to /installer.php in your browser. Be sure to include the name of your cache folder during installation.
```

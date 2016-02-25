# Legend of the Green Dragon
Legend of the Green Dragon (LotGD) is a text-based RPG originally developed by Eric Stevens and JT Traub, directly based on Seth Able's Legend of the Red Dragon (LoRD). There are numerous versions of the game you can play, with the original core (before this fork) located [here] (http://lotgd.net). The LotGD source code was then passed on to the [DragonPrime Development Team] (http://dragonprime.net) where it was kept up to date until the latest release of 1.1.2. Since the core is outdated, I have taken it upon myself to update the core for future use.

# Requirements
- PHP 7.0
- MariaDB 10.0 (Or similar MySQL database)
- Composer

# Installation
Before you install, it is best to have an understanding of what a linux server is, how to work with PHP and MySQL, as well as touch up on modern practices such as composer. For this example, we are using Ubuntu Trusty (14.04) and assuming you have all of the required programs installed.

```bash
# Install database, using your {user} and {password}.
mysql -u {user} -p{password} -e 'CREATE DATABASE LOTGD;'

# Clone LotGD
cd /var/www
git clone git@github.com:stephenKise/Legend-of-the-Green-Dragon.git

# Update the composer dependencies
composer update

# Navigate to /installer.php in your browser.
```

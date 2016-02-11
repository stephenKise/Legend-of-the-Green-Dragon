Legend of the Green Dragon
by  Eric "MightyE" Stevens (http://www.mightye.org)
and JT "Kendaer" Traub (http://www.dragoncat.net)

Software Project Page:
http://sourceforge.net/projects/lotgd

Modification AND Support Community Page:
http://dragonprime.net

Primary game servers:
http://lotgd.net
http://logd.dragoncat.net

For a new installation, see INSTALLATION below.
For upgrading a new installation, see UPGRADING below.
If you have problems, please visit Dragonprime at the address above.

----------------------------------------------
-- UPGRADING: --------------------------------
----------------------------------------------
ALWAYS extract the new distribution into a new directory!

BEFORE ANYTHING ELSE, read and understand the new code license.  This code
is no longer under the GPL!  Be aware that if you install this new version
on a publically accessible web server you are bound by the terms of the
license.

We also *STRONGLY* recommend that you shut down access to your game and
BACK UP your game database AND existing logd source files before attempting
an upgrade as most of the changes are NOT easily reversible!

If you are running a version after 0.9.7 you can do this by going
into the manage modules, installing the serversuspend module and then
activating it.  If you are running a 0.9.7 version, you will need to do
this some other way, such as via .htaccess under apache.  Consult the
documentation for your web server.

Once you have done this, copy the new code into the site directory. Due to
the need of the installer, you have to do this before running the
installer!  Make sure that you copy all of the files from all of the
subdirectories.

As of 0.9.8-prerelease.11, the only way to install or upgrade the game is
via the included installer.   To access the installer, log out of the game and
then access installer.php (for instance, if your game was installed at
http://logd.dragoncat.net, you would access the installer at
http://logd.dragoncat.net/installer.php)

From here, it should be a simple matter of walking through the steps!
Choose upgrade as the type of install (it defaults to *new* install, so
watch out for this!!) and choose the version you currently have installed and
it will perform an upgrade.

Once this is done, read the note for upgrading from 0.9.7 if you are, and
then go read the POST INSTALLATION section below.

*** NOTE FOR THOSE UPGRADING FROM 0.9.7 ***
In 0.9.8 and above, the 'specials' directory has been removed and that
functionality is now handled by modules.  If you have specials which are not
yet converted to modules, they will be unavailable until you convert them.
Move your specials directory to another directory name (for instance
specials.save) and work on converting them.  Most specials should convert
easily and you can look at existing examples.  If you haven't created (or
modified) specials on your server, just remove this directory.

----------------------------------------------
-- INSTALLATION: -----------------------------
----------------------------------------------
These instructions cover a new LoGD installation.
You will need access to a MySQL database and a PHP hosting
location to run this game. Your SQL user needs the LOCK TABLES 
privelege in order to run the game correct.

Extract the files into the directory where you will want the code to live.

BEFORE ANYTHING ELSE, read and understand the license that this game is
released under.  You are legally bound by the license if you install this
game on a publically accessible web server!

MySQL Setup:
Setup should be pretty straightforward, create the database, create
an account to access the database on behalf of the site; this account 
should have full permissions on the database.

After you have the database created, point your browser at the location you
have the logd files installed at and load up installer.php (for instance,
if the files are accessible as http://logd.dragoncat.net, you will want to
load http://logd.dragoncat.net/installer.php in the browser).  The installer
will walk you through a complete setup from the ground up.  Make sure to
follow all instructions!

Once you have completed the installation, read the POST INSTALLATION section
below.


----------------------------------------------
-- POST INSTALLATION: ------------------------
----------------------------------------------

Now that you have the game installed, you need to take a couple of sensible
precautions.

Firstly, make SURE that your dbconnect.php is not writeable.  Under unix,
you do this by typing
   chmod -w dbconnect.php
This is to keep you from making unintentional changes to this file.

The installer will have installed, but not activated, some common modules
which we feel make for a good baseline of the game.

You should log into the game (using the admin account created during
installation if this is a new install, or your regular admin account if this
is an update) and go into the manage modules section of the Superuser Grotto.
Go through the installed and uninstalled modules and make sure that the
modules you want are installed.  Do NOT activate them yet.
*** NOTE *** If this is a first-time install, you will see some messages about
races and specials not being installed during your character setup.  This is
fine and correct since you have not yet configured these items.

Now, go to the game settings page, and configure the game settings for the
base game and for the modules.   For an update, this should be just a
matter of looking at the non-active (grey-ed out) modules.  For an initial
install, this is a LOT of configuration, but taking your time here will
make your game MUCH better.

If you are upgrading from 0.9.7, look at your old game settings and make
the new ones similar.  A *lot* of settings have moved from the old
configuration screen and are now controlled by modules, so you will want
to write down your old configuration values BEFORE you start the upgrade.

Once you have things configured to your liking, you should go back to the
manage modules page and ACTIVATE any modules that you want to have running.

Good luck and enjoy your new LotGD server!

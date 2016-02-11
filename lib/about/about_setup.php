<?php
/**
 * Page displays some of the game settings
 * 
 * This page is part of the about system
 * and displays some of the game settings
 * so that users know some details about
 * the server.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
addnav("About LoGD");
addnav("About LoGD","about.php");
addnav("Module Info","about.php?op=listmodules");
addnav("License Info", "about.php?op=license");
$setup = array(
	"Game Setup,title",
	"pvp"=>"Enable Slay Other Players,viewonly",
	"pvpday"=>"Player Fights per day,viewonly",
	"pvpimmunity"=>"Days that new players are safe from PvP,viewonly",
	"pvpminexp"=>"Amount of experience when players become killable in PvP,viewonly",
	"soap"=>"Clean user posts (filters bad language and splits words over 45 chars long),viewonly",
	"newplayerstartgold"=>"Amount of gold to start a new character with,viewonly",
	"New Days,title",
	"fightsforinterest"=>"Player must have fewer than how many forest fights to earn interest?,viewonly",
	"maxinterest"=>"Max Interest Rate (%),viewonly",
	"mininterest"=>"Min Interest Rate (%),viewonly",
	"daysperday"=>"Game days per calendar day,viewonly",
	"specialtybonus"=>"Extra daily uses in specialty area,viewonly",

	"Bank settings,title",
	"borrowperlevel"=>"Max amount player can borrow per level,viewonly",
	"allowgoldtransfer"=>"Are players allowed to transfer gold,viewonly",
	"transferperlevel"=>"Max amount player can transfer per level of recipient (if transfers are enabled),viewonly",
	"mintransferlev"=>"Minimum level a player has to be before they can transfer gold (if transfers are enabled),viewonly",
	"transferreceive"=>"Total transfers a player can receive in one play day (if transfers are enabled),viewonly",
	"maxtransferout"=>"Max amount total a player can transfer to others per level (if transfers are enabled),viewonly",

	"Forest,title",
	"turns"=>"Forest Fights per day,viewonly",
	"dropmingold"=>"Forest Creatures always drop at least 1/4 of possible gold,viewonly",

	"Mail Settings,title",
	"mailsizelimit"=>"Message size limit per message,viewonly",
	"inboxlimit"=>"Limit # of messages in inbox,viewonly",
	"oldmail"=>"Automatically delete old messages after (days),viewonly",

	"Content Expiration,title",
	"expirecontent"=>"Days to keep comments and news?  (0 for infinite),viewonly",
	"expiretrashacct"=>"Days to keep accounts that were never logged in to? (0 for infinite),viewonly",
	"expirenewacct"=>"Days to keep level 1 accounts with no dragon kills? (0 for infinite),viewonly",
	"expireoldacct"=>"Days to keep all other accounts? (0 for infinite),viewonly",
	"LOGINTIMEOUT"=>"Seconds of inactivity before auto-logoff,viewonly"
);

$useful = array(
	"Useful Information,title",
	"dayduration"=>"Day Duration,viewonly",
	"curgametime"=>"Current game time,viewonly",
	"curservertime"=>"Current Server Time,viewonly",
	"lastnewday"=>"Last new day,viewonly",
	"nextnewday"=>"Next new day,viewonly"
);

$secstonextday = secondstonextgameday($details);
$useful_vals = array(
	"dayduration"=>round(($details['dayduration']/60/60),0)." hours",
	"curgametime"=>getgametime(),
	"curservertime"=>date("Y-m-d h:i:s a"),
	"lastnewday"=>date("h:i:s a",strtotime("-{$details['realsecssofartoday']} seconds")),
	"nextnewday"=>date("h:i:s a",strtotime("+{$details['realsecstotomorrow']} seconds"))." (".date("H\\h i\\m s\\s",$secstonextday).")"
);

output("`@<h3>Settings for this game</h3>`n`n",true);

$args = array('settings'=>array(),'values'=>array());
$args = modulehook("showsettings", $args);

$form = array_merge($setup, $args['settings']);
$form = array_merge($form, $useful);
$vals = array_merge($settings, $args['values']);
$vals = array_merge($vals, $useful_vals);
showform($form,$vals,true);
?>
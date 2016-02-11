<?php
/**
 * Page explaining what LotGD is
 * 
 * This page is part of the about page system
 * and is MightyE explaining what LotGD is. It
 * also contains a way in which a server admin
 * can display information about his/her server.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
$order=array("1","2");
while (list($key,$val)=each($order)){
	switch($val){
	case "2":
		/* NOTICE
		 * NOTICE Server admins may put their own information here,
		 * NOTICE please leave the main about body untouched.
		 * NOTICE
		 */
		rawoutput("<hr>");
		$impressum = getsetting("impressum", "");
		if ($impressum > "") {
			require_once("lib/nltoappon.php");
			output_notl("%s", nltoappon($impressum));
		}
		break;
	case "1":
		/* NOTICE
		 * NOTICE This section may not be modified, please modify the
		 * NOTICE Server Specific section above.
		 * NOTICE
		 */
		output("`@Legend of the Green Dragon`nBy Eric Stevens & JT Traub`n`n");
		output("`cLoGD version ");
		output_notl("$logd_version`c");
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("MightyE tells you, \"`2Legend of the Green Dragon is a remake of and homage to the classic BBS Door game, Legend of the Red Dragon (aka LoRD) by <a href='http://www.rtsoft.com' target='_blank'>Seth Able Robinson</a>.`@\"", true);
		output("`n`n`@\"`2LoRD is now owned by Gameport (<a href='http://www.gameport.com/bbs/lord.html' target='_blank'>http://www.gameport.com/bbs/lord.html</a>), and they retain exclusive rights to the LoRD name and game. ", true);
		output("That's why all content in Legend of the Green Dragon is new, with only a very few nods to the original game, such as the buxom barmaid, Violet, and the handsome bard, Seth.`@\"`n`n");
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("`@\"`2Although serious effort was made to preserve the original feel of the game, numerous departures were taken from the original game to enhance playability, and to adapt it to the web.`@\"`n`n");
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("`@\"`2LoGD (after version 0.9.7) is released under a <a href='http://creativecommons.org/licenses/by-nc-sa/2.0/' target='_blank'>Creative Commons License</a>, which essentially means that the source code to the game, and all derivatives of the game must remain open and available upon request.", true);
		output("Version 0.9.7 and before are still available under the <a href='http://www.gnu.org/licenses/gpl.html' target='_blank'>GNU General Public License</a> though 0.9.7 will be the last release under that license.", true);
		output("To use any of the new features requires using the 1.0.0 code.  You may explicitly not place code from versions after 0.9.7 into 0.9.7 and release the combined derivative work under the GPL.`@\"`n`n", true);
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("`@\"`2You may download the latest official version of LoGD at <a href='http://dragonprime.net/' target='_blank'>DragonPrime</a>  and you can play the Classic version at <a href='http://lotgd.net/'>http://lotgd.net</a>.`@\"`n`n",true);
		//output("`@\"`2The most recent *UNSTABLE* pre-release snapshot is available from <a href='http://dragonprime.net/users/Kendaer/' target='_blank'>http://dragonprime.net/users/Kendaer/</a>.", true);
		output("You should attempt to use this code only if you are comfortable with PHP and MySQL and willing to manually keep your code up to date.`@\"`n`n");
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("`@\"`2Additionally, there is an active modder community located at <a href='http://dragonprime.net' target='_blank'>DragonPrime</a> which may help you find additional features which you may wish to add to your game.", true);
		output("For these additional features you will find active support within the DragonPrime community.`@\"`n`n");
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("`@\"`2LoGD is programmed in PHP with a MySQL backend.");
		output("It is known to run on Windows and Linux with appropriate setups.");
		output("The core code has been actively written by Eric Stevens and JT Traub, with some pieces by other authors (denoted in the source at these locations), and the code has been released under a <a href='http://creativecommons.org/licenses/by-nc-sa/2.0/' target='_blank'>Creative Commons License</a>.", true);
		output("Users of the source are bound to the terms therein.`n",true);
		output("The DragonPrime Development Team took over responsibility for code development on January 1<sup>st</sup>, 2006 and continues to maintain and add to features of the core code.`@\"`n`n",true);
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		output("`@\"`2Users of the source are free to view and modify the source, but original copyright information, and original text from the about page must be preserved, though they may be added to.`@\"`n`n");
		output("`@\"`2We hope you enjoy the game!`@\"");
		/*
		 * This section may not be modified, please modify the Server
		 * Specific section above.
		 */
		break;
	}
}
addnav("About LoGD");
addnav("Game Setup Info","about.php?op=setup");
addnav("Module Info","about.php?op=listmodules");
addnav("License Info", "about.php?op=license");
modulehook("about");
?>
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
output("about.header");
output("about.version", $logd_version);
output("about.introduction", true);

$impressum = getsetting("impressum", "");
if ($impressum > '') {
    require_once('lib/nltoappon.php');
    output_notl('%s', nltoappon($impressum));
}
addnav("About LoGD");
addnav("Game Setup Info","about.php?op=setup");
addnav("Module Info","about.php?op=listmodules");
addnav("License Info", "about.php?op=license");
modulehook("about");
?>
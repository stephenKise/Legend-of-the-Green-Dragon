<?php
/**
 * Page explaining the LotGD license
 * 
 * This page is part of the about system
 * and is MightyE explaining the new license
 * in an easy to understand way.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
addnav("About LoGD");
addnav("About LoGD","about.php");
addnav("Game Setup Info","about.php?op=setup");
addnav("Module Info","about.php?op=listmodules");

output('about.license', true);
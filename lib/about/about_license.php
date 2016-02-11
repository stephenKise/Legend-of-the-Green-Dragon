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
output("`@MightyE tells you, \"`2We're going to take a few moments to try and explain this new license and the reasons behind it in plain English.");
output("The legalese for the license can be found online at <a href='http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode' target='_blank'>http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode</a> and should be read and understood in detail before you use this code.`@\"`n`n", true);
output("`@\"`2This new license was chosen because of a failing with the GPL.");
output("It only covered distribution of source if and only if binaries were distributed.`@\"`n`n");
output("`@\"`2In a web environment, specifically an interpreted web environment such as PHP, merely installing a game does not constitute distribution, and therefore people were taking our work, making modifications to it and not releasing the source code to their modifications so that the entire community could benefit.");
output("They worked with the letter but not the spirit of the law.`@\"`n`n");
output("`@\"`2Investigation on the part of the authors however, led to the fact that the right of public performance was one of the rights normally restricted to copyright holders, AND that computer programs, specifically video games and interactive ones such as Legend of the Green Dragon were considered to be publically performed if run from a public server.`@\"`n`n");
output("`@\"`2The new license restricts public performance of the work unless the source code of the modified work is made available on demand.`@\"`n`n");
output("`@\"`2In plain English, this means that if you put this game on a web server and allow people 'outside of a normal circle of family and its social acquaintances' to play there, then you are publically performing this work and MUST either a) make any and ALL changes which you make to the game available on request (note this doesn't have to be available via the online source display link, but they must be able to ask you for the code AND receive a complete copy), b) make arrangements privately with the authors wherein they grant you a special license, or c) remove the code entirely from the machine.`@\"`n`n");
output("`@\"`2We do recognize that people want to have areas of their game which are theirs and theirs alone.");
output("To that end we will make the following exception to the normal requirements for source code distribution -- any module file which is not modified or derived from a module file included in the base distribution AND which does not require any other modules AND which does not require any modifications to the core code (code distributed with the base release) may be withheld at the authors discretion.`@\"`n`n");
output("`@\"`2We also want to make very clear that version 0.9.7 (also known as version 0.9.7+jt) was the final version released under the GPL.");
output("All versions, starting with the 0.9.8-prerelease code are only licensed under the Creative Commons license.");
output("We EXPLICITLY deny the right to import any code from a 0.9.8-prerelease or later release into a 0.9.7 and earlier release.");
output("Allowing this would cause that imported code to be released under the GPL and that is not something we wish to allow.");
output("Authors of modifications to 0.9.7 will need to re-release their modifications as derivatives/modifications to 0.9.8 code and place them under the same Creative Commons license.");
output("It must be done by the original author since only the original author has the right to change the copyright or license upon their work.");
output("[Additionally, reworking the modifications will be a good idea anyway as the mechanism for making modifications is substantially cleaner/clearer starting with the 0.9.8-prerelease code.]`@\"");
?>
<?php
output("`@`c`bAll Done!`b`c");
output("Your install of Legend of the Green Dragon has been completed!`n");
output("`nRemember us when you have hundreds of users on your server, enjoying the game.");
output("Eric, JT, and a lot of others put a lot of work into this world, so please don't disrespect that by violating the license.");
if ($session['user']['loggedin']){
	addnav("Continue",$session['user']['restorepage']);
}else{
	addnav("Login Screen","./");
}
savesetting("installer_version",$logd_version);
$noinstallnavs=true;
?>
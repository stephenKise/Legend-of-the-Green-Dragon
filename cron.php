<?php

define("ALLOW_ANONYMOUS",true);
//enter your directory HERE! i.e. /home/myname/lotgd
$dir='/yourdir';
//end of admin modifications
chdir($dir);
require_once("common.php");
savesetting("newdaySemaphore",gmdate("Y-m-d H:i:s"));
if ($dir!='') {
	require("lib/newday/newday_runonce.php");
}
/* Prevent execution if no value has been entered... if it is a wrong value, it will still break!*/

?>
<?php
// add gems one off
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 29 Nov 2004 */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function addgems_getmoduleinfo(){
$info = array(
"name"=>"Add Gems module",
"version"=>"1.0",
"author"=>"Shannon Brown",
"category"=>"Village",
"download"=>"core_module",
"prefs"=>array(
"Add Gems User Preferences,title",
"gotgems"=>"Has the player received their gems yet?,bool|0",
)
);
return $info;
}

function addgems_install(){
module_addhook("newday");
return true;
}

function addgems_uninstall(){
return true;
}

function addgems_dohook($hookname,$args){
global $session;
switch($hookname){
case "newday":
$gotgems=get_module_pref("gotgems");
if ($gotgems==0) {
global $session;
$session['user']['gems']+=2;
output("`^`n`n`n`c* * * * * * * * * * * * * * * * * * * * * * * * *`n");
output("* * * * * * * * * * * * * * * * * * * * * * * * *`n");
output("`n`%As our way of saying thank you for all the wonderful donations you have pledged to Child's Play during November, we are pleased to present every player with two gems. `n`n`^Thanks for your support!`n`n");
output("* * * * * * * * * * * * * * * * * * * * * * * * *`n");
output("* * * * * * * * * * * * * * * * * * * * * * * * *`c`n`n`n");
set_module_pref("gotgems",1);
}
break;
}
return $args;
}

?>

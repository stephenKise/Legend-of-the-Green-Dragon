<?php
// addnews ready
// translator ready
// mail ready

/* Abigail the Street Hawker */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 23 Aug 2004 */
/* ver 1.1 by Christian Rutsch => xchrisx -at- lotgd -dot- de */
/* code now works with other lovers than Seth/Violet. */
/* gifts can be extended by modules. */
/* 03 Nov 2006 */

// default settings add average 6 charm points per 10 gems spent

require_once("lib/villagenav.php");
require_once("lib/http.php");
require_once("lib/partner.php");

function abigail_getmoduleinfo(){
$info = array(
"name"=>"Abigail the Street Hawker",
"version"=>"1.1",
"author"=>"Shannon Brown, changes by Christian Rutsch",
"category"=>"Village Specials",
"download"=>"core_module",
"requires"=>array(
"lovers" => "1.0|Eric Stevens, core_module",
),
"settings"=>array(
"Abigail the Street Hawker - Settings,title",
"cost"=>"Number of gems the items cost,int|2",
"charmchance"=>"What is the chance that the partner will like the gift?,range,5,100,5|80",
),
"prefs"=>array(
"Abigail the Street Hawker User Preferences,title",
"bought"=>"Purchased Gift Today?,bool|0",
"trinket"=>"Last gift offered?,boots",
"liked"=>"Liked the gift from their lover,bool|0",
"angry"=>"Abigail will leave the user allone for value days,int|0",
)
);
return $info;
}

function abigail_install(){
module_addhook("newday");
// we only want this to happen if they haven't seen her already today
module_addeventhook("village","\$bought=get_module_pref(\"bought\", \"abigail\");return (\$bought?0:50);");
module_addeventhook("gardens","\$bought=get_module_pref(\"bought\", \"abigail\");return (\$bought?0:50);");
return true;
}

function abigail_uninstall(){
return true;
}

function abigail_dohook($hookname,$args){
global $session;
require_once("lib/partner.php");
$partner = get_partner(true);
switch($hookname){
case "newday":
if(get_module_pref("angry") > 0) {
increment_module_pref("angry",-1);
} else if (get_module_pref("bought")==1) {
output("`n`n`5Just as you get ready to set off for the day, a messenger boy hands you a note from %s`5.", "`^".$partner);
if (get_module_pref("liked")==1){
output("`5\"`%What a wonderful surprise! Your gift was very thoughtful! I shall show everyone!`5\"`n`n");
output("`^You gain some charm!`n");
$session['user']['charm']+=2;
} else{
output("`5\"`%I can't believe you think you can win my approval with a cheap gift like that!`5\"`n`n");
output("`^You `\$lose`^ some charm.`n");
if ($session['user']['charm']>2){
$session['user']['charm']-=2;
} else $session['user']['charm'] = 0;
}
}
set_module_pref("liked",0);
if(get_module_pref("angry") > 0)
set_module_pref("bought",1);
else
set_module_pref("bought",0);
break;
}
return $args;
}

function abigail_runevent($type, $link) {
global $session;
$from = $link;
$session['user']['specialinc'] = "module:abigail";

$partner = get_partner(true);
$gemword="gems";
$cost=get_module_setting("cost");
if ($cost==1){
$gemword="gem";
}

$trinket=get_module_pref("trinket");

$op = httpget('op');
if ($op == "") {
$gifts[SEX_FEMALE] = array(
"pair of cufflinks",
"leather belt",
"hat",
"pair of boots",
);
$gifts[SEX_MALE] = array(
"pair of earrings",
"pair of satin slippers",
"jeweled necklace",
"pretty bracelet",
);
$gifts = modulehook("abigail-gifts", array("gifts"=>$gifts));
$gifts[SEX_FEMALE] = translate_inline($gifts['gifts'][SEX_FEMALE]);
$gifts[SEX_MALE] = translate_inline($gifts['gifts'][SEX_MALE]);
$trinket = $gifts[$session['user']['sex']][e_rand(0, count($gifts[$session['user']['sex']])-1)];
// Remember to leave some peanuts to get the elephant out of this labyrinth of brackets...
set_module_pref("trinket",$trinket);
output("`7While you are wandering idly, minding your own business, you are approached by a diminutive elf in a green cloak. `n`n");
$greeting = translate_inline($session['user']['sex']?"Madam":"Sir");
output("\"`&Happy Day to ye, %s!", $greeting);
output("Can I interest you in a lovely %s for somebody special?", $trinket);
output("It's a fine gift, crafted with care and skill!");

if ($cost == 1) {
output("And, for you, only `%%s`& gem!", $cost);
} else  {
output("And, for you, only `%%s`& gems!", $cost);
}
output_notl("`7\"`n`n");
output("`7You survey the %s, admiring the fine craftsmanship, and try to imagine `^%s`7 wearing such a gift.", $trinket, $partner);

addnav("Purchase this gift",$from."op=shop");
addnav("Don't buy anything",$from."op=nope");
addnav("Shout at Abigail",$from."op=shout");
}
elseif ($op == "leave") {
$session['user']['specialinc'] = "";
output("`5Not having any gems to buy a gift for `^%s`5, you wander sadly away.`n`n", $partner);
}
elseif($op=="nope"){
output("`7You decide not to buy the %s from Abigail.`n`n",$trinket);
output("`7You're sure that `^%s`7 wouldn't like something like that, anyway.`n", $partner);
$session['user']['specialinc'] = "";
}elseif($op == "shout")
{
output("Abigail just shakes her head and leaves!`n");
output("You feel a great relief.");
set_module_pref("angry",10);
set_module_pref("bought",1);
$session['user']['specialinc'] = "";
}elseif($session['user']['gems']<$cost){
if($session['user']['gems']==0){
output("`7Abigail stares at your empty hand.`n`n");
} else {
if($session['user']['gems']==1){
output("`7Abigail stares at the single gem in your hand.`n`n");
} else {
output("`7Abigail stares at the %s gems in your hand.`n`n", $session['user']['gems']);
}
}
output("`7How can you buy `^%s`7 a gift without enough gems?", $partner);
addnav("Walk Away",$from."op=leave");
}elseif($op=="shop"){
addnav("Return to whence you came", $from);
set_module_pref("bought",1);
$session['user']['gems']-=$cost;
debuglog("spent $cost gems on a gift for their lover");
if ($cost == 1) {
output("`7Agreeing to buy the %s, you hand over the %s gem.`n`n",
$trinket, $cost);
} else {
output("`7Agreeing to buy the %s, you hand over the %s gems.`n`n",
$trinket, $cost);
}
output("`7Abigail promises to have the %s delivered to `^%s`7 right away.`n`n", $trinket, $partner);
output("`7You can't wait to find out what `^%s`7 thinks of the gift!", $partner);
if ($session['user']['marriedto'] != INT_MAX && $session['user']['marriedto'] != 0) {
require_once("lib/systemmail.php");
$subject = "`%Abigail has delivered a gift to you!";
$body = array("`^%s`2 has delivered a %s as a gift.", $session['user']['name'], $trinket);
systemmail($session['user']['marriedto'], $subject, $body, 0);
}

$likechance=(e_rand(1,100));
$charmchance=get_module_setting("charmchance");
if ($likechance<=$charmchance) {
$newval=get_module_pref("liked");
$newval++;
set_module_pref("liked",$newval);
}
else {
set_module_pref("liked",-1);
}
}
if ($op != "") {
$session['user']['specialinc'] = "";
}
}
?>
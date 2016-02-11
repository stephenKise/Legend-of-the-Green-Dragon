<?php
// addnews ready
// mail ready
// translator ready

/* Sichae's Apple Bobbing */
/* ver 4.1 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* Based on Sichae's Apple Stand ver 4.0 by Chris Vorndran */
/* 21st Sept 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function applebob_getmoduleinfo(){
$info = array(
"name"=>"Sichae's Apple Bobbing",
"version"=>"4.1",
"author"=>"Chris Vorndran & Shannon Brown",
"category"=>"Village",
"download"=>"core_module",
"settings"=>array(
"Apple Bobbing - Settings,title",
"eatallowed"=>"How many apples may the player eat?,int|3",
"cost"=>"Price to play?,int|2",
"applebobloc"=>"Where does the stand appear,location|".getsetting("villagename", LOCATION_FIELDS)
),
"prefs"=>array(
"Apple Bobbing - User Preferences,title",
"eattoday"=>"How much has the user eaten today?,int|0",
)
);
return $info;
}

function applebob_install(){
module_addhook("changesetting");
module_addhook("newday");
module_addhook("village");
return true;
}

function applebob_uninstall(){
return true;
}

function applebob_dohook($hookname,$args){
global $session;
switch($hookname){
case "changesetting":
if ($args['setting'] == "villagename") {
if ($args['old'] == get_module_setting("applebobloc")) {
set_module_setting("applebobloc", $args['new']);
}
}
break;
case "newday":
set_module_pref("eattoday",0);
break;
case "village":
if ($session['user']['location'] == get_module_setting("applebobloc")) {
tlschema($args['schemas']['marketnav']);
addnav($args['marketnav']);
tlschema();
addnav("A?Sichae's Apple Bobbing","runmodule.php?module=applebob");
}
break;
}
return $args;
}

function applebob_run() {
global $session;
$op = httpget('op');
$cost=get_module_setting("cost");
$eatallowed=get_module_setting("eatallowed");
$eattoday=get_module_pref("eattoday");
page_header("Sichae's Apple Bobbing Stand");
output("`&`c`bApple Bobbing Stand`b`c");
if ($eattoday>=$eatallowed){
output("`7Much as you'd like to play, your stomach protests fitfully.");
addnav("Leave","village.php");
}elseif ($session['user']['gold']<$cost){
output("`7Much as you'd like to play, your purse doesn't yield enough to pay for the privilege.");
addnav("Leave","village.php");
}elseif ($op==""){
output("`7You begin to approach the Apple Bob, peering into the barrels with interest.");
output("Inside are apples of red, yellow and green.");
output("Sichae stands with her hands on her hips, and regards you with a mysterious smile.`n`n");
output("Her silken garments of jade and blue swish in the cool breeze, and her lithe muscles flex as she pads over to where you stand.`n`n");
output("`&\"Ah! A visitor to the realms!");
output("So you think you can do this, do you?");
output("It shall be amusing to see you try.\"`n`n");
output("`7She arches her delicate neck back, and laughs a deep and beautiful sound, that immediately makes you relax.");
output("She motions to the barrel in front of you.");
output("`&\"%s gold to show me what talent you posess.",$cost);
output("And one of the apples is special indeed...\"");
addnav(array("Try your luck (%s gold)",$cost),"runmodule.php?module=applebob&op=bob");
addnav("Leave","village.php");
}elseif ($op=="bob"){
$eattoday++;
set_module_pref("eattoday",$eattoday);
$session['user']['gold']-=$cost;
debuglog("spent $cost gold on an apple.");
output("`7You hand Sichae your %s gold, and place your hands on the edge of the barrel.",$cost);
output("Taking a deep breath, you plunge your head forwards into the chilly water, and vainly attempt to grab hold of an apple with your teeth.");
output("You finally emerge from the water, gasping for breath.");
output("Sichae smiles at your success.`n`n");
output("`&\"Well done, fair warrior!\"`n`n");
$applechance=(e_rand(1,10));
$colour=(e_rand(1,4));
if ($colour==1) $colour=translate_inline("`4red");
if ($colour==2) $colour=translate_inline("`2green");
if ($colour==3) $colour=translate_inline("`^yellow");
if ($applechance==1){
output("`7She grins mischievously, `&\"'Tis a rare warrior that plucks the hallowed blue apple!");
output("There you have my finest achievement.");
output("Go forth and slay all in your path, enchanted one!\"`n`n");
output("`7Your jaw slackens in astonishment at the thought of a blue apple, but you manage to catch the fruit in one hand as it falls.");
output("As you do, its delicious flavor hits you with surprise.");
output("Your muscles tingle and a warm buzz flows into your very bones.`n`n");
output("You feel `5mystical!");
apply_buff('sichae',array("name"=>"`!Blue Apple Mystique","rounds"=>20,"defmod"=>1.03,"roundmsg"=>"`!The Blue Apple's power tingles in your bones."));
}elseif ($colour==4){
output("`7As she says this though, you realize that something is very odd about this apple.");
output("It looks and tastes just like an ordinary green apple, but you begin to feel very strange.`n`n");
output("Bizarre creatures appear before your eyes.");
output("You realize that someone has poisoned the apple!`n`n");
output("All of the imaginary monsters from your nightmares close in on you, and you feel the terrifying urge to flee this place!");
set_module_pref("eattoday",$eatallowed);
apply_buff('sichae',array("name"=>"`!Poison Apple","rounds"=>20,"defmod"=>0.97,"roundmsg"=>"`!Strange hallucinations taunt you as you fight."));
blocknav("runmodule.php?module=applebob&op=bob");
}else{
output("`7You grin around your %s `7apple and enjoy its crisp flavor.",$colour);
// Don't let it heal them too far
if ($session['user']['hitpoints'] <=
$session['user']['maxhitpoints']*1.1) {
$session['user']['hitpoints']*=1.05;
output("`@You feel healthy!");
}
}
addnav(array("Try again (%s gold)",$cost),"runmodule.php?module=applebob&op=bob");
addnav("Leave","village.php");
}
page_footer();
}
?>

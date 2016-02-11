<?php

// this is a module that adds more tattoos to petra's tattoo parlor.

function additionaltattoos_getmoduleinfo()
{
$info = array(
"name"=>"Additional Tattoos",
"category"=>"Village",
"author"=>"dying",
"version"=> "0.1",
"download"=>"core_module",
"requires"=>array(
"petra"=>"1.3|by Shannon Brown and dying, distributed with the core code"
),
"settings"=>array(
"reqphoenix"=>"Required number of resurrections for phoenix tattoo to be visible,range,0,50,1|25",
"reqdragon"=>"Required number of dragon kills for dragon tattoo to be visible,range,0,50,1|5",
"buffname"=>"Name of the buff required for the firebreating tattoo|Dragon's Breath",
)
);
return $info;
}

function additionaltattoos_install()
{
module_addhook("petraavail");
module_addhook("petraadded");
module_addhook("petradescr");
module_addhook("petracolor");

return true;
}

function additionaltattoos_uninstall()
{
return true;
}

function additionaltattoos_dohook($hookname, $args)
{
global $session;

switch ($hookname) {
case "petraavail":
// Make sure we have Petra.  We *should* but, just in case.
require_once("modules/petra.php");

// Resurrections are only per DK, so someone actually has to be
// looking in order to get this one.
if (!isset($args['tattoos']['phoenix']) &&
($session['user']['resurrections'] >=
get_module_setting("reqphoenix"))) {
petra_addnav("phoenix", translate_inline("Phoenix"));
$args['canbuy'] = 1;
}
if (!isset($args['tattoos']['dragon']) &&
($session['user']['dragonkills'] >=
get_module_setting("reqdragon")) ) {
petra_addnav("dragon", translate_inline("Dragon"));
$args['canbuy'] = 1;
}
if (!isset($args['tattoos']['dragonfire']) &&
isset($args['tattoos']['dragon'])) {
$bufflist = @unserialize($session['user']['bufflist']);
if (isset($bufflist['buzz']['name']) &&
(color_sanitize($bufflist['buzz']['name']) == get_module_setting("buffname"))) {
petra_addnav("dragonfire", translate_inline("Dragonfire"));
$args['canbuy'] = 1;
}
}
break;
case "petraadded":
if (isset($args['tattoos']['dragon']) &&
($args['tattoos']['dragon']==1) &&
isset($args['tattoos']['dragonfire']) &&
($args['tattoos']['dragonfire']==1) ) {
// replace dragon and dragonfire tattoos with
// the firebreathingdragon tattoo set,
// but keep the two elements defined so that
// they don't show up as available in the parlor
$args['tattoos']['dragon'] = "hidden";
$args['tattoos']['dragonfire'] = "hidden";
// bonus of 1 for collecting set
$args['tattoos']['firebreathingdragon'] = 3;
}
break;
case "petradescr":
if ($args['tname']=="phoenix") {
/* [add translated phoenix description] */
$args['tattoodescr'] = "";
} elseif ($args['tname']=="dragon") {
/* [add translated dragon description] */
$args['tattoodescr'] = "";
} elseif ($args['tname']=="dragonfire") {
/* [add translated fire-breathing dragon description] */
$args['tattoodescr'] = "";
}
break;
case "petracolor":
if ($args['tname']=="phoenix") {
$args['colortat'] = "`\$p`Qh`^o`&e`^n`Qi`\$x";
} elseif ($args['tname']=="dragon") {
$args['colortat'] = "`2dragon";
} elseif ($args['tname']=="firebreathingdragon") {
$args['colortat'] = "`\$fi`Qre b`^rea`&thi`@ng `2dragon";
}
break;
}
return $args;
}

?>

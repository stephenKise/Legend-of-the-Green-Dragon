<?php
// addnews ready
// mail ready
function advertising_splitreason_getmoduleinfo(){
$info = array(
"name"=>"Advertising -- SplitReason",
"author"=>"Eric Stevens",
"version"=>"1.0",
"download"=>"core_module",
"category"=>"General",
"settings"=>array(
"trackingid"=>"Your SplitReason tracking ID|",
),
);
return $info;
}

function advertising_splitreason_install(){
module_addhook("mod_ad_banner");
return true;
}
function advertising_splitreason_uninstall(){
return true;
}
function advertising_splitreason_dohook($hookname,$args){
$banner = advertising_splitreason_get($args['size']);
if ($banner == "") {
}else{
reset($banner);
while (list($key,$val)=each($banner)){
array_push($args['banners'],$val);
}
}
return $args;
}
//http://www.splitreason.com//img/banners/460x60.gif
function advertising_splitreason_get($size){
$id = get_module_setting("trackingid");
switch($size){
case "468x60":
return array(
'<a href="http://www.splitreason.com//click_thru.php?id='.$id.'">'.
'<img src="http://www.splitreason.com//img/banners/460x60.gif" alt="SplitReason: Get your swag here" width="468" height="60" border="0">'.
'</a>',

'<a href="http://www.splitreason.com//click_thru.php?id='.$id.'">'.
'<img src="http://www.splitreason.com//img/banners/468x60-6.gif" alt="SplitReason: Get your swag here" width="468" height="60" border="0">'.
'</a>',
);
break;
}
return "";
}

?>

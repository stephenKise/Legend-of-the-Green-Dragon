<?php
// addnews ready
// mail ready
function advertising_amazon_getmoduleinfo(){
$info = array(
"name"=>"Advertising -- Amazon",
"author"=>"Eric Stevens",
"download"=>"core_module",
"version"=>"1.0",
"category"=>"General",
"settings"=>array(
"trackingid"=>"Your Amazon Associates tracking ID|",
),
);
return $info;
}

function advertising_amazon_install(){
module_addhook("mod_ad_banner");
return true;
}
function advertising_amazon_uninstall(){
return true;
}
function advertising_amazon_dohook($hookname,$args){
$banner = advertising_amazon_get($args['size']);
if ($banner == "") {
}else{
reset($banner);
while (list($key,$val)=each($banner)){
array_push($args['banners'],$val);
}
}
return $args;
}
function advertising_amazon_get($size){
$id = get_module_setting("trackingid");
switch($size){
case "468x60":
return array(
//standard amazon generic link
'<iframe width=468 height=60 scrolling="no" frameborder=0 src="http://rcm.amazon.com/e/cm?t='.$id.'&p=13&o=1&l=ez&f=ifr">'.
'<MAP NAME="boxmap-p13"><AREA SHAPE="RECT" COORDS="379, 50, 460, 57" HREF="http://rcm.amazon.com/e/cm/privacy-policy.html?o=1" ><AREA COORDS="0,0,10000,10000" HREF="http://www.amazon.com/exec/obidos/redirect-home/'.$id.'" ></MAP><img src="http://rcm-images.amazon.com/images/G/01/rcm/468x60.gif" width="468" height="60" border="0" usemap="#boxmap-p13" alt="Shop at Amazon.com">'.
'</iframe>',
//popular computer games
'<iframe marginwidth="0" marginheight="0" width="468" height="60" scrolling="no" frameborder="0" src="http://rcm.amazon.com/e/cm?t='.$id.'&p=13&o=1&l=bn1&browse=471280&mode=videogames&bg1=000000&fc1=639C18&lc1=7BC618&amp;lt1=_blank&f=ifr">'.
'<MAP NAME="boxmap-p13"><AREA SHAPE="RECT" COORDS="379, 50, 460, 57" HREF="http://rcm.amazon.com/e/cm/privacy-policy.html?o=1" target=main><AREA COORDS="0,0,10000,10000" HREF="http://www.amazon.com/exec/obidos/redirect-home/'.$id.'" target=main></MAP><img src="http://rcm-images.amazon.com/images/G/01/rcm/468x60.gif" width="468" height="60" border="0" usemap="#boxmap-p13" alt="Shop at Amazon.com">'.
'</iframe>',
// fantasy/sci-fi movies
'<iframe marginwidth="0" marginheight="0" width="468" height="60" scrolling="no" frameborder="0" src="http://rcm.amazon.com/e/cm?t='.$id.'&p=13&o=1&l=bn1&browse=163431&mode=dvd&bg1=000000&fc1=639C18&lc1=7BC618&amp;lt1=_blank&f=ifr">'
.'<MAP NAME="boxmap-p13"><AREA SHAPE="RECT" COORDS="379, 50, 460, 57" HREF="http://rcm.amazon.com/e/cm/privacy-policy.html?o=1" target=main><AREA COORDS="0,0,10000,10000" HREF="http://www.amazon.com/exec/obidos/redirect-home/'.$id.'" target=main></MAP><img src="http://rcm-images.amazon.com/images/G/01/rcm/468x60.gif" width="468" height="60" border="0" usemap="#boxmap-p13" alt="Shop at Amazon.com">'
.'</iframe>',
//computers
'<iframe marginwidth="0" marginheight="0" width="468" height="60" scrolling="no" frameborder="0" src="http://rcm.amazon.com/e/cm?t='.$id.'&p=13&o=1&l=bn1&browse=565118&mode=pc-hardware&bg1=000000&fc1=639C18&lc1=7BC618&amp;lt1=_blank&f=ifr">'
.'<MAP NAME="boxmap-p13"><AREA SHAPE="RECT" COORDS="379, 50, 460, 57" HREF="http://rcm.amazon.com/e/cm/privacy-policy.html?o=1" target=main><AREA COORDS="0,0,10000,10000" HREF="http://www.amazon.com/exec/obidos/redirect-home/'.$id.'" target=main></MAP><img src="http://rcm-images.amazon.com/images/G/01/rcm/468x60.gif" width="468" height="60" border="0" usemap="#boxmap-p13" alt="Shop at Amazon.com">'
.'</iframe>',
// fantasy/sci-fi books
'<iframe marginwidth="0" marginheight="0" width="468" height="60" scrolling="no" frameborder="0" src="http://rcm.amazon.com/e/cm?t='.$id.'&p=13&o=1&l=bn1&browse=25&mode=books&bg1=000000&fc1=639C18&lc1=7BC618&amp;lt1=_blank&f=ifr">'
.'<MAP NAME="boxmap-p13"><AREA SHAPE="RECT" COORDS="379, 50, 460, 57" HREF="http://rcm.amazon.com/e/cm/privacy-policy.html?o=1" target=main><AREA COORDS="0,0,10000,10000" HREF="http://www.amazon.com/exec/obidos/redirect-home/'.$id.'" target=main></MAP><img src="http://rcm-images.amazon.com/images/G/01/rcm/468x60.gif" width="468" height="60" border="0" usemap="#boxmap-p13" alt="Shop at Amazon.com">'
.'</iframe>',
);
break;
}
return "";
}

?>

<?php
// addnews ready
// mail ready
//To do:
//Finish the code for users to purchase their own ads.
define("MOD_ADVERTISING_UNAPPROVED",0);
define("MOD_ADVERTISING_APPROVED",1);
define("MOD_ADVERTISING_DENIED",2);

if (!defined("OVERRIDE_FORCED_NAV")) define("OVERRIDE_FORCED_NAV",true);

define("MOD_ADVERTISING_SUAPPROVER",SU_MEGAUSER|SU_EDIT_PETITIONS|SU_EDIT_COMMENTS|SU_EDIT_DONATIONS|SU_EDIT_USERS|SU_EDIT_CONFIG);

function advertising_getmoduleinfo(){
$info = array(
"name"=>"Advertising",
"version"=>"1.0",
"author"=>"Eric Stevens",
"category"=>"General",
"download"=>"core_module",
"allowanonymous"=>true,
"override_forced_nav"=>true,
"settings"=>array(
"Site Advertising,title",
"baseprice"=>"Base CPM in donator points (per thousand impressions for avg sized banner),int|100",
"bytespp"=>"Max bytes per pixel (rec ~2.5),int|2.6",
"totalads"=>"Total ads to show on each page,enum,0,0,1,1,2,2,3,3,4,4|1",
"crawldir"=>"Subdirectory to periodically write page copies in for search engine crawling (applies mostly to Google ads; blank to disable; you must include a trailing slash).|crawl/",
),
"prefs"=>array(
"Site Advertising Prefences (choose up to only 2 types of banner),title",
"impressions"=>"Advertising Impressions,int|0",
"optout"=>"Opt-Out impressions purchased,int|0",
"optoutused"=>"Opt-out impressions used,int|0",
"optouttill"=>"Date through which opt-out has been purchased|".date("Y-m-d H:i:s"),
),
);


$banners = advertising_getbanners();
while (list($key,$val)=each($banners)){
$i = split("x",$key);
$w = $i[0];
$h = $i[1];
$i = split(",",$val);
$title = $i[0];
//required advertising
$info['settings']['sizes']="Banner sizes,title";
$info['settings']["banner_{$w}x{$h}"]="Show $title ({$w}x{$h})?,bool|0";
}
return $info;
}

function advertising_getbanners($limited=false){
//key format: width, height, cost multiplier, template location -- separated by x's
$banners = array(
"468x60"=>"Banner,headerad,1",
"728x90"=>"Leaderboard,headerad,1.8",
"234x60"=>"Half Banner,headerad,.75",
"125x125"=>"Button,navad,.75",
"120x600"=>"Skyscraper,verticalad,2",
"160x600"=>"Wide Skyscraper,verticalad,1.5",
"120x240"=>"Vertical Banner,verticalad,1.1",
"300x250"=>"Medium rectangle,bodyad,1.2",
"250x250"=>"Square,bodyad,.8",
"336x280"=>"Large Rectangle,bodyad,1",
"180x150"=>"Small Rectangle,navad,.5",
);
if ($limited){
while (list($key,$val)=each($banners)){
if (get_module_setting("banner_".$key)) continue;
unset($banners[$key]);
}
reset($banners);
}
return $banners;
}

function advertising_getallads(){
$ret = array();
$types = array();
$banners = advertising_getbanners(true);
while (list($key,$val)=each($banners)){
$info = explode(",",$val);
if (!isset($types[$info[1]])){
$types[$info[1]] = array("priority"=>0);
}
}
reset($banners);
$sql = "SELECT * FROM ". db_prefix("mod_advertising_banners") . " WHERE approved=".MOD_ADVERTISING_APPROVED." AND servedimpressions < maximpressions ORDER BY priority DESC";
$result = db_query_cached($sql,"advertising_getallads");
while ($row = db_fetch_assoc($result)){
//get the display location of this ad
if ($row['start'] > "2000" && $row['end'] < "2038"){
//when banners have a reasonable start and end date, spread their
//impressions out evenly over the full length of time.
$pctserved = $row['servedimpressions'] / $row['maximpressions'];
$start = strtotime($row['start']);
$end = strtotime($row['end']);
$now = strtotime("now");
$pctcampaign = ($now-$start) / ($end-$start);
if ($pctcampaign > $pctserved) continue;
}
if (isset($banners[$row['size']])){
$info = explode(",",$banners[$row['size']]);
$position = $info[1];
}else{
$position="bodyad";
}
if ($types[$position]['priority'] < $row['priority']){
$types[$position]['banners'] = array($row);
$types[$position]['priority'] = $row['priority'];
}elseif ($types[$position]['priority'] == $row['priority']){
if (!isset($types[$position]['banners'])) $types[$position]['banners'] = array();
array_push($types[$position]['banners'],$row);
$types[$position]['priority'] = $row['priority'];
}
}
uasort($types,"advertising_sorttypes");
reset($types);
$totalbanners = 0;
//don't do vertical or body google ads
if (!array_key_exists('verticalad', $types) ||
!array_key_exists('priority', $types['verticalad']) ||
$types['verticalad']['priority']==0) {
unset($types['verticalad']);
}
if (!array_key_exists('bodyad', $types) ||
!array_key_exists('priority', $types['bodyad']) ||
$types['bodyad']['priority']==0) {
unset($types['bodyad']);
}

reset($types);
//collect ads for output
while (list($position,$info)=each($types)){
if ($totalbanners >= get_module_setting("totalads")) break;
if (array_key_exists('banners', $info) && count($info['banners'])>0){
//user-defined banner
$ad = $info['banners'][mt_rand(0,count($info['banners'])-1)];
$ret[$position] = advertising_getad($ad);
}else{
//google banner

//get all banners that fit this space
$avail = array();
reset($banners);
while (list($key,$val)=each($banners)){
$info = explode(",",$val);
if ($info[1]==$position)
array_push($avail,$key);
}
//choose one banner size at random
$info = explode("x",$avail[mt_rand(0,count($avail)-1)]);
//push on the google banner.
//$ret[$position] = advertising_getgoogle($info[0],$info[1]);
$ret[$position] = advertising_getad($info[0]."x".$info[1]);
}
$totalbanners++;
}
return $ret;
}

function advertising_getad($ad){
if (is_array($ad)){
//return("Here's a user ad: ".serialize($ad));
//return "<a href=\"".htmlentities($ad['link'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."' target='_blank'><img src='".$ad['image']."' alt=></a>";
$info = explode("x",$ad['size']);
$id = $ad['bannerid'];
}else{
$info = explode("x",$ad);
$id=false;
}
return advertising_getbanner($info[0],$info[1],$id);
}

function advertising_sorttypes($a,$b){
if ($a['priority'] > $b['priority'])
return 1;
elseif ($a['priority'] < $b['priority'])
return -1;
else //we don't want one type of banner to always override another type at the same priority level.
return mt_rand(0,1) ? -1 : 1 ;
}

function advertising_install(){
require_once("lib/tabledescriptor.php");

module_addhook("everyfooter");
module_addhook("lodge");
module_addhook("superuser");
module_addhook("about");

$bannertable = array(
"bannerid"=>array("type"=>"int(11) unsigned","extra"=>"auto_increment"),
"owner"=>array("type"=>"int(11) unsigned","default"=>"0"),
"size"=>array("type"=>"varchar(20)"),
"start"=>array("type"=>"date","default"=>"0000-00-00"),
"end"=>array("type"=>"date","default"=>"2038-12-31"),
"maximpressions"=>array("type"=>"int(11) unsigned","default"=>"4294967295"),
"servedimpressions"=>array("type"=>"int(11) unsigned","default"=>"0"),
"link"=>array("type"=>"text"),
"image"=>array("type"=>"varchar(255)"),
"priority"=>array("type"=>"tinyint(4)","default"=>"0"),
"clicks"=>array("type"=>"int(11) unsigned","default"=>"0"),
"approved"=>array("type"=>"tinyint(4)","default"=>"" . MOD_ADVERTISING_UNAPPROVED),
"alttext"=>array("type"=>"varchar(255)"),

"key-PRIMARY"=>array("type"=>"primary key","columns"=>"bannerid"),
"key-daterange"=>array("type"=>"key","columns"=>"start,end"),
"key-impressions"=>array("type"=>"key","columns"=>"servedimpressions"),
);
//you can do these tables as $name=>array(descriptor) or array("name"=>$name,{descriptor})
$adlog = array(
"logid"=>array("name"=>"logid","type"=>"int(11) unsigned","extra"=>"auto_increment"),
"bannerid"=>array("name"=>"bannerid","type"=>"int(11) unsigned","default"=>"0"),
"userid"=>array("name"=>"userid","type"=>"int(11) unsigned","default"=>"0"),
"ip"=>array("name"=>"ip","type"=>"varchar(40)","default"=>""),
"id"=>array("name"=>"id","type"=>"varchar(32)","default"=>""),
"date"=>array("name"=>"date","type"=>"datetime","default"=>"0000-00-00 00:00:00"),

"key-PRIMARY"=>array("name"=>"PRIMARY","type"=>"primary key","columns"=>"logid"),
"key-date"=>array("name"=>"date","type"=>"key","columns"=>"date"),
"key-bannerid"=>array("name"=>"bannerid","type"=>"key","columns"=>"bannerid"),
);
synctable(db_prefix("mod_advertising_banners"),$bannertable);
synctable(db_prefix("mod_advertising_log"),$adlog);
//debug(table_create_descriptor("accounts"));
return true;
}

function advertising_uninstall(){
db_query("DROP TABLE ".db_prefix("mod_advertising_banners"));
db_query("DROP TABLE ".db_prefix("mod_advertising_log"));
return true;
}

$mod_advertising_needStyle=true;
function advertising_getbanner($w,$h,$id=false){
if ($id===false){
$src = "runmodule.php?module=advertising&op=getbanner&w=$w&h=$h";
}else{
$src = "runmodule.php?module=advertising&op=getbanner&id=$id";
}
return "<iframe src='$src' width='$w' height='$h' scrolling='no' style='border:0px none;margin:0px;float: left; clear: both;' frameborder='0'></iframe>";
/*return "<div class='modAdvertisingAd' onMouseOver='showAdInfo(this);' onMouseOut='hideAdInfo(this);' style='position: relative;'>"
."<div class='label'><a href='runmodule.php?module=advertising&op=about' target='_blank' onClick=\"".popup("'+this.href+'")."; return false;\">Advertise here</a></div>"
."<iframe src='$src' width='$w' height='$h' scrolling='no' style='border:0px none;margin:0px;float: left; clear: both;' frameborder='0'></iframe>"
."</div>";
*/
}

function advertising_dohook($hookname,$args){
global $session;
if ($hookname=="superuser"){
if ($session['user']['superuser'] & MOD_ADVERTISING_SUAPPROVER){
$sql = "SELECT count(*) AS c FROM ".db_prefix("mod_advertising_banners")." WHERE approved=".MOD_ADVERTISING_UNAPPROVED;
$result = db_query($sql);
$row = db_fetch_assoc($result);
addnav("Mechanics");
addnav(array("Ad Banner Approval (%s)",$row['c']),"runmodule.php?module=advertising&op=bannerapprover",false,true);
}
}elseif ($hookname=="lodge"){
// If they have less than what they need just ignore them
addnav("Advertising Opt-out (varies)","runmodule.php?module=advertising&op=opt-out");
}elseif ($hookname=="about"){
if ($session['user']['superuser'] & MOD_ADVERTISING_SUAPPROVER)
addnav("Advertising on this site","runmodule.php?module=advertising&op=about",false,true);
}elseif ($hookname=="everyfooter"){
// Exclude the installer pages from the advertiser because they break
// badly causing a slowdown when you try to upgrade
if ($args['__scriptfile__'] == "installer") return $args;
global $session;
$crawl = get_module_setting("crawldir");
if ($crawl>""){
$fullfile = basename($session['user']['restorepage']);
if ($fullfile == "") return $args;
if (strpos($fullfile,"?")) {
$file = substr($fullfile,0,strpos($fullfile,"?"));
if ($file == "runmodule.php"){
parse_str(substr($fullfile,strpos($fullfile,"?")+1),$get); //turn arguments into an array.
$file="module.{$get['module']}.php";
}
}else{
$file = $fullfile;
}
$file = $crawl.$file;
$fe = file_exists($file);
if (!$fe || @filemtime($file) < strtotime("-1 hour")){
if ((e_rand(1,20)==1 || !$fe) && strpos($session['user']['output'],"<!--Su_Restricted-->")===false){
$contents = $session['user']['output'];
$contents = preg_replace("/runmodule.php\\?.*module=([^'\"&]*)/","module.\\1.php?",$contents);
$contents = "<?php require_once('../modules/advertising.php');advertising_checkcrawler();?>".$contents;
$fp = fopen($file,"w");
if ($fp){
if (!fwrite($fp,$contents)){
}else{
}
fclose($fp);
}else{
}//end if
}//end if
}//end if
}//end if

//see if they opted out by subscription.
if ($session['loggedin']) {
if (get_module_pref("optouttill") >= date("Y-m-d H:i:s")){
debug("Opt-out by subscription");
return $args;
}else{
//see if they opted out by impressions.
$optout = get_module_pref("optout");
$used = get_module_pref("optoutused");
if ($used < $optout){
set_module_pref("optoutused",$used+1);
return $args;
}
}
}
//they didn't opt out
$banners = advertising_getallads();
reset($banners);
while (list($key,$val)=each($banners)){
if (!isset($args[$key]))
$args[$key] = array();
elseif (!is_array($args[$key]))
$args[$key] = array($args[$key]);
array_push($args[$key],templatereplace("adwrapper",array("content"=>$val)));
}
if (!array_key_exists('script', $args) || !is_array($args['script']))
$args['script']=array();
array_push($args['script'],"
<style type='text/css'>
.modAdvertisingAd div.label {
visibility: hidden;
display: none;
}
.modAdvertisingAdHover div.label {
background-color: #003399;
visibility: visible;
display: inline;
position: absolute;
top: -12px;
float: left;
clear: both;
font-size: 10px;
height: 12px;
}
.modAdvertisingAdHover div.label a {
color: #FFFFFF;
}
</style>
<script language='JavaScript'>
var modAdvertisingInfoTimer;
var modAdvertisingInfoDiv;
function hideAdInfo(div){
window.status='hide';
modAdvertisingInfoDiv = div;
modAdvertisingInfoTimer = setTimeout(\"hideAdInfo_doit(modAdvertisingInfoDiv);\",2000);
}
function showAdInfo(div){
window.status='Show';
if (modAdvertisingInfoTimer){
hideAdInfo_doit(modAdvertisingInfoDiv);
}
div.className=\"modAdvertisingAdHover\";
modAdvertisingInfoDiv = div;
}
function  hideAdInfo_doit(div){
window.status='doit';
div.className=\"modAdvertisingAd\";
clearTimeout(modAdvertisingInfoTimer);
modAdvertisingInfoTimer=false;
}
</script>
");
}
return $args;
}//end function

function advertising_run(){
$op = httpget("op");
if ($op=="opt-out"){
//this page requires the user to be logged in and have this item in their allowed navs.
do_forced_nav(false,false);
page_header("Advertising Opt-out");
$cost = get_module_setting("baseprice");
$what = httpget("what");
$intervals = array(
"1 month"=>600,
"3 months"=>1500,
"6 months"=>2500
);
global $session;
$avail = $session['user']['donation'] - $session['user']['donationspent'];
output("`2Because we recognize that not everyone really appreciates banner advertisements, we provide a method to opt-out of banner ads in exchange for donator points.");
output("`n`n`bImpression-based opt-outs`b`n");
output("The plan is to offer the opt-out at the same rate per impression as advertisers pay.");
output("Because advertisers pay for thousands of users, each user ends up paying a much smaller amount to opt out of advertising than an advertiser would spend on that advertising.`n");
output("`nCurrently the cost per 1000 impressions (page hits) on this site is `@%s`2 donator points.`n",$cost);
output("`n`bSubscription-based opt-outs`b`n");
output("You can also choose to opt out for a period of time.  We offer the following packages:`n");
while(list($key,$val)=each($intervals)){
output("%s for %s points`n",$key,$val);
}
reset($intervals);
$months = httpget("months");
if ($months>""){
$thiscost = $intervals[$months];
if ($avail >= $thiscost){
$session['user']['donationspent']+=$thiscost;
$till = get_module_pref("optouttill");
if ($till < date("Y-m-d H:i:s")) $till = date("Y-m-d H:i:s");
$till = strtotime("+$months",strtotime($till));
$till = date("Y-m-d H:i:s",$till);
set_module_pref("optouttill",$till);
$avail -= $thiscost;
output("`n`^Thank you for supporting LoGD, you have opted out of advertising until %s.",date("M d, Y h:i a",strtotime($till)));
}else{
output("`n`\$Error`2, opting out for %s requires %s donator points, but you only have %s available.`n",$months,$thiscost,$avail);
}
}
if ($what>""){
$thiscost = $cost * $what / 1000;
if ($avail >= $thiscost){
$session['user']['donationspent'] += $thiscost;
set_module_pref("optout",get_module_pref("optout")+$what);
output("`n`^Thank you for supporting LoGD, you have opted out of advertising for `&%s`^ more impressions!`n",$what);
$avail -= $thiscost;
}else{
output("`n`\$Error`2, Opting out on %s impressions requires %s donator points, but you only have %s available.`n",$what,$thiscost,$avail);
}
}
$optout = (int)get_module_pref("optout");
$used = (int)get_module_pref("optoutused");
if (get_module_pref("optouttill") > date("Y-m-d H:i:s")){
output("`n`2You have currently opted out of advertising until `^%s`2.`n",date("M d, Y h:i a",strtotime(get_module_pref("optouttill"))));
}
output("`n`2You currently have purchased `3%s`2 total impressions, and have used `3%s`2 of them, leaving a balance of `#%s`2 impressions before you will see another ad on this site.`n",$optout,$used,($optout-$used));
output("You have `^%s`2 available donator points.",$avail);
addnav("Return to the Lodge","lodge.php");
addnav("Impression Opt-Out");
addnav("1000 impressions ($cost points)","runmodule.php?module=advertising&op=opt-out&what=1000");
addnav("3000 impressions (".($cost*3)." points)","runmodule.php?module=advertising&op=opt-out&what=3000");
addnav("5000 impressions (".($cost*5)." points)","runmodule.php?module=advertising&op=opt-out&what=5000");
addnav("Subscription Opt-Out");
while (list($key,$val)=each($intervals)){
addnav("$key ($val points)","runmodule.php?module=advertising&op=opt-out&months=".rawurlencode($key));
}
page_footer();
}elseif ($op=="about"){
global $session;
popup_header("Advertising on this site");
output("We offer banner advertising on this site.");
output("You may purchase and control your own advertising by using donator points.`n");
if ($session['user']['loggedin']){
output("`n`bManage your banners`b`n");
$sql = "SELECT * FROM ".db_prefix("mod_advertising_banners")." WHERE owner={$session['user']['acctid']}";
$result = db_query($sql);
rawoutput("<table><tr class='trhead'><td rowspan='2'>".tl("Ops")."</td><td rowspan='2'>".tl("Alt Text")."</td><td colspan='2'>".tl("Impressions")."</td><td colspan='2'>".tl("Clicks")."</td><td colspan='2'>".tl("Dates")."</td></tr>");
rawoutput("<tr class='trhead'><td>".tl("Purchased")."</td><td>".tl("Used")."</td><td>".tl("Total")."</td><td>".tl("Rate")."</td><td>".tl("Start")."</td><td>".tl("End")."</td></tr>");
if (db_num_rows($result)==0){
rawoutput("<tr class='trlight'><td colspan='8' align='center'><i>".tl("You have no banners")."</i></td></tr>");
}
$x=0;
while ($row = db_fetch_assoc($result)){
$x++;
rawoutput("<tr class='".($x%2?"trlight":"trdark")."'>");
rawoutput("<td>Ops</td>");
rawoutput("<td nowrap='true' style='overflow: hidden;'>".htmlentities($row['alttext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</td>
");
rawoutput("<td>{$row['maximpressions']}</td>");
rawoutput("<td>{$row['servedimpressions']}</td>");
rawoutput("<td>{$row['clicks']}</td>");
rawoutput("<td>".round(100*$row['clicks']/max(1,$row['servedimpressions']),1)."%</td>");
rawoutput("<td>{$row['start']}</td>");
rawoutput("<td>{$row['end']}</td>");
rawoutput("</tr>");
}
rawoutput("</table>");
$buy = translate_inline("Purchase placement for your banner image");
$upload = translate_inline("Upload a banner image");
output("There are 2 easy steps to purchasing advertising on this site:`n");
output_notl("1&gt; <a href='runmodule.php?module=advertising&op=upload'>`%$upload`0</a><br>",true);
output_notl("2&gt; <a href='runmodule.php?module=advertising&op=buy'>`%$buy`0</a><br>",true);
}else{
output("`iLog in to manage your advertisements`i`n");
}

output("`nWe give a lot of control over how your ads are displayed:<ul>",true);
output("<li>Set limits on your advertisement:<ul>",true);
output("<li>Limit how much you're willing to spend</li>",true);
output("<li>Optionally set start and end dates for your ad</li>",true);
output("<li>Upload your own ad images</li>",true);
output("<li>Upload GIF, PNG, JPG or SWF files</li>",true);
output("</ul></li>",true);
output("<li>Track the performance of your advertisements on this site</li>",true);
output("<li>Use donator points to buy your ads</li>",true);
output("<li>Purchasing is done on a CPM* basis</li>",true);
output("</ul>",true);
output("There are a few terms and conditions to purchasing advertising on this site:<ul>",true);
output("<li>You may not advertise for content or services which are inappropriate for underaged children</li>",true);
output("<li>Your banners may not display content which is inappropriate for underaged children</li>",true);
output("<li>You agree that this site and its authors, owners, and administrators are not liable for server downtime, whether it is scheduled or not</li>",true);
output("<li>You agree that all advertisements submitted here are subject to review and approval by the site's staff.  Advertising which is denied will have its unused balance refunded</li>",true);
output("<li>You agree that rejection of advertisements occurs solely at the discression of site staff, and may include reasons beyond those explicitly listed on this site.</li>",true);
output("</ul>",true);

output("*CPM stands for 1000 advertising impressions`n");

popup_footer();
}elseif ($op=="upload"){
global $session;
$banner_list = advertising_getbanners();
if (isset($_FILES['bannerimage'])){
debug($_FILES);
$maxsize=0;
while (list($key,$val)=each($banner_list)){
$size = explode("x",$key);
$w = $size[0];
$h = $size[1];
$info = explode(",",$val);
$size = round(($w*$h)/get_module_setting("bytespp"),1);
if ($size > $maxsize) $maxsize=$size;
}
$filesize = filesize($_FILES['bannerimage']['tmp_name']);
if ($filesize > $maxsize){
output("The file you uploaded was %sK, while the largest banner we accept is %sK.  Please try again.`n`n",round($filesize/1024,1),round($maxsize/1024,1));
}else{
$ext = @substr($_FILES['bannerimage']['name'],strrpos($_FILES['bannerimage']['name'],'.'));
if ($ext=="") $ext = ".png";
$uploadfile = "modules/advertising/ad-".rawURLEncode($session['user']['acctid'])."-".date("YmdHis").$ext;
if (move_uploaded_file($_FILES['bannerimage']['tmp_name'], $uploadfile)){
$info = getimagesize($uploadfile);
debug($info);
rawoutput("<img src='$uploadfile' width='{$info[0]}' height='{$info[1]}'>");
}else{
output("`\$`bError`b`0: Unable to process your uploaded image, please try again.`n`n");
}
}
}
popup_header("Upload an Image");
$banners="";
rawoutput("<form action='runmodule.php?module=advertising&op=upload' enctype='multipart/form-data' method='POST'>");
output("`bStep 1:`b Upload your banner`n");
output("We accept the following banner sizes (click for a preview):`n");
rawoutput("
<div id='bannerPreview' style='background-color: #000000; border: 1px dotted #FFFFFF; visibility: hidden; display: none; color: #FFFFFF; width: 100px; float: left;'>This is a preview of your banner space</div>
<div style='clear: both;'></div>
<div id='bannerPreviewTall' style='background-color: #000000; border: 1px dotted #FFFFFF; visibility: hidden; display: none; float: right; color: #FFFFFF;'>This is a preview of your banner space</div>
<script language='JavaScript'>
function doSize(w,h){
if (h < w*1.2){
var div = document.getElementById('bannerPreview');
var off = document.getElementById('bannerPreviewTall');
}else{
var div = document.getElementById('bannerPreviewTall');
var off = document.getElementById('bannerPreview');
}
div.style.visibility = 'visible';
div.style.display='inline';
div.style.width = w;
div.style.height = h;

off.style.visibility = 'hidden';
off.style.display='none';
}
</script>");
rawoutput("<ul>");
$places = array("headerad"=>"page header","navad"=>"navigational area","bodyad"=>"page body","verticalad"=>"right-aligned in page body");
reset($banner_list);
while (list($key,$val)=each($banner_list)){
$size = explode("x",$key);
$w = $size[0];
$h = $size[1];
$info = explode(",",$val);
$size = round(($w*$h)/get_module_setting("bytespp")/1024,1)."K";
$cost = round(get_module_setting("baseprice") * $info[2]/100,2);
$val = explode(",",$val);
$name = $val[0];
output("<li onClick='doSize($w,$h);' style='cursor: pointer; cursor: hand;'>`^{$w}x$h &#151; `@$name `3(&lt;= $size)`2. CPM: `5$cost USD`2. Placement: `#{$places[$info[1]]}`0</li>
",true);
}
rawoutput("</ul>");
output("Choose a file:");
rawoutput("<input type='file' name='bannerimage'>");
$upload = translate_inline("Upload");
tlbutton_clear();
rawoutput("<input type='submit' value='$upload' class='button'>");
rawoutput("</form>");
popup_footer();
}elseif ($op=="buy"){
popup_header("Buy/Modify Advertisement");
require_once("lib/showform.php");
$banner_list = advertising_getbanners();
$banners="";
while (list($key,$val)=each($banner_list)){
$size = explode("x",$key);
$w = $size[0];
$h = $size[1];
$info = explode(",",$val);
$size = round(($w*$h)/get_module_setting("bytespp")/1024,1)."K";
$cost = round(get_module_setting("baseprice") * $info[2]/100,2);
$val = explode(",",$val);
$name = $val[0];
$banners.=",{$w}x{$h},$name ($w x $h; <= $size) CPM: $cost USD";
}
if (httpget("id")>""){
$result = db_query("SELECT * FROM ".db_prefix("mod_advertising_banners")." WHERE bannerid='".httpget("id")."' AND owner={$session['user']['id']}");
$row = db_fetch_assoc($result);
}
$d = dir("modules/advertising/");
global $session;
while (false !== ($entry = $d->read())){
if (is_file("modules/advertising/$entry")){
$a = explode("-",$entry);
if ($a[1]==$session['user']['acctid']){
$images .= ",$entry,<div style='overflow: auto; width: 400px; max-height: 200px;'><img src='modules/advertising/$entry' align='middle'></div>";
}else{

}
}
}
$form = array(
"Advertisement Details,title",
"bannerid"=>"Banner id,hidden",
"size"=>"Banner Size,enum$banners",
"image"=>"Banner Image,radio$images",
"link"=>"Link",
"alttext"=>"Alt text",
"Limits,title",
"maximpressions"=>"Maximum impressions (in whole thousands only)",
"start"=>"Banner start date (leave blank for today)",
"end"=>"Banner end date (leave blank to run until you're out of impressions)",
);
rawoutput("<form action='runmodule.php?module=advertising&op=save' method='POST'>");
showform($form,$row,true);
$buy = translate_inline("Purchase");
rawoutput("<input type='submit' value='$buy' class='button'>");
rawoutput("</form>");
popup_footer();
}elseif($op=="save"){
popup_header("Buy/Modify Advertising");
$image = preg_replace("/[^a-zA-Z0-9.-]/","",httppost("image"));
$size = getimagesize("modules/advertising/$image");
output(serialize($size));
$size = $size[0]."x".$size[1];
$start = date("Y-m-d",strtotime(httppost("start")));
if ($start < date("Y-m-d")) $start=date("Y-m-d");
$end = date("Y-m-d",httppost("end"));
if ($end < date("Y-m-d")) $end = "2038-12-31";
$maximpressions = (int)httppost("maximpressions");
$link = httppost("link");
$priority=1; //for now, there's no option on priority.
$approved = MOD_ADVERTISING_UNAPPROVED;
$alttext = httppost("alttext");
global $session;
$sql = "INSERT INTO " . db_prefix("mod_advertising_banners") . " (
owner,
size,
start,
end,
maximpressions,
link,
priority,
approved,
alttext,
image
) VALUES (
'{$session['user']['acctid']}',
'$size',
'$start',
'$end',
'$maximpressions',
'$link',
'$priority',
'$approved',
'$alttext',
'$image'
)";
db_query($sql);
output("Your advertising has been purchased, and is pending approval.");
popup_footer();
}elseif ($op=="bannerapprover"){
check_su_access(MOD_ADVERTISING_SUAPPROVER);

if (httpget("id")>""){
$sql = "UPDATE " . db_prefix("mod_advertising_banners") . " SET approved='".httpget("approve")."' WHERE bannerid='".httpget("id")."'";
db_query($sql);
output("Status Updated`n`n");
}
popup_header("Banner Approval");
$sql = "SELECT " . db_prefix("mod_advertising_banners").".*, ". db_prefix("accounts").".name FROM ".db_prefix("mod_advertising_banners") ." INNER JOIN ".db_prefix("accounts") . " ON " . db_prefix("mod_advertising_banners") . ".owner = " . db_prefix("accounts") . ".acctid ORDER BY approved, start";
$result = db_query($sql);
rawoutput("<table cellspacing='0'>");
$ops = translate_inline("Ops");
$status = translate_inline("Status");
$size = translate_inline("Size");
$owner = translate_inline("Owner");
$link = translate_inline("Link");
$alt = translate_inline("Alt");
$image = translate_inline("Image");
$approve = translate_inline("Approve");
$deny = translate_inline("Deny");
rawoutput("<tr class='trhead'><td>$Ops</td><td>$status</td><td>$size</td><td>$owner</td><td>$link</td><td>$alt</td><td>$image</td></tr>");
$x=0;
while ($row = db_fetch_assoc($result)){
rawoutput("<tr class='".($x%2?"trlight":"trdark")."'>");
rawoutput("<td>[ <a href='runmodule.php?module=advertising&op=bannerapprover&approve=".MOD_ADVERTISING_APPROVED."&id={$row['bannerid']}'>$approve</a>");
rawoutput(" | <a href='runmodule.php?module=advertising&op=bannerapprover&approve=".MOD_ADVERTISING_DENIED."&id={$row['bannerid']}'>$deny</a> ]");
rawoutput("</td><td>");
output(
$row['approved']==MOD_ADVERTISING_APPROVED ? "`^Approved`0" :
($row['approved']==MOD_ADVERTISING_DENIED ? "`\$Denied`0" :
"`#UnApproved`0"
)
);
rawoutput("</td><td>");
output_notl($row['size']);
rawoutput("</td><td>");
output_notl($row['name']);
rawoutput("</td><td style='max-width: 25%; overflow: auto;'>");
rawoutput("<a href=\"".htmlentities($row['link'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" target='_blank'>".htmlentities($row['link'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</a>");
rawoutput("</td><td><div style='max-width: 200px; max-height: 200px; overflow: auto;'>");
output_notl($row['alttext']);
rawoutput("</div></td><td><div style='max-width: 200px; max-height: 200px; overflow: auto;'>");
rawoutput("<img src='modules/advertising/{$row['image']}'>");
rawoutput("</div></td>");
rawoutput("</tr>");
$x++;
}
rawoutput("</table>");
popup_footer();
}elseif ($op=="click"){
$id = httpget("id");
$result = db_query("SELECT link FROM " . db_prefix("mod_advertising_banners") . " WHERE bannerid='$id'");
if ($row = db_fetch_assoc($result)){
db_query("UPDATE " . db_prefix("mod_advertising_banners") . " SET clicks=clicks+1 WHERE bannerid='$id'");
global $session;
$aid = (int)$session['user']['acctid'];
db_query("INSERT INTO " . db_prefix("mod_advertising_log") . " (bannerid,date,userid,ip,id) VALUES ('$id','".date("Y-m-d H:i:s")."',$aid,'{$session['user']['lastip']}','{$session['user']['uniqueid']}')");
header("Location: {$row['link']}");
}else{
header("Location: ./");
}
exit();
}elseif ($op=="getbanner"){
//serve a banner if we have one.
$id = httpget("id");
if ($id > ""){
$result = db_query("SELECT image,alttext FROM " . db_prefix("mod_advertising_banners") . " WHERE bannerid='$id'");
if ($row = db_fetch_assoc($result)){
$banner = "<a href='runmodule.php?module=advertising&op=click&id=$id' target='_blank'><img src=\"modules/advertising/".htmlentities($row['image'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" border='0' alt=\"".htmlentities($row['alttext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" title=\"".htmlentities($row['alttext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></a>";
db_query("UPDATE " . db_prefix("mod_advertising_banners") . " SET servedimpressions=servedimpressions+1 WHERE bannerid='$id'");
}else{
$banner="";
}
}else{
$w = httpget("w");
$h = httpget("h");
$banner_types = advertising_getbanners();
$type = $w."x".$h;
if (isset($banner_types[$type])){
$genericbanners = modulehook("mod_ad_banner",array("size"=>$type,"w"=>$w,"h"=>$h,"banners"=>array()));
//remove keys if any
sort($genericbanners['banners']);
if (count($genericbanners['banners'])>0){
$banner = $genericbanners['banners'][e_rand(0,count($genericbanners['banners'])-1)];
}else{
$banner = "";
}
//echo htmlentities(serialize($genericbanners['banners']), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
}else{
$banner="";
}
}
echo "<html><head><style type='text/css'>body { margin: 0px; }</style><base target='_blank'></head><body>".$banner."</body></html>";
set_module_pref("impressions",get_module_pref("impressions")+1);
exit();
}
}

function advertising_getUploadedImages(){
$path = "modules/advertising/";
$d = dir($path);
global $session;
$me = "ad-".$session['user']['acctid']."-";
$l = strlen($me);
$out = array();
while (($entry = $d->read()) !==false){
if (substr($entry,0,$l)==$me){
if ($extended){
$out[$entry]=getimagesize($path.$entry);
$out[$entry]['filesize'] = filesize($path.$entry);
$out[$entry]['filemtime'] = filemtime($path.$entry);
}else{
$out[$entry]=array();
}
}
}
return $out;
}

function advertising_checkcrawler(){
$haystack=$_SERVER['HTTP_USER_AGENT'];
$bots=array('bot', 'slurp', 'grub', 'crawl', 'scan', 'szukacz', 'mozilla');
while (list($key,$needle)=each($bots)){
if (stristr($haystack,$needle)){
//try to bounce out people viewing a google cache.
echo "<script language='JavaScript'>var x=1; if (x) document.write('<scr'+'ipt language=\"JavaScript\">document.location=\"../\";</scr'+'ipt>');</script>";
echo($_SERVER['HTTP_USER_AGENT']);
return true;
}
}
header("Location: ../");
exit();
}
?>

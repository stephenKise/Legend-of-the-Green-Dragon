<?php
// translator ready
// addnews ready
// mail ready

if (isset($_POST['template'])){
	$skin = $_POST['template'];
	if ($skin > "") {
		setcookie("template",$skin ,strtotime("+45 days"));
		$_COOKIE['template']=$skin;
	}
}

define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");


if (!isset($session['loggedin'])) $session['loggedin']=false;
if ($session['loggedin']){
	redirect("badnav.php");
}

tlschema("home");

$op = httpget('op');

page_header();
output("`cWelcome to Legend of the Green Dragon, a browser based role playing game, based on Seth Able's Legend of the Red Dragon.`n");

if (getsetting("homecurtime", 1)) {
	output("`@The current time in %s is `%%s`@.`0`n", getsetting("villagename", LOCATION_FIELDS), getgametime());
}

if (getsetting("homenewdaytime", 1)) {
	$secstonewday = secondstonextgameday();
	output("`@Next new game day in: `\$%s (real time)`0`n`n",
			date("G\\".translate_inline("h","datetime").", i\\".translate_inline("m","datetime").", s\\".translate_inline("s","datetime"),
				$secstonewday));
}

if (getsetting("homenewestplayer", 1)) {
	$name = "";
	$newplayer = getsetting("newestplayer", "");
	if ($newplayer != 0) {
		$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$newplayer'";
		$result = db_query_cached($sql, "newest");
		$row = db_fetch_assoc($result);
		$name = $row['name'];
	} else {
		$name = $newplayer;
	}
	if ($name != "") {
		output("`QThe newest resident of the realm is: `&%s`0`n`n", $name);
	}
}

clearnav();
addnav("New to LoGD?");
addnav("Create a character","create.php");
addnav("Game Functions");
addnav("Forgotten Password","create.php?op=forgot");
addnav("List Warriors","list.php");
addnav("Daily News", "news.php");
addnav("Other Info");
addnav("About LoGD","about.php");
addnav("Game Setup Info", "about.php?op=setup");
addnav("LoGD Net","logdnet.php?op=list");

modulehook("index", array());

if (abs(getsetting("OnlineCountLast",0) - strtotime("now")) > 60){
	$sql="SELECT count(acctid) as onlinecount FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".getsetting("LOGINTIMEOUT",900)." seconds"))."'";
	$result = db_query($sql);
	$onlinecount = db_fetch_assoc($result);
	$onlinecount = $onlinecount ['onlinecount'];
	savesetting("OnlineCount",$onlinecount);
	savesetting("OnlineCountLast",strtotime("now"));
}else{
	$onlinecount = getsetting("OnlineCount",0);
}
if ($onlinecount<getsetting("maxonline",0) || getsetting("maxonline",0)==0){
	output("Enter your name and password to enter the realm.`n");
	if ($op=="timeout"){
		$session['message'].= translate_inline(" Your session has timed out, you must log in again.`n");
	}
	if (!isset($_COOKIE['lgi'])){
		$session['message'].=translate_inline("It appears that you may be blocking cookies from this site.  At least session cookies must be enabled in order to use this site.`n");
		$session['message'].=translate_inline("`b`#If you are not sure what cookies are, please <a href='http://en.wikipedia.org/wiki/WWW_browser_cookie'>read this article</a> about them, and how to enable them.`b`n");
	}
	if (isset($session['message']) && $session['message']>"")
		output_notl("`b`\$%s`b`n", $session['message'],true);
	rawoutput("<script language='JavaScript' src='lib/md5.js'></script>");
	rawoutput("<script language='JavaScript'>
	<!--
	function md5pass(){
		//encode passwords before submission to protect them even from network sniffing attacks.
		var passbox = document.getElementById('password');
		if (passbox.value.substring(0, 5) != '!md5!') {
			passbox.value = '!md5!' + hex_md5(passbox.value);
		}
	}
	//-->
	</script>");
	$uname = translate_inline("<u>U</u>sername");
	$pass = translate_inline("<u>P</u>assword");
	$butt = translate_inline("Log in");
	rawoutput("<form action='login.php' method='POST' onSubmit=\"md5pass();\">".templatereplace("login",array("username"=>$uname,"password"=>$pass,"button"=>$butt))."</form>");
	output_notl("`c");
	addnav("","login.php");
} else {
	output("`\$`bServer full!`b`n`^Please wait until some users have logged out.`n`n`0");
	if ($op=="timeout"){
		$session['message'].= translate_inline(" Your session has timed out, you must log in again.`n");
	}
	if (!isset($_COOKIE['lgi'])){
		$session['message'].=translate_inline("It appears that you may be blocking cookies from this site. At least session cookies must be enabled in order to use this site.`n");
		$session['message'].=translate_inline("`b`#If you are not sure what cookies are, please <a href='http://en.wikipedia.org/wiki/WWW_browser_cookie'>read this article</a> about them, and how to enable them.`b`n");
	}
	if ($session['message']>"") output("`b`\$%s`b`n", $session['message'],true);
	rawoutput(templatereplace("loginfull",array()));
	output_notl("`c");
}

$msg = getsetting("loginbanner","*BETA* This is a BETA of this website, things are likely to change now and again, as it is under active development *BETA*");
output_notl("`n`c`b`&%s`0`b`c`n", $msg);
$session['message']="";
output("`c`2Game server running version: `@%s`0`c", $logd_version);

if (getsetting("homeskinselect", 1)) {
	rawoutput("<form action='home.php' method='POST'>");
	rawoutput("<table align='center'><tr><td>");
	$form = array("template"=>"Choose a different display skin:,theme");
	$prefs['template'] = $_COOKIE['template'];
	if ($prefs['template'] == "")
		$prefs['template'] = getsetting("defaultskin", "jade.htm");
	require_once("lib/showform.php");
	showform($form, $prefs, true);
	$submit = translate_inline("Choose");
	rawoutput("</td><td><br>&nbsp;<input type='submit' class='button' value='$submit'></td>");
	rawoutput("</tr></table></form>");
}

page_footer();
?>
<?php
// translator ready
// addnews ready
// mail ready

if (isset($_POST['template'])) {
	$skin = $_POST['template'];
	if ($skin > '') {
		setcookie('template', $skin ,strtotime('+45 days'));
		$_COOKIE['template'] = $skin;
	}
}

define("ALLOW_ANONYMOUS", true);
require_once("common.php");
require_once("lib/http.php");

if (!getSession('message'))
	$session['message'] = '';

if (!isset($session['loggedin'])) $session['loggedin']=false;
if ($session['loggedin']){
	redirect("badnav.php");
}


$op = httpget('op');
if ($op=="timeout") {
	$session['message'] .= loadTranslation("home.timeout");
}

page_header(loadTranslation('common.title'));
output('home.description');

if (getsetting('homecurtime', 1)) {
	output('home.time', getsetting('villagename', LOCATION_FIELDS), getgametime());
}

if (getsetting('homenewdaytime', 1)) {
	output(
        'home.newday_time',
		date('G\\h, i\\m, s\\s', secondstonextgameday())
    );
}

if (getsetting('homenewestplayer', 1)) {
	$name = '';
	$newplayer = getsetting('newestplayer', 1);
	if ($newplayer != '') {
		$sql = "SELECT name FROM " . db_prefix('accounts') . " WHERE acctid='$newplayer'";
		$result = db_query_cached($sql, 'newest');
		$row = db_fetch_assoc($result);
		$name = $row['name'];
	} else {
		$name = $newplayer;
	}
	if ($name != '') {
		output('home.newest_player', $name);
	}
}

clearnav();
addnav(loadTranslation('home.nav_headers.new'));
addnav(loadTranslation('home.navs.create'), "create.php");
addnav(loadTranslation('home.nav_headers.about_game'));
addnav(loadTranslation('home.navs.forgot'),"create.php?op=forgot");
addnav(loadTranslation('home.navs.list_characters'), 'list.php');
addnav(loadTranslation('home.navs.news'), "news.php");
addnav(loadTranslation('home.nav_headers.info'));
addnav(loadTranslation('home.navs.about'), "about.php");
addnav(loadTranslation('home.navs.setup_info'), "about.php?op=setup");
addnav(loadTranslation('home.navs.lotgdnet'),"logdnet.php?op=list");

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
	if ($op=="timeout"){
		$session['message'].= loadTranslation('home.timeout');
	}
	if (!isset($_COOKIE['lgi'])){
		$session['message'].= loadTranslation('home.no_cookie');
	}
	if (isset($session['message']) && $session['message']>"")
		output_notl("`b`\$%s`b`n", $session['message'],true);
    output(
        'home.login_form',
        templatereplace('login', [
            'username' => loadTranslation('home.login_username'),
            'password' => loadTranslation('home.login_password'),
            'button'   => loadTranslation('home.login_submit'),
        ]),
        true
    );
	output('home.login_form_javascript', true);
	addnav('', 'login.php');
} else {
	output('home.server_full');
	if (!isset($_COOKIE['lgi'])) {
		$session['message'] .= loadTranslation('home.no_cookie');
	}
	rawoutput(templatereplace('loginfull', []));
}
if ($session['message'] > '') output_notl("`c`b`\$%s`b`c`n", $session['message'],true);

output('home.banner');
output("home.game_version", $logd_version);

if (getsetting('homeskinselect', 1)) {
	rawoutput("<form action='home.php' method='POST'>");
	rawoutput("<table align='center'><tr><td>");
	$form = ["template" => "Choose a different display skin:,theme"];
	$prefs['template'] = $_COOKIE['template'];
	require_once("lib/showform.php");
	showform($form, $prefs, true);
    // output(
    //     'home.skin_select',
    //     join(showform($form, $prefs, true)),
    //     true
    // );
	$submit = translate_inline("Choose");
	rawoutput("</td><td><br>&nbsp;<input type='submit' class='button' value='$submit'></td>");
	rawoutput("</tr></table></form>");

}

unset($session['message']);
page_footer();

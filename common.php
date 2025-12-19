<?php
// **** NOTICE ****
// This series of scripts (collectively known as Legend of the Green Dragon
// or LotGD) is copyright as per below.
// You are prohibited by law from removing or altering this copyright
// information in any fashion except as follows:
//		if you have added functionality to the code, you may append your
// 		name at the end indicating which parts are copyright by you.
// Eg:
// Copyright 2002-2004, Game: Eric Stevens & JT Traub, modified by Your Name
$copyright = "Game Design and Code: Copyright &copy; 2002-2005, Eric Stevens & JT Traub, &copy; 2006-2007, Dragonprime Development Team";
// **** NOTICE ****
// This series of scripts (collectively known as Legend of the Green Dragon
// or LotGD) is copyright as per above.   Read the above paragraph for
// instructions regarding this copyright notice.

// **** NOTICE ****
// This series of scripts (collectively known as Legend of the Green Dragon
// or LotGD) is licensed according to the Creating Commons Attribution
// Non-commercial Share-alike license.  The terms of this license must be
// followed for you to legally use or distribute this software.   This
// license must be used on the distribution of any works derived from this
// work.  This license text may not be removed nor altered in any way.
// Please see the file LICENSE for a full textual description of the license.
$license = "\n<!-- Creative Commons License -->\n<a rel='license' href='http://creativecommons.org/licenses/by-nc-sa/2.0/' target='_blank'><img clear='right' align='left' alt='Creative Commons License' border='0' src='images/somerights20.gif' /></a>\nThis work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-nc-sa/2.0/' target='_blank'>Creative Commons License</a>.<br />\n<!-- /Creative Commons License -->\n<!--\n  <rdf:RDF xmlns='http://web.resource.org/cc/' xmlns:dc='http://purl.org/dc/elements/1.1/' xmlns:rdf='http://www.w3.org/1999/02/22-rdf-syntax-ns#'>\n	<Work rdf:about=''>\n	  <dc:type rdf:resource='http://purl.org/dc/dcmitype/Interactive' />\n	  <license rdf:resource='http://creativecommons.org/licenses/by-nc-sa/2.0/' />\n	</Work>\n	<License rdf:about='http://creativecommons.org/licenses/by-nc-sa/2.0/'>\n	  <permits rdf:resource='http://web.resource.org/cc/Reproduction' />\n	  <permits rdf:resource='http://web.resource.org/cc/Distribution' />\n	  <requires rdf:resource='http://web.resource.org/cc/Notice' />\n	  <requires rdf:resource='http://web.resource.org/cc/Attribution' />\n	  <prohibits rdf:resource='http://web.resource.org/cc/CommercialUse' />\n	  <permits rdf:resource='http://web.resource.org/cc/DerivativeWorks' />\n	  <requires rdf:resource='http://web.resource.org/cc/ShareAlike' />\n	</License>\n  </rdf:RDF>\n-->\n";
// .... NOTICE *****
// This series of scripts (collectively known as Legend of the Green Dragon
// or LotGD) is licensed according to the Creating Commons Attribution
// Non-commercial Share-alike license.  The terms of this license must be
// followed for you to legally use or distribute this software.   This
// license must be used on the distribution of any works derived from this
// work.  This license text may not be removed nor altered in any way.
// Please see the file LICENSE for a full textual description of the license.

$logd_version = "1.1.2 Dragonprime Edition";

//start the gzip compression
//ob_start('ob_gzhandler');

// Include some commonly needed and useful routines
require_once("lib/local_config.php");
require_once("lib/dbwrapper.php");
require_once("lib/holiday_texts.php");
require_once("lib/sanitize.php");
require_once("lib/constants.php");
require_once("lib/datacache.php");
require_once("lib/modules.php");
require_once("lib/http.php");
require_once("lib/e_rand.php");
require_once("lib/buffs.php");
require_once("lib/template.php");
require_once("lib/settings.php");
require_once("lib/pageparts.php");
require_once("lib/output.php");
require_once("lib/tempstat.php");
require_once("lib/su_access.php");
require_once("lib/datetime.php");
require_once("lib/translator.php");


//mt_srand(make_seed());
$pagestarttime = getmicrotime();

// Set some constant defaults in case they weren't set before the inclusion of
// common.php
if(!defined("OVERRIDE_FORCED_NAV")) define("OVERRIDE_FORCED_NAV",false);
if(!defined("ALLOW_ANONYMOUS")) define("ALLOW_ANONYMOUS",false);

//Initialize variables required for this page

require_once("lib/redirect.php");
require_once("lib/censor.php");
require_once("lib/saveuser.php");
require_once("lib/addnews.php");
require_once('lib/villagenav.php');
require_once("lib/sql.php");
require_once("lib/mounts.php");
require_once("lib/debuglog.php");
require_once("lib/forcednavigation.php");
require_once("lib/php_generic_environment.php");
require_once('lib/checkban.php');
require_once('lib/translations.php');
require_once('vendor/autoload.php');

session_start();
$session = [];
$session =& $_SESSION['session'];
require_once('lib/session.php');
php_generic_environment();

// lets us provide output in dbconnect.php that only appears if there's a
// problem connecting to the database server.  Useful for migration moves
// like LotGD.net experienced on 7/20/04.
ob_start();
if (file_exists("dbconnect.php")){
	require_once("dbconnect.php");
    define('IS_INSTALLER', false);
	$link = db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
} else if (!defined("IS_INSTALLER")) {
 	if (!defined("DB_NODB")) define("DB_NODB",true);
 	page_header("The game has not yet been installed");
	output("`#Welcome to `@Legend of the Green Dragon`#, a game by Eric Stevens & JT Traub.`n`n");
	output("You must run the game's installer, and follow its instructions in order to set up LoGD.  You can go to the installer <a href='installer.php'>here</a>.",true);
	output("`n`nIf you're not sure why you're seeing this message, it's because this game is not properly configured right now. ");
	output("If you've previously been running the game here, chances are that you lost a file called '`%dbconnect.php`#' from your site.");
	output("If that's the case, no worries, we can get you back up and running in no time, and the installer can help!");
	addnav("Game Installer","installer.php");
	page_footer();
}
// If you are running a server that has high overhead to *connect* to your
// database (such as a high latency network connection to mysql),
// reversing the commenting of the following two code lines may significantly
// increase your overall performance.  Pconnect uses more server resources though.
// For more details, see
// http://php.net/manual/en/features.persistent-connections.php
//
//$link = db_pconnect($DB_HOST, $DB_USER, $DB_PASS);
//$link = db_connect($DB_HOST, $DB_USER, $DB_PASS);

$out = ob_get_contents();
ob_end_clean();
unset($DB_HOST);
unset($DB_USER);
unset($DB_PASS);
unset($DB_NAME);
if (isset($link) && $link===false){
 	if (!defined("IS_INSTALLER")){
		echo $out;
		// And tell the user it died.  No translation here, we need the DB for
		// translation.
	 	if (!defined("DB_NODB")) define("DB_NODB",true);
		page_header("Database Connection Error");
		output("Unable to connect to the database server.  Sorry it didn't work out.");
		page_footer();
	}
	define("DB_CONNECTED",false);
}else{
	define("DB_CONNECTED",true);
}

if (isset($DB_NAME) && (!DB_CONNECTED || !$DB_NAME || !db_select_db($DB_NAME))){
	if (!defined("IS_INSTALLER") && DB_CONNECTED){
		// And tell the user it died.  No translation here, we need the DB for
		// translation.
	 	if (!defined("DB_NODB")) define("DB_NODB",true);
		page_header("Database Connection Error");
		output("I was able to connect to the database server, but couldn't connect to the specified database.  Sorry it didn't work out.");
		page_footer();
	}
	define("DB_CHOSEN",false);
}
else {
	if (isset($DB_NAME))
		define('LINK', $DB_NAME);
		else
		define('LINK', false);
	define("DB_CHOSEN",true);
}

	if (defined('IS_INSTALLER') == false) {
header("Content-Type: text/html; charset=".getsetting('charset','ISO-8859-1'));

if (strtotime("-".getsetting("LOGINTIMEOUT",900)." seconds") > $session['lasthit'] && $session['lasthit']>0 && $session['loggedin']){
	// force the abandoning of the session when the user should have been
	// sent to the fields.
	$session=array();
	// technically we should be able to translate this, but for now,
	// ignore it.
	// 1.1.1 now should be a good time to get it on with it, added tl-inline
	translator_setup();
	$session['message'].=translate_inline("`nYour session has expired!`n","common");
}
$session['lasthit'] = date('Y-m-d H:i:s');
mass_module_prepare([
	'template-header',
	'template-footer',
	'template-statstart',
	'template-stathead',
	'template-statrow',
	'template-statbuff',
	'template-statend',
	'template-navhead',
	'template-navitem',
	'template-petitioncount',
	'template-adwrapper',
	'template-login',
	'template-loginfull',
	'everyhit',
			(isset($script) ? "header-$script" : ''),
			(isset($script) ? "footer-$script" : ''),
	'holiday',
	'collapse{',
	'collapse-nav{',
	'}collapse-nav',
	'}collapse',
	'charstats'
]);
	}
$cp = $copyright;
$l = $license;
do_forced_nav(ALLOW_ANONYMOUS,OVERRIDE_FORCED_NAV);

$script = substr($SCRIPT_NAME,0,strrpos($SCRIPT_NAME,"."));

// In the event of redirects, we want to have a version of their session we
// can revert to:
$revertsession=$session;
if (!isset($session['user']['loggedin'])) $session['user']['loggedin']=false;
if (!$session['user']['loggedin']) $session['loggedin'] = false;
else $session['loggedin'] = true;

if ($session['user']['loggedin']!=true && !ALLOW_ANONYMOUS){
	redirect("login.php?op=logout");
}

if (!isset($session['user']['gentime'])) $session['user']['gentime'] = 0;
if (!isset($session['user']['gentimecount'])) $session['user']['gentimecount'] = 0;
if (!isset($session['user']['gensize'])) $session['user']['gensize'] = 0;
if (!isset($session['user']['acctid'])) $session['user']['acctid'] = 0;
if (!isset($session['counter'])) $session['counter']=0;
$session['counter']++;
$nokeeprestore=array("newday.php"=>1,"badnav.php"=>1,"motd.php"=>1,"mail.php"=>1,"petition.php"=>1);
if (OVERRIDE_FORCED_NAV) $nokeeprestore[$SCRIPT_NAME]=1;
if (!isset($nokeeprestore[$SCRIPT_NAME]) || !$nokeeprestore[$SCRIPT_NAME]) {
  $session['user']['restorepage']=$REQUEST_URI;
}else{

}
if ($logd_version != getsetting('installer_version', '-1') && !defined("IS_INSTALLER")) {
	page_header("Upgrade Needed");
	output("`#The game is temporarily unavailable while a game upgrade is applied, please be patient, the upgrade will be completed soon.");
	output("In order to perform the upgrade, an admin will have to run through the installer.");
	output("If you are an admin, please <a href='installer.php'>visit the Installer</a> and complete the upgrade process.`n`n",true);
	output("`@If you don't know what this all means, just sit tight, we're doing an upgrade and will be done soon, you will be automatically returned to the game when the upgrade is complete.");
	rawoutput("<meta http-equiv='refresh' content='30; url={$session['user']['restorepage']}'>");
	addnav("Installer (Admins only!)","installer.php");
	define("NO_SAVE_USER",true);
	page_footer();
}

if (getSessionUser('hitpoints') > 0) {
	$session['user']['alive'] = true;
}
else {
	$session['user']['alive'] = false;
}
if (getSession('bufflist'))
	$session['bufflist'] = unserialize($session['user']['bufflist']);
else
	$session['bufflist'] = [];
if (!is_array(getSession('bufflist'))) $session['bufflist'] = [];
$session['user']['lastip']=$REMOTE_ADDR;
$u = md5(microtime());
if (isset($_COOKIE['lgi']) && strlen($_COOKIE['lgi'])<32){
	if (strlen($session['user']['uniqueid'])<32){
		setcookie("lgi",$u,strtotime("+365 days"));
		$_COOKIE['lgi']=$u;
		$session['user']['uniqueid']=$u;
	}else{
		setcookie("lgi",$session['user']['uniqueid'],strtotime("+365 days"));
	}
}
else if (isset($_COOKIE['lgi'])) {
	$session['user']['uniqueid'] = $_COOKIE['lgi'];
}
else {
    $_COOKIE['lgi'] = $u;
	$session['user']['uniqueid'] = $u; 
}
$url = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']);
$url = substr($url,0,strlen($url)-1);
$urlport = "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].dirname($_SERVER['REQUEST_URI']);
$urlport = substr($urlport,0,strlen($urlport)-1);

if (!isset($_SERVER['HTTP_REFERER'])) $_SERVER['HTTP_REFERER'] = "";

if (
	substr($_SERVER['HTTP_REFERER'],0,strlen($url))==$url ||
	substr($_SERVER['HTTP_REFERER'],0,strlen($urlport))==$urlport ||
	$_SERVER['HTTP_REFERER']=="" ||
	strtolower(substr($_SERVER['HTTP_REFERER'],0,7))!="http://"
	){

}else{
	$site = str_replace("http://","",$_SERVER['HTTP_REFERER']);
	if (strpos($site,"/"))
		$site = substr($site,0,strpos($site,"/"));
	$host = str_replace(":80","",$_SERVER['HTTP_HOST']);

	if ($site != $host){
		$sql = "SELECT * FROM " . db_prefix("referers") . " WHERE uri='{$_SERVER['HTTP_REFERER']}'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		db_free_result($result);
		if ($row['refererid']>""){
			$sql = "UPDATE " . db_prefix("referers") . " SET count=count+1,last='".date("Y-m-d H:i:s")."',site='".addslashes($site)."',dest='".addslashes($host)."/".addslashes($REQUEST_URI)."',ip='{$_SERVER['REMOTE_ADDR']}' WHERE refererid='{$row['refererid']}'";
		}else{
			$sql = "INSERT INTO " . db_prefix("referers") . " (uri,count,last,site,dest,ip) VALUES ('{$_SERVER['HTTP_REFERER']}',1,'".date("Y-m-d H:i:s")."','".addslashes($site)."','".addslashes($host)."/".addslashes($REQUEST_URI)."','{$_SERVER['REMOTE_ADDR']}')";
			if (e_rand(1,100)==2){
				$timestamp = date("Y-m-d H:i:s",strtotime("-1 month"));
				db_query("DELETE FROM ".db_prefix("referers")." WHERE last < '$timestamp' LIMIT 300");
				require_once("lib/gamelog.php");
				gamelog("Deleted ".db_affected_rows()." records from ".db_prefix("referers")." older than $timestamp.","maintenance");
			}
		}
		db_query($sql);
	}
}

if (!isset($session['user']['superuser'])) $session['user']['superuser']=0;

$x = '';
$lc = $l ?? '';

prepare_template();

if (!getSessionUser('hashorse')) $session['user']['hashorse']=0;
$playermount = getmount($session['user']['hashorse']);
$temp_comp = @unserialize($session['user']['companions']);
$companions = array();
if(is_array($temp_comp)) {
	foreach ($temp_comp as $name => $companion) {
		if (is_array($companion)) {
			$companions[$name] = $companion;
		}
	}
}
unset($temp_comp);

$beta = getsetting("beta", 0);
if (!$beta && getsetting("betaperplayer", 1) == 1)
	$beta = getSessionUser('beta');

$clansPrefix = (string) db_prefix('clans');
if (getSessionUser('loggedin')) {
    checkban(getSessionUser('login'));
	$clanId = (int) getSessionUser('clanid') ?: 0;
    $selectedClanQuery = db_query_cached(
        "SELECT clanname AS name,
         clanshort AS tag,
         clandesc AS description,
         clanmotd AS motd,
         clanid AS id,
         descauthor,
         motdauthor,
         customsay
         FROM $clansPrefix
         WHERE clanid = '$clanId'",
        "clan_data:$clanId",
        3600
    );
    $clanData = db_fetch_assoc($selectedClanQuery);
	if (getSessionSuperUser() & SU_MEGAUSER)
		$session['user']['superuser'] =
			getSessionSuperUser() | SU_EDIT_USERS;
}

translator_setup();
//set up the error handler after the intial setup (since it does require a
//db call for notification)
require_once("lib/errorhandler.php");

// WARNING:
// do not hook on these modulehooks unless you really need your module to run
// on every single page hit.  This is called even when the user is not
// logged in!!!
// This however is the only context where blockmodule can be called safely!
// You should do as LITTLE as possible here and consider if you can hook on
// a page header instead.
if (!IS_INSTALLER) {
	modulehook("everyhit");
}
if ($session['user']['loggedin'] && !IS_INSTALLER) {
	modulehook("everyhit-loggedin");
}

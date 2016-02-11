<?php
//addnews ready
// mail ready
require_once("common.php");
require_once("lib/showform.php");
require_once("lib/datetime.php");
require_once("lib/http.php");
require_once("lib/sanitize.php");
require_once("lib/names.php");

tlschema("user");
check_su_access(SU_EDIT_USERS);

$op = httpget('op');
$userid=httpget("userid");

if ($op == "lasthit") {
	// Try and keep user editor and captcha from breaking each other.
	$_POST['i_am_a_hack'] = 'true';
}
page_header("User Editor");

$sort = httpget('sort');
$petition=httpget("returnpetition");
$returnpetition="";
if ($petition != "") $returnpetition = "&returnpetition=$petition";

$gentime = 0;
$gentimecount = 0;

$order = "acctid";
if ($sort!="") $order = "$sort";
$display = 0;
$query = httppost('q');
if ($query === false) $query = httpget('q');

if ($op=="search" || $op== ""){
	require_once("lib/lookup_user.php");
	list($searchresult, $err) = lookup_user($query, $order);
	$op = "";
	if ($err) {
		output($err);
	} else {
		$display = 1;
	}
}


$m = httpget("module");
if ($m) $m = "&module=$m&subop=module";
rawoutput("<form action='user.php?op=search$m' method='POST'>");
output("Search by any field below: ");
rawoutput("<input name='q' id='q'>");
$se = translate_inline("Search");
rawoutput("<input type='submit' class='button' value='$se'>");
rawoutput("</form>");
rawoutput("<script language='JavaScript'>document.getElementById('q').focus();</script>");
addnav("","user.php?op=search$m");
require_once("lib/superusernav.php");
superusernav();
addnav("Bans");
addnav("Add a ban","user.php?op=setupban");
addnav("List/Remove bans","user.php?op=removeban");

// This doesn't seem to be used, so I'm going to comment it out now
//$msg = httpget('msg');
//if ($msg>"") {
//	output("Message: %s`n", $msg);
//}

// Collect a list of the mounts
$mounts="0," . translate_inline("None");
$sql = "SELECT mountid,mountname,mountcategory FROM " . db_prefix("mounts") .  " ORDER BY mountcategory";
$result = db_query($sql);
while ($row = db_fetch_assoc($result)){
	$mounts.=",{$row['mountid']},{$row['mountcategory']}: ".color_sanitize($row['mountname']);
}

$specialties = array(""=>translate_inline("Undecided"));
$specialties = modulehook("specialtynames", $specialties);
$enum = "";
foreach ($specialties as $key=>$name) {
	if ($enum) $enum .= ",";
	$enum .= "$key,$name";
}

//Inserted for v1.1.0 Dragonprime Edition to extend clan possibilities
$ranks = array(CLAN_APPLICANT=>"`!Applicant`0",CLAN_MEMBER=>"`#Member`0",CLAN_OFFICER=>"`^Officer`0",CLAN_LEADER=>"`&Leader`0", CLAN_FOUNDER=>"`\$Founder");
$ranks = modulehook("clanranks", array("ranks"=>$ranks, "clanid"=>NULL, "userid"=>$userid));
$ranks = $ranks['ranks'];
$rankstring = "";
foreach($ranks as $rankid => $rankname) {
	if ($rankstring != "") $rankstring .= ",";
	$rankstring .= $rankid.",".sanitize($rankname);
}
$userinfo = array(
	"Account info,title",
	"acctid"=>"User id,viewonly",
	"login"=>"Login",
	"newpassword"=>"New Password",
	"emailaddress"=>"Email Address",
	"locked"=>"Account Locked,bool",
	"banoverride"=>"Override Bans for this account,bool",
	"referer"=>"ID of player who referred this player," .
		(($session['user']['superuser'] & SU_EDIT_DONATIONS) ? "int" : "viewonly"),
	"refererawarded"=>"Has the referring player been awarded points,viewonly",

	"Basic user info,title",
	"name"=>"Character Name (Do NOT include ANY title information)",
	"title"=>"Dragonkill Title (prepended to name if Custom Title unset)" . (getsetting("edittitles",1) ? "" : ",hidden"),
	"ctitle"=>"Custom Title (prepended to name if set)",
	"sex"=>"Sex,enum,0,Male,1,Female",
	"age"=>"Days since level 1,int",
	"dragonkills"=>"How many times has slain the dragon,int",
	"dragonage"=>"How old when last killed dragon,int",
	"bestdragonage"=>"Youngest days when killed dragon,int",
	"pk"=>"Has user attacked in pvp?,bool",
	"bio"=>"Bio",

	"Stats,title",
	"level"=>"Level,int",
	"race"=>"Race",
	"experience"=>"Experience,int",
	"hitpoints"=>"Current Hitpoints,int",
	"maxhitpoints"=>"Max Hitpoints,int",
	"turns"=>"Turns left,int",
	"playerfights"=>"Playerfights left,int",
	"attack"=>"Attack (includes weapon damage),int",
	"defense"=>"Defense (includes armor defense),int",
	"spirits"=>"Spirits (display only),enum,-6,Resurrected,-2,Very Low,-1,Low,0,Normal,1,High,2,Very High",
	"resurrections"=>"Resurrections,int",
	"location"=>"Where is the user currently",

	"Specialty,title",
	"specialty"=>"Specialty,enumpretrans,". $enum,

	"Grave Fights,title",
	"deathpower"=>array("Favor with %s`0,int", getsetting("deathoverlord", '`$Ramius')),
	"gravefights"=>"Grave fights left,int",
	"soulpoints"=>"Soulpoints (HP while dead),int",

	"Gear,title",
	"gems"=>"Gems,int",
	"gold"=>"Gold in hand,int",
	"goldinbank"=>"Gold in bank,int",
	"transferredtoday"=>"Number of transfers today,int",
	"amountouttoday"=>"Total value of transfers from player today,int",
	"weapon"=>"Weapon Name",
	"weapondmg"=>"Damage of weapon,int",
	"weaponvalue"=>"Purchase cost of weapon,int",
	"armor"=>"Armor Name",
	"armordef"=>"Armor defense,int",
	"armorvalue"=>"Purchase cost of armor,int",

	"Special,title",
	"charm"=>"Charm,int",
	"seendragon"=>"Saw dragon today,bool",
	"seenmaster"=>"Seen master,bool",
	"hashorse"=>"Mount,enumpretrans,".$mounts,
	"fedmount"=>"Fed mount today,bool",
	"boughtroomtoday"=>"Bought a room today,bool",
	"marriedto"=>"Is married to the player with AcctID," .
		(($session['user']['superuser'] & SU_MEGAUSER) ? "int" : "viewonly"),

	"Clan Info,title",
	"clanid"=>"Clan,enumpretrans,0,".translate_inline("None"),
	"clanrank"=>"Clan Rank,enum,$rankstring",
	"clanjoindate"=>"Clan Join Date",

	"Superuser Flags,title",
	"superuser"=>"Superuser Permissions".
	    "<br/><i>For the most part you can only set flags that you yourself possess;".
		"<br/>if you try to set one that you don't have; it won't stick.</i>,".
		"bitfield,".
		($session['user']['superuser'] | SU_ANYONE_CAN_SET |
		  ($session['user']['superuser']&SU_MEGAUSER ? 0xFFFFFFFF : 0)).",".
		SU_MEGAUSER.        ",MEGA USER (enable all permissions)* <i>(this applies to any future flags as well)</i>".
		"<br/><br/><b>Editors</b>,".
		SU_EDIT_CONFIG.     ",Edit Game Configurations*,".
		SU_EDIT_USERS.      ",Edit Users*,".
		SU_EDIT_MOUNTS.     ",Edit Mounts,".
		SU_EDIT_CREATURES.  ",Edit Creatures & Taunts,".
		SU_EDIT_EQUIPMENT.  ",Edit Armor & Weapons,".
		SU_EDIT_RIDDLES.    ",Edit Riddles,".
		SU_MANAGE_MODULES.  ",Manage Modules".
		"<br/><br/><b>Customer Service</b>,".
		SU_IS_GAMEMASTER.   ",Can post comments as gamemaster,".
		SU_EDIT_PETITIONS.  ",Handle Petitions,".
		SU_EDIT_COMMENTS.   ",Moderate Comments,".
		SU_MODERATE_CLANS.  ",Moderate Clan Commentary,".
		SU_AUDIT_MODERATION.",Audit Moderated Comments,".
        SU_OVERRIDE_YOM_WARNING.",Do NOT display YOM warning for this person,".
		SU_POST_MOTD.       ",Post MoTD's".
		"<br/><br/><b>Donations</b>,".
		SU_EDIT_DONATIONS.  ",Manage Donations*,".
		SU_EDIT_PAYLOG.     ",Manage Payment Log".
		"<br/><br/><b>Game Development</b>,".
		SU_INFINITE_DAYS.   ",Infinite Days*,".
		SU_DEVELOPER.       ",Game Developer* (super powers),".
		SU_IS_TRANSLATOR.   ",Enable Translation Tool,".
		SU_DEBUG_OUTPUT.    ",Debug Output,".
		SU_SHOW_PHPNOTICE.  ",See PHP Notices in debug output,".
		SU_RAW_SQL.         ",Execute Raw SQL*,".
		SU_VIEW_SOURCE.     ",View source code,".
		SU_GIVE_GROTTO.     ",Grotto access (only if not granted implicitly in another permission),".
		SU_NEVER_EXPIRE.    ",Account never expires".
		"<br/><br/>* Granting any of these options will hide the user from the HoF",

	"Misc Info,title",
	"regdate"=>"Registered on,viewonly",
	"beta"=>"Willing to participate in beta,bool",
	"laston"=>"Last On (local time),viewonly",
	"lasthit"=>"Last New Day (time is in GMT not local),viewonly",
	"lastmotd"=>"Last MOTD date (local time),viewonly",
	"lastip"=>"Last IP,viewonly",
	"uniqueid"=>"Unique ID,viewonly",
	"gentime"=>"Sum of page gen times,viewonly",
	"gentimecount"=>"Page hits,viewonly",
	"allowednavs"=>"Allowed Navs,viewonly",
	"dragonpoints"=>"Dragon points spent,viewonly",
	"bufflist"=>"Buff List,viewonly",
	"prefs"=>"Preferences,viewonly",
	"donationconfig"=>"Donation buys,viewonly",
	"Battle Info,title",
	"companions"=>"List of companions,viewonly",
	"badguy"=>"Last Badguy,viewonly"
);

$sql = "SELECT clanid,clanname,clanshort FROM " . db_prefix("clans") ." ORDER BY clanshort";
$result = db_query($sql);
while ($row=db_fetch_assoc($result)){
	$userinfo['clanid'].=",{$row['clanid']},<{$row['clanshort']}> {$row['clanname']}";
}

switch ($op) {
	case "lasthit":
		require("lib/user/user_lasthit.php");
		break;
	case "savemodule":
		require("lib/user/user_savemodule.php");
		break;
	case "special":
		require("lib/user/user_special.php");
		break;
	case "save":
		require("lib/user/user_save.php");
		break;
}

switch($op) {
	case "edit":
		require("lib/user/user_edit.php");
		break;
	case "setupban":
		require("lib/user/user_setupban.php");
		break;
	case "del":
		require("lib/user/user_del.php");
		break;
	case "saveban":
		require("lib/user/user_saveban.php");
		break;
	case "delban":
		require("lib/user/user_delban.php");
		break;
	case "removeban":
		require("lib/user/user_removeban.php");
		break;
	case "debuglog":
		require("lib/user/user_debuglog.php");
		break;
	case "":
		require("lib/user/user_.php");
		break;
}
page_footer();

function show_bitfield($val){
	$out = "";
	$v = 1;
	for ($i=0; $i < 32; $i++){
		$out.= (int)$val & (int)$v?"1":"0";
		$v *= 2;
	}
	return($out);
}
?>
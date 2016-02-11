<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/http.php");
require_once("lib/events.php");
require_once("lib/experience.php");

tlschema('village');
//mass_module_prepare(array("village","validlocation","villagetext","village-desc"));
// See if the user is in a valid location and if not, put them back to
// a place which is valid
$valid_loc = array();
$vname = getsetting("villagename", LOCATION_FIELDS);
$iname = getsetting("innname", LOCATION_INN);
$valid_loc[$vname]="village";
$valid_loc = modulehook("validlocation", $valid_loc);
if (!isset($valid_loc[$session['user']['location']])) {
	$session['user']['location']=$vname;
}

$newestname = "";
$newestplayer = getsetting("newestplayer", "");
if ($newestplayer == $session['user']['acctid']) {
	$newtext = "`nYou're the newest member of the village.  As such, you wander around, gaping at the sights, and generally looking lost.";
	$newestname = $session['user']['name'];
} else {
	$newtext = "`n`2Wandering near the inn is `&%s`2, looking completely lost.";
	if ((int)$newestplayer != 0) {
		$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$newestplayer'";
		$result = db_query_cached($sql, "newest");
		if (db_num_rows($result) == 1) {
			$row = db_fetch_assoc($result);
			$newestname = $row['name'];
		} else {
			$newestplayer = "";
		}
	} else {
		if ($newestplayer > "") {
			$newestname = $newestplayer;
		} else {
			$newestname = "";
		}
	}
}

$basetext = array(
	"`@`c`b%s Square`b`cThe village of %s hustles and bustles.  No one really notices that you're standing there.  ".
	"You see various shops and businesses along main street.  There is a curious looking rock to one side.  ".
	"On every side the village is surrounded by deep dark forest.`n`n",$vname,$vname
	);
$origtexts = array(
	"text"=>$basetext,
	"clock"=>"The clock on the inn reads `^%s`@.`n",
	"title"=>array("%s Square", $vname),
	"talk"=>"`n`%`@Nearby some villagers talk:`n",
	"sayline"=>"says",
	"newest"=>$newtext,
	"newestplayer"=>$newestname,
	"newestid"=>$newestplayer,
	"gatenav"=>"City Gates",
	"fightnav"=>"Blades Boulevard",
	"marketnav"=>"Market Street",
	"tavernnav"=>"Tavern Street",
	"infonav"=>"Info",
	"othernav"=>"Other",
	"section"=>"village",
	"innname"=>$iname,
	"stablename"=>"Merick's Stables",
	"mercenarycamp"=>"Mercenary Camp",
	"armorshop"=>"Pegasus Armor",
	"weaponshop"=>"MightyE's Weaponry"
	);
$schemas = array(
	"text"=>"village",
	"clock"=>"village",
	"title"=>"village",
	"talk"=>"village",
	"sayline"=>"village",
	"newest"=>"village",
	"newestplayer"=>"village",
	"newestid"=>"village",
	"gatenav"=>"village",
	"fightnav"=>"village",
	"marketnav"=>"village",
	"tavernnav"=>"village",
	"infonav"=>"village",
	"othernav"=>"village",
	"section"=>"village",
	"innname"=>"village",
	"stablename"=>"village",
	"mercenarycamp"=>"village",
	"armorshop"=>"village",
	"weaponshop"=>"village"
	);
// Now store the schemas
$origtexts['schemas'] = $schemas;

// don't hook on to this text for your standard modules please, use "village"
// instead.
// This hook is specifically to allow modules that do other villages to create
// ambience.
$texts = modulehook("villagetext",$origtexts);
//and now a special hook for the village
$texts = modulehook("villagetext-{$session['user']['location']}",$texts);
$schemas = $texts['schemas'];

tlschema($schemas['title']);
page_header($texts['title']);
tlschema();

addcommentary();
$skipvillagedesc = handle_event("village");
checkday();

if ($session['user']['slaydragon'] == 1) {
	$session['user']['slaydragon'] = 0;
}


if ($session['user']['alive']){ }else{
	redirect("shades.php");
}

if (getsetting("automaster",1) && $session['user']['seenmaster']!=1){
	//masters hunt down truant students
	$level = $session['user']['level']+1;
	$dks = $session['user']['dragonkills'];
	$expreqd = exp_for_next_level($level, $dks);
	if ($session['user']['experience']>$expreqd &&
			$session['user']['level']<15){
		redirect("train.php?op=autochallenge");
	}
}

$op = httpget('op');
$com = httpget('comscroll');
$refresh = httpget("refresh");
$commenting = httpget("commenting");
$comment = httppost('insertcommentary');
// Don't give people a chance at a special event if they are just browsing
// the commentary (or talking) or dealing with any of the hooks in the village.
if (!$op && $com=="" && !$comment && !$refresh && !$commenting) {
	// The '1' should really be sysadmin customizable.
	if (module_events("village", getsetting("villagechance", 0)) != 0) {
		if (checknavs()) {
			page_footer();
		} else {
			// Reset the special for good.
			$session['user']['specialinc'] = "";
			$session['user']['specialmisc'] = "";
			$skipvillagedesc=true;
			$op = "";
			httpset("op", "");
		}
	}
}

tlschema($schemas['gatenav']);
addnav($texts['gatenav']);
tlschema();

addnav("F?Forest","forest.php");
if (getsetting("pvp",1)){
	addnav("S?Slay Other Players","pvp.php");
}
addnav("Q?`%Quit`0 to the fields","login.php?op=logout",true);
if (getsetting("enablecompanions",true)) {
	tlschema($schemas['mercenarycamp']);
	addnav($texts['mercenarycamp'], "mercenarycamp.php");
	tlschema();
}

tlschema($schemas['fightnav']);
addnav($texts['fightnav']);
tlschema();
addnav("u?Bluspring's Warrior Training","train.php");
if (@file_exists("lodge.php")) {
	addnav("J?JCP's Hunter Lodge","lodge.php");
}

tlschema($schemas['marketnav']);
addnav($texts['marketnav']);
tlschema();
tlschema($schemas['weaponshop']);
addnav("W?".$texts['weaponshop'],"weapons.php");
tlschema();
tlschema($schemas['armorshop']);
addnav("A?".$texts['armorshop'],"armor.php");
tlschema();
addnav("B?Ye Olde Bank","bank.php");
addnav("Z?Ze Gypsy Tent","gypsy.php");
if (getsetting("betaperplayer", 1) == 1 && @file_exists("pavilion.php")) {
	addnav("E?Eye-catching Pavilion","pavilion.php");
}

tlschema($schemas['tavernnav']);
addnav($texts['tavernnav']);
tlschema();
tlschema($schemas['innname']);
addnav("I?".$texts['innname']."`0","inn.php",true);
tlschema();
tlschema($schemas['stablename']);
addnav("M?".$texts['stablename']."`0","stables.php");
tlschema();

addnav("G?The Gardens", "gardens.php");
addnav("R?Curious Looking Rock", "rock.php");
if (getsetting("allowclans",1)) addnav("C?Clan Halls","clan.php");

tlschema($schemas['infonav']);
addnav($texts['infonav']);
tlschema();
addnav("??F.A.Q. (newbies start here)", "petition.php?op=faq",false,true);
addnav("N?Daily News","news.php");
addnav("L?List Warriors","list.php");
addnav("o?Hall o' Fame","hof.php");

tlschema($schemas['othernav']);
addnav($texts['othernav']);
tlschema();
addnav("P?Preferences","prefs.php");
if (!file_exists("lodge.php")) {
	addnav("Refer a Friend", "referral.php");
}

tlschema('nav');
addnav("Superuser");
if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
	addnav(",?Comment Moderation","moderate.php");
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
  addnav("X?`bSuperuser Grotto`b","superuser.php");
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS){
  addnav("/?New Day","newday.php");
}
tlschema();
//let users try to cheat, we protect against this and will know if they try.
addnav("","superuser.php");
addnav("","user.php");
addnav("","taunt.php");
addnav("","creatures.php");
addnav("","configuration.php");
addnav("","badword.php");
addnav("","armoreditor.php");
addnav("","bios.php");
addnav("","badword.php");
addnav("","donators.php");
addnav("","referers.php");
addnav("","retitle.php");
addnav("","stats.php");
addnav("","viewpetition.php");
addnav("","weaponeditor.php");

if (!$skipvillagedesc) {
	modulehook("collapse{", array("name"=>"villagedesc-".$session['user']['location']));
	tlschema($schemas['text']);
	output($texts['text']);
	tlschema();
	modulehook("}collapse");
	modulehook("collapse{", array("name"=>"villageclock-".$session['user']['location']));
	tlschema($schemas['clock']);
	output($texts['clock'],getgametime());
	tlschema();
	modulehook("}collapse");
	modulehook("village-desc",$texts);
	//support for a special village-only hook
	modulehook("village-desc-{$session['user']['location']}",$texts);
	if ($texts['newestplayer'] > "" && $texts['newest']) {
		modulehook("collapse{", array("name"=>"villagenewest-".$session['user']['location']));
		tlschema($schemas['newest']);
		output($texts['newest'], $texts['newestplayer']);
		tlschema();
		$id = $texts['newestid'];
		if ($session['user']['superuser'] & SU_EDIT_USERS && $id) {
			$edit = translate_inline("Edit");
			rawoutput(" [<a href='user.php?op=edit&userid=$id'>$edit</a>]");
			addnav("","user.php?op=edit&userid=$id");
		}
		output_notl("`n");
		modulehook("}collapse");
	}
}
modulehook("village",$texts);
//special hook for all villages... saves queries...
modulehook("village-{$session['user']['location']}",$texts);

if ($skipvillagedesc) output("`n");

$args = modulehook("blockcommentarea", array("section"=>$texts['section']));
if (!isset($args['block']) || $args['block'] != 'yes') {
		tlschema($schemas['talk']);
		output($texts['talk']);
		tlschema();
		commentdisplay("",$texts['section'],"Speak",25,$texts['sayline'], $schemas['sayline']);
}

module_display_events("village", "village.php");
page_footer();
?>
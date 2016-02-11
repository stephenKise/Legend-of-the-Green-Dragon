<?php
// addnews ready
// mail ready
// translator ready
/*
Staff List
File: stafflist.php
Author:  Red Yates aka Deimos
Date:    9/8/2004
Version: 1.2 (10/3/2004)

Just a means of listing staff members. In order to put people on the list,
you have to edit their prefs for this module, setting their rank to
something more than 0. The list will be sorted by ranks, so rank them in
groups, like Moderators = 1, SrMods = 2, JrAdmin = 3, Admin = 4, SrAdmin = 5,
Owner = 6, or something like that. Or, to force an order, you could give
everyone a number, but that is kind of silly.  Anyone less than rank 1 won't
be listed. You might want to set the pref ranks before activating the module.
Also included is a space for a blurb at the bottom.

v1.1
Query optimization and such, with help from Kendaer and MightyE
v1.2
Added feature to show if staff is online (suggested by Anyanka of Central)
*/
require_once("lib/villagenav.php");

function stafflist_getmoduleinfo(){
	$info = array(
		"name"=>"Staff List",
		"version"=>"1.2",
		"author"=>"`\$Red Yates",
		"allowanonymous"=>true,
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Staff List Settings, title",
			"biolink"=>"Link staff names to their bios,bool|1",
			"showdesc"=>"Show staff member description fields,bool|1",
			"showon"=>"Show if staff member is online,bool|1",
			"blurb"=>"Blurb to be displayed below the staff list,textarea|",
		),
		"prefs"=>array(
			"Staff List User Preferences, title",
			"rank"=>"Arbitrary ranking number (higher means higher on list),int|0",
			"desc"=>"Description to be put in the staff list|I work here?",
		),
	);
	return $info;
}

function stafflist_install(){
	module_addhook("village");
	module_addhook("about");
	module_addhook("validatesettings");
	module_addhook("player-login");
	module_addhook("player-logout");
	return true;
}

function stafflist_uninstall(){
	return true;
}

function stafflist_dohook($hookname, $args){
	global $session;
	switch ($hookname){
	case "player-login":
	case "player-logout":
		// Invalidate the staff list when someone on staff logs in or out
		if (get_module_pref("rank")) {
			invalidatedatacache("stafflist");
		}
		break;

	case "validatesettings":
		require_once("lib/nltoappon.php");
		$args['blurb'] = nltoappon($args['blurb']);
		break;
	case "village":
		tlschema($args['schemas']['infonav']);
		addnav($args["infonav"]);
		tlschema();
		addnav("Staff List","runmodule.php?module=stafflist&from=village");
		break;
	case "about":
		addnav("Staff List","runmodule.php?module=stafflist&from=about");
		break;
	}
	return $args;
}

function stafflist_run(){
	page_header("Staff List");
	global $session;

	$from=httpget('from');
	if ($from=="about"){
		addnav("Return whence you came","about.php");
	}elseif ($from=="village"){
		villagenav();
	}

	$biolink=get_module_setting("biolink");
	$sql = "SELECT p1.userid, (p1.value+0) AS rank,	p2.value AS descr, u.name, u.login, u.sex, u.laston, u.loggedin FROM ".db_prefix("accounts")." as u, ".db_prefix("module_userprefs")." as p1, ".db_prefix("module_userprefs")." as p2 WHERE (p1.value+0) > 0 AND p1.modulename='stafflist' AND p1.setting='rank' AND p1.userid=u.acctid AND p2.modulename='stafflist' AND p2.setting='desc' AND p2.userid=u.acctid ORDER BY rank DESC, u.acctid ASC";
	$result = db_query_cached($sql, "stafflist", 600);
	$count = db_num_rows($result);

	output("`c`b`@Staff List`0`b`c`n`n");

	if ($count>0){
		$hname = translate_inline("Name");
		$hsex = translate_inline("Sex");
		$hdesc = translate_inline("Description");
		$hon = translate_inline("Online");
		$showdesc=get_module_setting("showdesc");
		$showon=get_module_setting("showon");

		rawoutput("<center>");
		rawoutput("<table border='0' cellpadding='2' cellspacing='1' bgcolor='#999999'>");
		rawoutput("<tr class='trhead'><td>$hname</td><td>$hsex</td>");
		if ($showdesc) rawoutput("<td>$hdesc</td>");
		if ($showon) rawoutput("<td>$hon</td>");
		rawoutput("</tr>");
		for($i=0;$i<$count;$i++){
			$row = db_fetch_assoc($result);
			rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
			if ($session['user']['loggedin'] && $biolink) {
				$link = "bio.php?char=".$row['userid']."&ret=".urlencode($_SERVER['REQUEST_URI']);
				rawoutput("<a href='$link'>");
				addnav("","$link");
			}
			output_notl("`&%s`0", $row['name']);
			if ($session['user']['loggedin'] && $biolink) rawoutput("</a>");
			rawoutput("</td><td>");
			$sex = translate_inline($row['sex']?"`%Female`0":"`!Male`0");
			output_notl("%s", $sex);
			if ($showdesc){
				rawoutput("</td><td>");
				output_notl("`#%s`0", $row['descr']);
			}
			if ($showon) {
				rawoutput("</td><td align='center'>");
				$loggedin=(date("U") - strtotime($row['laston']) <
						getsetting("LOGINTIMEOUT",900) && $row['loggedin']);
				$on=translate_inline($loggedin?"`@Yes`0":"`\$No`0");
				output_notl("%s",$on);
			}
			rawoutput("</td></tr>");
		}
		rawoutput("</table>");
		rawoutput("</center>");
	}else{
		output("`c`@This server appears to not have any staff.");
		output("Most likely, no one has been added to this list yet.`c");
	}

	$blurb=get_module_setting("blurb");
	output_notl("`n`n`c`@%s`0`c", $blurb);
	page_footer();
}
?>

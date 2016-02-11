<?php

//version history
//1.0 basic version with hacked from user.php entrails
//1.1 various upgrades
//      added option to search by either id or ip
//      added nifty tabular form
//      changed it to a submenu of bio, instead of smack out in bio
//1.2 added viewing of possible alt's referals

function bioalt_getmoduleinfo(){
	$info = array(
		"name"=>"Bio Alt",
		"version"=>"1.2",
		"author"=>"Dan Norton",
		"category"=>"Administrative",
		"download"=>"core_module",
		"prefs"=>array(
			"Bio Commentary Preferences,title",
			"viewallowed"=>"Is this person allowed to view alts?,bool|0",
		),
	);
	return $info;
}
function bioalt_install(){
	module_addhook("bioinfo");
	return true;
}
function bioalt_uninstall(){
	return true;
}
function bioalt_dohook($hookname,$args){
	switch($hookname) {
	case "bioinfo":
		global $session;
		$allowed=get_module_pref("viewallowed");
		$char=httpget("char");
		if ($allowed) {
			addnav("Alt Detection");
			addnav("View Possible Alts by IP",
					"runmodule.php?module=bioalt&char=$char&op=viewipalt&ret=".urlencode($args['return_link']));
			addnav("View Possible Alts by ID",
					"runmodule.php?module=bioalt&char=$char&op=viewidalt&ret=".urlencode($args['return_link']));
		}
		break;
	}
	return $args;
}

function bioalt_run(){
	global $session;
	$char=httpget("char");
	$op=httpget("op");
	page_header("Possible Alts");
	switch($op){
	case "viewipalt":
		output("`n`bViewing by IP Address`b`n");
		if (is_numeric($char)) {
			$sql = "SELECT name,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE acctid=$char";
		} else {
			$sql = "SELECT name,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE login=\"$char\"";
		}
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$id = $row['uniqueid'];
		$ip = $row['lastip'];
		$dots = 0;
		$oip = "";
		$tnm = translate_inline("Name");
		$tip = translate_inline("IP");
		$tid = translate_inline("Unique ID");
		$thits = translate_inline("Hits");
		$tlast = translate_inline("Last On");
		$referer = translate_inline("Referer");
		output("`n`bSome possible alts of %s`b`n", is_numeric($char)?$row['name']:$char);
		$thisip = $ip;
		$previpmatches = false;
		while ($dots < 2) {
			debug("Checking $thisip");
			$sql = "SELECT name, lastip, uniqueid, laston, gentimecount, referer FROM " . db_prefix("accounts") . " WHERE lastip LIKE '$thisip%' AND NOT (lastip LIKE '$oip') ORDER BY uniqueid";
			$result = db_query($sql);
			if (db_num_rows($result)>0){
				if ($previpmatches)
					output("(this filter would also catch the following names)`n");
				output("- IP Filter: %s ", $thisip);
				output_notl("`n`n");
				$previpmatches = true;
				rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#999999'>");
				rawoutput("<tr class='trhead'><td>$tnm</td><td>$tip</td><td>$tid</td><td>$thits</td><td>$tlast</td><td>$referer</td></tr>");
				$i=0;
				while ($row=db_fetch_assoc($result)){
					$i=$i+1;
					$altname=$row['name'];
					$altip=$row['lastip'];
					$altid=$row['uniqueid'];
					$hits=$row['gentimecount'];
					$laston=reltime(strtotime($row['laston']));
					$refid=$row['referer'];
					if($refid=="0"){
						$refname=translate_inline("none");
					}else{
						$sqlref = "SELECT name FROM " .
							db_prefix("accounts") . " WHERE acctid=$refid";
						$resultref = db_query($sqlref);
						$rowref=db_fetch_assoc($resultref);
						$refname=$rowref['name'];
					}
					rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
					output_notl("%s", $altname);
					rawoutput("</td><td>$altip</td><td>$altid</td><td>$hits</td><td>$laston</td><td>");
					output("%s",$refname);
					rawoutput("</td></tr>");
				}
				rawoutput("</table>");
				output_notl("`n");
			}

			// Find the previous dot
			$oip = $thisip."%";
			$thisip = substr($ip, 0, strrpos($ip, '.'));
			$dots++;
		}
		break;
	case "viewidalt":
		output("`n`bViewing by ID`n`b");
		if (is_numeric($char)) {
			$sql = "SELECT name,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE acctid=$char";
		} else {
			$sql = "SELECT name,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE login=\"$char\"";
		}
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$id = $row['uniqueid'];
		$ip = $row['lastip'];
		$dots = 0;
		$oip = "";
		$tnm = translate_inline("Name");
		$tip = translate_inline("IP");
		$tid = translate_inline("Unique ID");
		$thits = translate_inline("Hits");
		$tlast = translate_inline("Last On");
		$referer = translate_inline("Referer");
		output("`n`bSome possible alts of %s`b`n", is_numeric($char)?$row['name']:$char);
		$sql = "SELECT name,lastip,uniqueid,laston,gentimecount,referer  FROM " . db_prefix("accounts") . " WHERE uniqueid=\"$id\"";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#999999'>");
			rawoutput("<tr class='trhead'><td>$tnm</td><td>$tip</td><td>$tid</td><td>$thits</td><td>$tlast</td><td>$referer</td></tr>");
			$i=0;
			while ($row=db_fetch_assoc($result)){
				$i=$i+1;
				$altname=$row['name'];
				$altip=$row['lastip'];
				$altid=$row['uniqueid'];
				$hits=$row['gentimecount'];
				$laston=reltime(strtotime($row['laston']));
				$refid=$row['referer'];
				if($refid=="0"){
					$refname=translate_inline("none");
				}else{
					$sqlref = "SELECT name FROM " . db_prefix("accounts") .
						" WHERE acctid=$refid";
					$resultref = db_query($sqlref);
					$rowref=db_fetch_assoc($resultref);
					$refname=$rowref['name'];
				}
				rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
				output_notl("%s", $altname);
				rawoutput("</td><td>$altip</td><td>$altid</td><td>$hits</td><td>$laston</td><td>");
				output("%s",$refname);
				rawoutput("</td></tr>");
			}
			rawoutput("</table>");
		}else {
			output("`n`bSomething went horribly wrong. Panic. Now.`n`b");
		}
		break;
	}
	$ret = urlencode(httpget("ret"));
	addnav("Return to viewing character","bio.php?char=$char&ret=$ret");
	page_footer();
}
?>

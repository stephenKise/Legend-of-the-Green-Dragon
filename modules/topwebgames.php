<?php
// addnews ready
// mail ready
// translator ready

function topwebgames_getmoduleinfo(){
	$info = array(
		"name"=>"Top Web Games",
		"author"=>"JT Traub",
		"category"=>"Administrative",
		"version"=>"1.0",
		"download"=>"core_module",
		"allowanonymous"=>true,
		"override_forced_nav"=>true,
		"settings"=>array(
			"Top Web Games Settings,title",
			"id"=>"Top Web Games ID,int|0",
			"hours"=>"Offset to Top Web Games servers,int|3",
		),
		"prefs"=>array(
			"Top Web Games User Preferences,title",
			"lastvote"=>"When did user last vote|0000-00-00 00:00:00",
			"voted"=>"Did user vote this week?,bool|0",
		),
	);
	return $info;
}

function topwebgames_install(){
	module_addhook("lodge");
	module_addhook("charstats");

	$sql = "DESCRIBE " . db_prefix("accounts");
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		if ($row['Field']=="lastwebvote"){
			$sql = "SELECT lastwebvote,acctid FROM " . db_prefix("accounts") . " WHERE lastwebvote>'0000-00-00 00:00:00'";
			$result1 = db_query($sql);
			debug("Migrating last web vote time.`n");
			while ($row1 = db_fetch_assoc($result1)){
				$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) VALUES ('topwebgames','lastvote',{$row1['acctid']},{$row1['lastwebvote']})";
				db_query($sql);
			}//end while
			debug("Dropping last web vote column from the user table.`n");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP lastwebvote";
			db_query($sql);
			//drop it from the user's session too.
			unset($session['user']['lastwebvote']);
		}//end if
	} // end while

	return true;
}

function topwebgames_uninstall(){
	debug("Uninstalling module.");
	return true;
}

function topwebgames_dohook($hookname, $args) {
	global $session;
	$id = get_module_setting("id");


	switch($hookname){
	case "lodge":
		if (!$id) break;
		addnav("Top Web Games");
		addnav("`&Write a Comment",
			"http://www.topwebgames.com/comments/form.asp?id=$id&page=1",
			false, true);
		break;
	case "charstats":
		if (!$id) break;
		require_once("lib/pullurl.php");
		$counts = datacache("topwebcounts", 600);
		if ($counts === false) {
			$counts = "";
			$c = @pullurl("http://www.topwebgames.com/games/votes.js?id=$id");
			$r = @pullurl("http://www.topwebgames.com/games/placement.js?id=$id");
			if ($c !== false) {
				$c = join($c, "");
				if (preg_match("/\\.write\\('([0-9]+)'\\)/", $c, $matches)) {
					$counts .= "`&Votes this week: `^".$matches[1]."`n";
				}
			} else {
				$counts = "`&Votes this week: `^TWG Error`n";
			}

			if ($r !== false) {
				$r = join($r, "");
				if (preg_match("/\\.write\\('([0-9]+)'\\)/", $r, $matches)) {
					$counts .= "`&Rank this week: `@".$matches[1]."`n";
				}
			} else {
				$counts .= "`&Rank this week: `@TWG Error`n";
			}

			updatedatacache("topwebcounts", $counts);
		}
		addcharstat("Top Web Games");
		$prev = datacache("topwebprev", 600);
		if ($prev === false) {
			$when = @pullurl("http://www.topwebgames.com/games/countdown.js");
			if ($when !== false) {
				$when = join($when, "");
				if(preg_match("/Next reset: (.+ [AP]M)/", $when, $matches)) {
					$prev = strtotime($matches[1] . " -7 days");
					//in case the web call fails this time around, we'll still cache the old value for 10 minutes.
					//So we need to track what the old value was independant of the datacache library.
					set_module_setting("topwebprev",$prev);
				}
			}
			if ($prev === false){
				//this'll happen when the pullurl call failed, we fetch the last known value so that
				//we don't end up trying to do a pullurl every page hit.
				$prev = get_module_setting("topwebprev");
			}
			updatedatacache("tpowebprev",$prev);
		}
		$l = get_module_pref("lastvote");
		if (!$l) $l = "0000-00-00 00:00:00";
		$last = strtotime($l);
		$img = "<img border='0' src='http://www.topwebgames.com/images/banners/88x31.gif'>";
		if ($prev && $last < $prev) {
			$acct = $session['user']['acctid'];
			$url = "http://www.topwebgames.com/in.asp?id=$id&acctid=$acct&alwaysreward=1";
			$vote = "<a href='$url' target='_blank' onClick=\"".popup($url, "800x600").";return false;\">$img<br>`^Vote now! `&Gain `%1 Gem`0</a>";
			set_module_pref("voted", 0);
		} else {
			$vote = "$img<br>`@Already voted this week`0";
		}
		addnav("Top Web Games");
		$val = "`c$vote<br>$counts`c";
		addcharstat("Top Web Games", $val);
		break;
	}
	return $args;
}

function topwebgames_run(){
	$op = httpget('op');
	if ($op != "twgvote") do_forced_nav(false, false);
	$id = httppost('acctid');
	if (!$id) $id = httpget('acctid');
	if (!$id) $id = 1;
	$lastvote = get_module_pref("lastvote", "topwebgames", $id);
	if (!get_module_pref("voted", "topwebgames", $id)) {
		$dt = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " + " . get_module_setting("hours") . " hours" ));
		$sql = "UPDATE " . db_prefix("accounts") . " SET gems=gems+1 WHERE acctid=$id";
		db_query($sql);
		set_module_pref("voted", 1, "topwebgames",$id);
		set_module_pref("lastvote", $dt, "topwebgames",$id);
		debuglog("gained 1 gem for topwebgames", 0, $id);
		invalidatedatacache("topwebcounts");
		echo("OK");
	} else {
		echo("Already voted");
	}
}
?>

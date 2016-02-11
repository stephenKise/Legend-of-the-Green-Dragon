<?php

// This is a basic stat tracking module that will let you your # of fight,
// % of flawless fights, and some other stats over your range of dks. This is
// something that I had been doing by hand on and off for a while, to see how
// my choice of dks points spending had been going, at which point, I got
// fed up, and decided to make the computer do it for me.

// I appoligize ahead of time for how ugly this code is, but it is what it is,
// number crunching.

// Version tracking:
// 1.0 basic funtionality, required additional modulehooks that were not in
//     the core.
// 1.1 JT made changes to allow for forest fight accounting without the
//     additional modulehooks followed by some cleanup of the code by me.
//    - changed many of the:
//          $foo=get_module_pref("foo");
//          $foo=unserialize($foo)
//      to:
//          $foo=unserialize(get_module_pref("foo"));
//      which has the effect of making the code more compact (i.e. harder to
//      read), but *slightly* faster.
//    - added a note that the first line of the table may be inaccurate.
//    - removed $numffwin, as it was never used
//1.2 revised some of the initialization routines.
//1.3 added the ability to block viewing of stats by players
//1.4 added a check to make sure that the dk "in progress" is actually the dk in progress
//1.5 revised the accounting to remove the dkstart term, which is unnecessary if the number of entries is tracked.
//1.6 added pagination (code borrowed from Deimos' Haberdashery
//1.7 reversed the order of display of the stats because of the pagination
//    most recent accomplishments should come first, and be easiest to see


function stattracker_getmoduleinfo(){
	$info = array(
		"name"=>"Stat Tracker",
		"version"=>"1.7",
		"author"=>"Dan Norton",
		"category"=>"Stat Display",
		"download"=>"core_module",
		"prefs"=>array(
			"Bio Stats Preferences,title",
			"numff"=>"Forest fight tallies that are kept,viewonly|none",
			"numflawless"=>"Flawless forest fight tallies that are kept,viewonly|none",
			"numpvp"=>"PvP tallies that are kept,viewonly|none",
			"numpvpwin"=>"Won PvP tallies that are kept,viewonly|none",
			"basehp"=>"Base HP after DK tallies that are kept,viewonly|none",
			"baseatk"=>"Base Atk after DK tallies that are kept,viewonly|none",
			"basedef"=>"Base def after DK tallies that are kept,viewonly|none",
			"dkspeed"=>"DK speed tallies that are kept,viewonly|none",
			"numentries"=>"Number of entries in the array-1,int|0",
			"initialized"=>"Have the arrays been initialized?,bool|0",
		),
		"settings"=>array(
			"Stat Tracker - Preferences,title",
			"visible"=>"Can the players see their own stats?,bool|1",
			"perpage"=>"DKs to show per page in Stattracker, int|25",
		),
	);
	return $info;
}

function stattracker_install(){
	module_addhook("bioinfo");
	module_addhook("battle-victory");
	module_addhook("battle-defeat");
	module_addhook("dragonkill");
	module_addhook("newday");
	return true;
}

function stattracker_uninstall(){
	return true;
}

function stattracker_initialize(){
	global $session;
	$numflawless[0]=0;
	set_module_pref("numflawless",serialize($numflawless));
	set_module_pref("numff",serialize($numflawless));
	set_module_pref("numflawless",serialize($numflawless));
	set_module_pref("numpvp",serialize($numflawless));
	set_module_pref("numpvpwin",serialize($numflawless));
	$basehp[0] = $session['user']['maxhitpoints'] -
		(10 * $session['user']['level']);
	set_module_pref("basehp",serialize($basehp));
	$baseatk[0] = $session['user']['attack'] -
		$session['user']['level'] - $session['user']['weapondmg'];
	set_module_pref("baseatk",serialize($baseatk));
	$basedef[0] = $session['user']['defense'] -
		$session['user']['level'] - $session['user']['armordef'];
	set_module_pref("basedef",serialize($basedef));
	set_module_pref("dkspeed",serialize($numflawless));
	set_module_pref("initialized",1);
}

function stattracker_dohook($hookname,$args){
	global $session;

	$initialized=get_module_pref("initialized");
	if (!$initialized && $session['loggedin'])
		stattracker_initialize();

	switch($hookname) {
	case "bioinfo":
		global $session;
		$visible=get_module_setting("visible","stattracker");
		//check to see if the bio is the player's own, and if the
		//arrays have been initialized
		if (get_module_pref("initialized","stattracker",$args['acctid']) &&
			(($session['user']['acctid']==$args['acctid'] && $visible) ||
			($session['user']['superuser'] & SU_EDIT_USERS))) {
			$char = $args['login'];
			$id = $args['acctid'];

			addnav("Statistical Analysis");
			// Since we're only doing stats for ourself, there is no reason to
			// pass in the char here.

			$perpage = get_module_setting("perpage");
			$arrayrow=get_module_pref("numentries");
			$sql = "SELECT name,dragonkills FROM " . db_prefix("accounts") . " WHERE acctid=$id";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$dragonkills=$row['dragonkills'];

			if($perpage<$arrayrow){
				$pagestop=$dragonkills-$perpage+2;
			}else{
				$pagestop=$dragonkills-$arrayrow;
			}
			$pagestart=$dragonkills+1;
			addnav("View Statistical Analysis","runmodule.php?module=stattracker&op=viewstats&pagestart=$pagestart&pagestop=$pagestop&page=0&char=$char&id=$id&ret=".urlencode($args['return_link']));
		}
		break;
	case "battle-victory":
		global $options;
		static $runonce = false;
		if ($runonce !== false) break;
		$runonce = true;
		$arrayrow=get_module_pref("numentries");
		if ($options['type'] == 'pvp'){
			$numpvp=unserialize(get_module_pref("numpvp"));
			$numpvpwin=unserialize(get_module_pref("numpvpwin"));
			$numpvp[$arrayrow]++;
			$numpvpwin[$arrayrow]++;
			set_module_pref("numpvp",serialize($numpvp));
			set_module_pref("numpvpwin",serialize($numpvpwin));
		} elseif ($options['type'] == 'forest' || $options['type'] == 'travel') {
			if (!isset($args['diddamage']) || $args['diddamage'] != 1) {
				// Flawless
				$numflawless=unserialize(get_module_pref("numflawless"));
				if(!isset($numflawless[$arrayrow])) $numflawless[$arrayrow] = 0;
				$numflawless[$arrayrow]++;
				set_module_pref("numflawless",serialize($numflawless));
			}
			$numff=unserialize(get_module_pref("numff"));
			$numff[$arrayrow]++;
			set_module_pref("numff",serialize($numff));
		}
		break;
	case "battle-defeat":
		global $options;
		static $runonce = false;
		if ($runonce !== false) break;
		$runonce = true;
		$arrayrow=get_module_pref("numentries");
		if ($options['type'] == 'pvp'){
			$numpvp=unserialize(get_module_pref("numpvp"));
			$numpvp[$arrayrow]++;
			set_module_pref("numpvp",serialize($numpvp));
		} elseif ($options['type'] == 'forest' || $options['type'] == 'travel') {
			$numff=unserialize(get_module_pref("numff"));
			$numff[$arrayrow]++;
			set_module_pref("numff",serialize($numff));
		}
		break;
	case "dragonkill":
		// this person has dk'd so update dkspeed, and initialize
		// the next dk's arrays.
		$arrayrow=get_module_pref("numentries");
		set_module_pref("numentries",$arrayrow+1);
		$dkspeed=unserialize(get_module_pref("dkspeed"));
		$dkspeed[$arrayrow]=$session['user']['dragonage'];
		set_module_pref("dkspeed",serialize($dkspeed));
		$numpvp=unserialize(get_module_pref("numpvp"));
		$numpvp[$arrayrow+1]=0;
		set_module_pref("numpvp",serialize($numpvp));
		$numpvpwin=unserialize(get_module_pref("numpvpwin"));
		$numpvpwin[$arrayrow+1]=0;
		set_module_pref("numpvpwin",serialize($numpvpwin));
		$numff=unserialize(get_module_pref("numff"));
		$numff[$arrayrow+1]=0;
		set_module_pref("numff",serialize($numff));
		break;
	case "newday":
		$numff=unserialize(get_module_pref("numff"));
		if($session['user']['age']==1&&$initialized){
			//this person just dk'd, so get the new stats
			$arrayrow=get_module_pref("numentries");
			$basedef=unserialize(get_module_pref("basedef"));
			$basedef[$arrayrow] = $session['user']['defense'] -
				$session['user']['level'] - $session['user']['armordef'];
			set_module_pref("basedef",serialize($basedef));
			$baseatk=unserialize(get_module_pref("baseatk"));
			$baseatk[$arrayrow] = $session['user']['attack'] -
				$session['user']['level'] - $session['user']['weapondmg'];
			set_module_pref("baseatk",serialize($baseatk));
			$basehp=unserialize(get_module_pref("basehp"));
			$basehp[$arrayrow] = $session['user']['maxhitpoints'] - 10;
			set_module_pref("basehp",serialize($basehp));
		}
		break;
	}
	return $args;
}

function stattracker_run(){
	global $session;
	$char=httpget("char");
	$id = httpget("id");
	$op=httpget("op");

	//pagination code shamelessly borrowed and modified from Deimos' Haberdashery
	$page=httpget("page");
	$perpage = get_module_setting("perpage");
	$page = (int)$page;
	//if (!$page) $page = 1;
	$pageoffset = $page;
	if ($pageoffset>0) $pageoffset--;
	$pageoffset*=$perpage;
	$from = $pageoffset+1;
	$limit=" LIMIT $pageoffset,$perpage ";

	switch($op){
	case "viewstats":
		//get the arrays and information
		$arrayrow=get_module_pref("numentries", "stattracker", $id);
		$sql = "SELECT name,dragonkills FROM " . db_prefix("accounts") . " WHERE acctid=$id";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$dragonkills=$row['dragonkills'];

		$numpvp=unserialize(get_module_pref("numpvp", "stattracker", $id));
		$numpvpwin=unserialize(get_module_pref("numpvpwin", "stattracker", $id));
		$numff=unserialize(get_module_pref("numff", "stattracker", $id));
		$numflawless=unserialize(get_module_pref("numflawless", "stattracker", $id));
		$dkspeed=unserialize(get_module_pref("dkspeed", "stattracker", $id));
		$basedef=unserialize(get_module_pref("basedef", "stattracker", $id));
		$baseatk=unserialize(get_module_pref("baseatk", "stattracker", $id));
		$basehp=unserialize(get_module_pref("basehp", "stattracker", $id));

		//output the table
		page_header("Statistical Analysis");
		$dkheader=translate_inline("Dragon Kill #");
		$atkheader=translate_inline("Base Attack");
		$defheader=translate_inline("Base Defense");
		$hpheader=translate_inline("Base Hit Points");
		$ffheader=translate_inline("Number of Forest Fights");
		$flawlessheader=translate_inline("Percent of Forest Fights that were Flawless");
		$pvpheader=translate_inline("Number of Initiated PvPs");
		$pvpwinheader=translate_inline("Percent of Initiated PvPs Won");
		$dkspeedheader=translate_inline("Number of Days to DK");
		rawoutput("<center>");
		rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#999999'>");
		rawoutput("<tr class='trhead'><td>$dkheader</td><td>$atkheader</td><td>$defheader</td><td>$hpheader</td><td>$ffheader</td><td>$flawlessheader</td><td>$pvpheader</td><td>$pvpwinheader</td><td>$dkspeedheader</td>");

		$offset=$dragonkills-$arrayrow+1;
		$pagestart=httpget("pagestart");
		$pagestart = (int)$pagestart-$offset;
		$pagestop=httpget("pagestop");
		$pagestop = (int)$pagestop-$offset;

		for ($i=$pagestart; $i>=$pagestop; $i--) {
			rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
			rawoutput("<td>");
			output_notl("`^%s`0",$dragonkills-$arrayrow+$i+1);
			rawoutput("</td><td>");
			output_notl("%s",isset($baseatk[$i])?$baseatk[$i]:"N/A");
			rawoutput("</td><td>");
			output_notl("%s",isset($basedef[$i])?$basedef[$i]:"N/A");
			rawoutput("</td><td>");
			output_notl("%s",isset($basehp[$i])?$basehp[$i]:"N/A");
			rawoutput("</td><td>");
			output_notl("%s",isset($numff[$i])?$numff[$i]:"N/A");
			rawoutput("</td><td>");
			$flawless = 0;
			if (isset($numflawless[$i]) && $numflawless[$i])
				$flawless = $numflawless[$i];
			if(isset($numff[$i]) && $numff[$i]!=0)
				output_notl("%.2f%%",$flawless/$numff[$i]*100);
			else
				output("N/A");
			rawoutput("</td><td>");
			output_notl("%s",isset($numpvp[$i])?$numpvp[$i]:"N/A");
			rawoutput("</td><td>");
			if(isset($numpvp[$i]) && $numpvp[$i]!=0)
				output("%.2f%%",$numpvpwin[$i]/$numpvp[$i]*100);
			else
				output("N/A");
			rawoutput("</td><td nowrap>");
			if (isset($dkspeed[$i]) && $dkspeed[$i])
				output_notl("%s",$dkspeed[$i]);
			// added this check in case the table got screwed up previously,
			// so that it will only output "In progress" for the dk in
			// progress.
			elseif ($i==$arrayrow)
				output("In progress");
			else
				output("N/A");
			rawoutput("</td></tr>");
		}
		rawoutput("</table>");
		rawoutput("</center>");
		output("`n`cNote: the statistics for the first achieved tracked dragon kill may be inaccurate, due to limited data.`c`n");
		$ret = urlencode(httpget("ret"));
		addnav("Return to viewing character","bio.php?char=$id&ret=$ret");

		if ($arrayrow>$perpage){
			// only show multipage navs if multiple pages are used.
			addnav("Pages");
			for ($pnum=0;($pnum*$perpage)<=$arrayrow;$pnum+=1){
				$pagestart=$dragonkills-($pnum)*$perpage+1;
				$pagestop=$pagestart-$perpage+1;
				if($pagestop<($dragonkills-$arrayrow+1)){
					$pagestop=$dragonkills-$arrayrow+1;
				}
				if ($pnum == $page){
					addnav(array("`b`#Page %s`0 (%s-%s)`b", ($pnum), $pagestop, $pagestart), "runmodule.php?module=stattracker&op=viewstats&pagestart=$pagestart&pagestop=$pagestop&page=$pnum&char=$char&id=$id&ret=$ret");
				} else {
					addnav(array("Page %s (%s-%s)", ($pnum), $pagestop, $pagestart), "runmodule.php?module=stattracker&op=viewstats&pagestart=$pagestart&pagestop=$pagestop&page=$pnum&char=$char&id=$id&ret=$ret");
				}
			}
		}


		page_footer();
		break;
	}
}
?>

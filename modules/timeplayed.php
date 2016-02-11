<?php
// translator ready
// mail ready
// news ready

function timeplayed_getmoduleinfo(){
	$info = array(
		"name"=>"Time Played Counter",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"General",
		"download"=>"core_module",
		"prefs"=>array(
			"Time Played user settings,title",
			"countedtime"=>"Actual time counted between hits,float|0",
			"countedhits"=>"Number of hits counted so far,int|0",
			"lastcountedhit"=>"Time we last counted a hit for this stat,viewonly|0",
		),
	);
	return $info;
}

function timeplayed_install(){
	module_addhook("everyhit-loggedin");
	module_addhook("biostat");
	return true;
}

function timeplayed_uninstall(){
	return true;
}

function timeplayed_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "everyhit-loggedin":
		$prefs = get_all_module_prefs();

		$sincelast = time() - $prefs['lastcountedhit'];
		if ($sincelast < getsetting("LOGINTIMEOUT",900) && $sincelast > 0){
			set_module_pref("countedtime",$prefs['countedtime'] + $sincelast);
			set_module_pref("countedhits",$prefs['countedhits'] + 1);
		}
		set_module_pref("lastcountedhit",time());
		break;
	case "biostat":
		if (get_module_pref("countedhits", false, $args['acctid']) < 100){
			$sql = "SELECT gentimecount FROM " . db_prefix("accounts") . " WHERE acctid={$args['acctid']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			if ($row['gentimecount'] > 100) {
				output("`^Estimated Time Played: `@Too little data to build estimate.`n");
				break;
			}
		}
		require_once("lib/datetime.php");
		$reltime = strtotime("+".round(timeplayed_estimatetotaltime($args['acctid']),0)." seconds");
		output("`^Estimated Time Played: `@%s`n",reltime($reltime,false));
		break;
	}
	return $args;
}

function timeplayed_estimatetotaltime($who){
	//current average time between hits
	$hits = get_module_pref('countedhits', false, $who);
	$counted = get_module_pref('countedtime', false, $who);

	if ($hits < 100) {
		//we'll presume 10 seconds of play time per hit if they have no hits
		//since this module was introduced
		$avgtimeperhit = 10;
	}else{
		$avgtimeperhit = $counted / $hits;
	}

	global $session;
	if ($who == $session['user']['acctid']) {
		$gentimecount = $session['user']['gentimecount'];
	}else{
		$sql = "SELECT gentimecount FROM " . db_prefix("accounts") . " WHERE acctid='$who'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$gentimecount = $row['gentimecount'];
	}
	if ($gentimecount < $hits){
		//this is clearly the result of some data loss, gentimecount should
		// always be higher, but if this *does* happen, let's just zero out
		// the estimated time and presume it's bogus.
		$gentimecount = $hits;
	}
	$estimatedtime = ($gentimecount - $hits) * $avgtimeperhit;

	return $estimatedtime + $counted;
}

?>

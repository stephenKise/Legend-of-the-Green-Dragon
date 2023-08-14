<?php

function module_events($eventtype, $basechance, $baseLink = false) {
	if ($baseLink === false){
		global $PHP_SELF;
		$baseLink = substr($PHP_SELF,strrpos($PHP_SELF,"/")+1)."?";
	}else{
		//debug("Base link was specified as $baseLink");
		//debug(debug_backtrace());
	}
	if (e_rand(1, 100) <= $basechance) {
		global $PHP_SELF;
		$events = module_collect_events($eventtype);
		$chance = r_rand(1, 100);
		reset($events);
		$sum = 0;
		foreach($events as $event) {
			if ($event['rawchance'] == 0) {
				continue;
			}
			if ($chance > $sum && $chance <= $sum + $event['normchance']) {
				$_POST['i_am_a_hack'] = 'true';
				tlschema("events");
				output("`^`c`bSomething Special!`c`b`0");
				tlschema();
				$op = httpget('op');
				httpset('op', "");
				module_do_event($eventtype, $event['modulename'], false, $baseLink);
				httpset('op', $op);
				return 1;
			}
			$sum += $event['normchance'];
		}
	}
	return 0;
}

function module_do_event($type, $module, $allowinactive=false, $baseLink=false)
{
	global $navsection;

	if ($baseLink === false){
		global $PHP_SELF;
		$baseLink = substr($PHP_SELF,strrpos($PHP_SELF,"/")+1)."?";
	}else{
		//debug("Base link was specified as $baseLink");
		//debug(debug_backtrace());
	}
	// Save off the mostrecent module since having that change can change
	// behaviour especially if a module calls modulehooks itself or calls
	// library functions which cause them to be called.
	if (!isset($mostrecentmodule)) $mostrecentmodule = "";
	$mod = $mostrecentmodule;
	$_POST['i_am_a_hack'] = 'true';
	if(injectmodule($module, $allowinactive)) {
		$oldnavsection = $navsection;
		tlschema("module-$module");
		$fname = $module."_runevent";
		$fname($type,$baseLink);
		tlschema();
		//hook into the running event, but only in *this* running event, not in all
		modulehook("runevent_$module", array("type"=>$type, "baselink"=>$baseLink, "get"=>httpallget(), "post"=>httpallpost()));
		//revert nav section after we're done here.
		$navsection = $oldnavsection;
	}
	$mostrecentmodule=$mod;
}

function event_sort($a, $b)
{
	return strcmp($a['modulename'], $b['modulename']);
}

function module_display_events($eventtype, $forcescript=false) {
	global $PHP_SELF, $session;
	if (!($session['user']['superuser'] & SU_DEVELOPER)) return;
	if ($forcescript === false)
		$script = substr($PHP_SELF,strrpos($PHP_SELF,"/")+1);
	else
		$script = $forcescript;
	$events = module_collect_events($eventtype,true);

	if (!is_array($events) || count($events) == 0) return;

	usort($events, "event_sort");

	tlschema("events");
	output("`n`nSpecial event triggers:`n");
	$name = translate_inline("Name");
	$rchance = translate_inline("Raw Chance");
	$nchance = translate_inline("Normalized Chance");
	rawoutput("<table cellspacing='1' cellpadding='2' border='0' bgcolor='#999999'>");
	rawoutput("<tr class='trhead'>");
	rawoutput("<td>$name</td><td>$rchance</td><td>nchance</td>");
	rawoutput("</tr>");
	$i = 0;
	foreach($events as $event) {
		// Each event is an associative array of 'modulename',
		// 'rawchance' and 'normchance'
		rawoutput("<tr class='" . ($i%2==0?"trdark":"trlight")."'>");
		$i++;
		if ($event['modulename']) {
			$link = "module-{$event['modulename']}";
			$name = $event['modulename'];
		}
		$rlink = "$script?eventhandler=$link";
		$rlink = str_replace("?&","?",$rlink);
		$first = strpos($rlink, "?");
		$rl1 = substr($rlink, 0, $first+1);
		$rl2 = substr($rlink, $first+1);
		$rl2 = str_replace("?", "&", $rl2);
		$rlink = $rl1 . $rl2;
		rawoutput("<td><a href='$rlink'>$name</a></td>");
		addnav("", "$rlink");
		rawoutput("<td>{$event['rawchance']}</td>");
		rawoutput("<td>{$event['normchance']}</td>");
		rawoutput("</tr>");
	}
	rawoutput("</table>");
}


function module_collect_events($type, $allowinactive=false)
{
	global $session, $playermount;
	global $blocked_modules, $block_all_modules, $unblocked_modules;
	$active = "";
	$events = array();
	if (!$allowinactive) $active = " active=1 AND";

	$sql = "SELECT " . db_prefix("module_event_hooks") . ".* FROM " . db_prefix("module_event_hooks") . " INNER JOIN " . db_prefix("modules") . " ON ". db_prefix("modules") . ".modulename = " . db_prefix("module_event_hooks") . ".modulename WHERE $active event_type='$type' ORDER BY RAND(".e_rand().")";
	$result = db_query_cached($sql,"event-".$type);
	while ($row = db_fetch_assoc($result)){
		// The event_chance bit needs to return a value, but it can do that
		// in any way it wants, and can have if/then or other logical
		// structures, so we cannot just force the 'return' syntax unlike
		// with buffs.
		ob_start();
		$chance = eval($row['event_chance'].";");
		$err = ob_get_contents();
		ob_end_clean();
		if ($err > ""){
			debug(array("error"=>$err,"Eval code"=>$row['event_chance']));
		}
		if ($chance < 0) $chance = 0;
		if ($chance > 100) $chance = 100;
		if (($block_all_modules || array_key_exists($row['modulename'],$blocked_modules) && $blocked_modules[$row['modulename']]) &&
				(!array_key_exists($row['modulename'],$unblocked_modules) || !$unblocked_modules[$row['modulename']])) {
			$chance = 0;
		}
		$events[] = array('modulename'=>$row['modulename'],
				'rawchance' => $chance);
	}

	// Now, normalize all of the event chances
	$sum = 0;
	reset($events);
	foreach($events as $event) {
		$sum += $event['rawchance'];
	}
	reset($events);
	foreach($events as $index=>$event) {
		if ($sum == 0) {
			$events[$index]['normchance'] = 0;
		} else {
			$events[$index]['normchance'] =
				round($event['rawchance']/$sum*100,3);
			// If an event requests 1% chance, don't give them more!
			if ($events[$index]['normchance'] > $event['rawchance'])
				$events[$index]['normchance'] = $event['rawchance'];
		}
	}
	return modulehook("collect-events", $events);
}

function module_addeventhook($type, $chance){
	global $mostrecentmodule;
	debug("Adding an event hook on $type events for $mostrecentmodule");
	$sql = "DELETE FROM " . db_prefix("module_event_hooks") . " WHERE modulename='$mostrecentmodule' AND event_type='$type'";
	db_query($sql);
	$sql = "INSERT INTO " . db_prefix("module_event_hooks") . " (event_type,modulename,event_chance) VALUES ('$type', '$mostrecentmodule','".addslashes($chance)."')";
	db_query($sql);
	invalidatedatacache("event-".$type);
}
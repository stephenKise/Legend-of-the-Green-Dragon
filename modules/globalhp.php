<?php
// mail ready
// addnews ready
// translator ready
function globalhp_getmoduleinfo(){
	$info = array(
		"name"=>"Global Hitpoint Limits",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Global Hitpoint Settings,title",
			"The first option will completely override the second.  You must set it to true if you want to allow any HP to carry over DK.,note",
			"carrydk"=>"Do any max hitpoints gained carry across DKs?,bool|1",
			"othercarry"=>"Do unaccounted for max hitpoints gained carry across DKs?,bool|1",
		),
	);
	return $info;
}

function globalhp_install(){
	module_addhook_priority("hprecalc", 100);
	module_addhook("superuser");
	return true;
}

function globalhp_uninstall(){
	return true;
}

function globalhp_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "hprecalc":
		if (!get_module_setting("carrydk")) {
			$args['extra'] = 0;
		} elseif (!get_module_setting("othercarry")) {
			// See what is not already accounted for.  Other modules will
			// have adjusted total to account for their stuff.
			$extra = $args['total'] - $args['base'];
			$args['extra'] -= $extra;
		}
		break;
	case "superuser":
		if($session['user']['superuser'] & SU_MEGAUSER) {
			addnav("Mechanics");
			addnav("Reset Hitpoints", "runmodule.php?module=globalhp&op=reset&admin=true");
		}
		break;
	}
	return $args;
}

function globalhp_runevent($type)
{
}

function globalhp_run(){
	check_su_access(SU_MEGAUSER);
	require_once("lib/superusernav.php");
	page_header("Reset User Hitpoints");
	$op = httpget("op");
	superusernav();
	if ($op == "reset") {
		output("`\$Resetting all user hitpoints is a dangerous thing and could seriously upset your users!`n`n");
		output("Only continue if you are sure.`n`n");
		addnav("Reset");
		addnav("Continue?", "runmodule.php?module=globalhp&op=confirm");
		$sql = "SELECT acctid,level,name,gems,maxhitpoints,hitpoints,dragonpoints FROM " .  db_prefix("accounts") . " WHERE maxhitpoints != level*10";
		$res = db_query($sql);
		while($row = db_fetch_assoc($res)) {
			$dp = @unserialize($row["dragonpoints"]);
			if (!is_array($dp)) $dp = array();
			$tot = $row['level'] * 10;
			reset($dp);
			$extra = 0;
			while(list($key, $val)=each($dp)) {
				if ($val == "hp") $extra += 5;
			}
			// Okay.. this guy is fine.. he's not spent anything.
			if ($tot == $row['maxhitpoints']) continue;

			// Okay.. this guy gets a refund.
			$diff = $row['maxhitpoints'] - ($tot + $extra);
			// Okay, figure out the cost of HP potions.
			// If the cedrikspotion module is using random costs, we want to
			// use the average cost.  Otherwise, use the cost for vitality
			// potions.  yes, this is wrong if people bought HP and then the
			// cost changed, but we cannot see the past.
			$cost = 0;
			if (get_module_setting("random", "cedrikspotions")) {
				$min = get_module_setting("minrand", "cedrikspotions");
				$max = get_module_setting("maxrand", "cedrikspotions");
				$cost = ($min + $max) / 2;
			} else {
				$cost = get_module_setting("maxcost", "cedrikspotions");
				// Just in case they have disabled the cedrik potion mod
				// we have a nice default
				if (!$cost) $cost = 2;
			}

			$gems = round($diff * $cost /
					get_module_setting("vitalgain", "cedrikspotions"), 0);
			if ($gems < 0) $gems = 0;
			output("`^Would refund `%%d gems`^ to `#%s`^ for `@%d hitpoints`^.`n", $gems, $row['name'], $diff);
		}
	} elseif ($op == "confirm") {
		$sql = "SELECT acctid,level,name,gems,maxhitpoints,hitpoints,dragonpoints FROM " .  db_prefix("accounts") . " WHERE maxhitpoints != level*10";
		$res = db_query($sql);
		while($row = db_fetch_assoc($res)) {
			$dp = @unserialize($row["dragonpoints"]);
			if (!is_array($dp)) $dp = array();
			$tot = $row['level'] * 10;
			reset($dp);
			$extra = 0;
			$att = 0;
			$def = 0;
			while(list($key, $val)=each($dp)) {
				if ($val == "hp") $extra += 5;
				if ($val == "de") $def++;
				if ($val == "at") $att++;
			}
			// Okay.. this guy is fine.. he's not spent anything.
			if ($tot == $row['maxhitpoints']) continue;

			// Okay.. this guy gets a refund.
			$diff = $row['maxhitpoints'] - ($tot + $extra);
			// Okay, figure out the cost of HP potions.
			// If the cedrikspotion module is using random costs, we want to
			// use the average cost.  Otherwise, use the cost for vitality
			// potions.  yes, this is wrong if people bought HP and then the
			// cost changed, but we cannot see the past.
			$cost = 0;
			if (get_module_setting("random", "cedrikspotions")) {
				$min = get_module_setting("minrand", "cedrikspotions");
				$max = get_module_setting("maxrand", "cedrikspotions");
				$cost = ($min + $max) / 2;
			} else {
				$cost = get_module_setting("maxcost", "cedrikspotions");
				// Just in case they have disabled the cedrik potion mod
				// we have a nice default
				if (!$cost) $cost = 2;
			}
			$gems = round($diff * $cost /
					get_module_setting("vitalgain", "cedrikspotions"), 0);
			if ($gems < 0) $gems = 0;
			$id = $row['acctid'];
			$sql = "UPDATE " . db_prefix("accounts") . " set maxhitpoints=$tot,hitpoints=$tot,gems=gems+$gems,attack=attack-$att,defense=defense-$def WHERE acctid=$id";
			db_query($sql);
			debuglog("Refunded $gems gems for $diff extra hitpoints.", false, $id);
			output("`^Refunding `%%d gems`^ to `#%s`^ for `@%d hitpoints`^.`n", $gems, $row['name'], $diff);
		}
		// Okay.. all of that is done, now remove all 'extrahps' from the
		// preferences table just in case.
		$sql = "DELETE from " . db_prefix("module_userprefs") . " WHERE setting  = 'extrahps'";
		db_query($sql);

		// Okay.. wipe all tynan buffs.
		$sql = "DELETE from " . db_prefix("module_userprefs") . " WHERE modulename='tynan'";
		db_query($sql);

		// And, give everyone a new day. so that tynan buffs recompute and also
		// force everyone to respend their DK points
		$sql = "UPDATE " . db_prefix("accounts") . " SET lasthit='0000-00-00 00:00:00', dragonpoints=''";
		db_query($sql);

	}
	page_footer();
}
?>

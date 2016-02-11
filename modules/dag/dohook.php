<?php
function dag_dohook_private($hookname,$args){
	require_once("modules/dag/misc_functions.php");
	global $session;
	switch($hookname){
	case "pvpwin":
		$args = dag_pvpwin($args);
		break;
	case "dragonkill":
		// handle bounties -- they go away on defeat of green dragon
		$windate = date("Y-m-d H:i:s");
		$sql = "UPDATE " . db_prefix("bounty") . " SET status=1,winner=0,windate='$windate' WHERE target={$session['user']['acctid']} AND status=0";
		db_query($sql);
		break;
	case "inn-desc":
		if (getsetting("pvp",1)) {
			output("`nDag Durnick sits, sulking in the corner with a pipe clamped firmly in his mouth.`n");
		}
		break;
	case "inn":
		if (getsetting("pvp",1)) {
			addnav("Things to do");
			addnav("D?Talk to Dag Durnick","runmodule.php?module=dag");
		}
		break;
	case "delete_character":
		// handle bounties -- they go away on character deletion
		$windate = date("Y-m-d H:i:s");
		$sql = "UPDATE " . db_prefix("bounty") . " SET status=1,winner=0,windate='$windate' WHERE target={$args['acctid']} AND status=0";
		db_query($sql);
		break;
	case "superuser":
		if ($session['user']['superuser'] & SU_EDIT_USERS) {
			addnav("Module Configurations");
			// Stick the admin=true on so that runmodule will let us run
			// even if the module is deactivated
			addnav("Dag's Bounties", "runmodule.php?module=dag&manage=true&admin=true");
		}
		break;
	case "newday":
		set_module_pref("bounties",0);
		break;
	case "showsettings":
		$info = dag_getmoduleinfo();
		$parts = array();
		$values = array();
		while(list($key,$val)= each($info['settings'])) {
			if (is_array($val)) {
				$x = explode("|", $val[0]);
			} else {
				$x = explode("|", $val);
			}
			$y = explode(",", $x[0]);
			if ($y[1] == "title") $parts[$key] = $x[0];
			else $parts[$key] = $y[0] . ",viewonly";
			$values[$key] = get_module_setting($key);
		}
		$args['settings'] = array_merge($args['settings'], $parts);
		$args['values'] = array_merge($args['values'], $values);
		break;
	}
	return $args;
}
?>

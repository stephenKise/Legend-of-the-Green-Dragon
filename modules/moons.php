<?php
// addnews ready
// translator ready
// mail ready

function moons_getmoduleinfo(){
	$info = array(
		"name"=>"Moons",
		"author"=>"JT Traub",
		"version"=>"1.0",
		"category"=>"General",
		"download"=>"core_module",
		"settings"=>array(
			"Moon Settings,title",
			"First Moon,title",
			"moon1"=>"Is the first moon active?,bool|1",
			"moon1name"=>"The name of the first moon,|Vordus",
			"moon1cycle"=>"Days in the first moons lunar cycle,range,10,60,1|23",
			"moon1place"=>"Place in cycle?,range,1,60,1|",
			"Second Moon,title",
			"moon2"=>"Is the second moon active?,bool|0",
			"moon2name"=>"The name of the second moon,|Erela",
			"moon2cycle"=>"Days in the second moons lunar cycle,range,10,60,1|43",
			"moon2place"=>"Place in cycle?,range,1,60,1|",
			"Third Moon,title",
			"moon3"=>"Is the third moon active?,bool|0",
			"moon3name"=>"The name of the third moon,|Lurani",
			"moon3cycle"=>"Days in the third moons lunar cycle,range,10,60,1|37",
			"moon3place"=>"Place in cycle?,range,1,60,1|",

		)
	);
	return $info;
}

function moons_phase($cur, $max)
{
	$phase = "new";
	if ($cur < $max * .12) {
		// new to first quarter
		$phase = "`)new";
	} elseif ($cur < $max * .25) {
		// first quarter to waxing half
		$phase = "a `7waxing crescent";
	} elseif ($cur < $max * .37) {
		// waxing half to 3/4 full
		$phase = "`6half full";
	} elseif ($cur < $max * .5) {
		// 3/4 full to full
		$phase = "`6waxing gibbous";
	} elseif ($cur < $max * .62) {
		// full to waning 3/4
		$phase = "`^full";
	} elseif ($cur < $max * .75) {
		// waning 3/4 to waning half
		$phase = "`6waning gibbous";
	} elseif ($cur < $max * .87) {
		// waning half to waning 1/4
		$phase = "`6half full and waning";
	} else {
		// waning 1/4 to new
		$phase = "a `7waning crescent";
	}
	return translate_inline($phase);
}

function moons_install(){
	module_addhook("newday-runonce");
	module_addhook("newday");
	module_addhook("village-desc");
	module_addhook("forest-desc");
	module_addhook("journey-desc"); // for the worldmap

	if (!get_module_setting("moon1place", "moons")) {
		$place = e_rand(1, get_module_setting("moon1cycle", "moons"));
		set_module_setting("moon1place", $place);
		$place = e_rand(1, get_module_setting("moon2cycle", "moons"));
		set_module_setting("moon2place", $place);
		$place = e_rand(1, get_module_setting("moon3cycle", "moons"));
		set_module_setting("moon3place", $place);
	}

	return true;
}

function moons_uninstall(){
	return true;
}

function moons_dohook($hookname,$args)
{
	global $session;
	$moon1 = get_module_setting("moon1");
	$moon2 = get_module_setting("moon2");
	$moon3 = get_module_setting("moon3");
	if (!$moon1 && !$moon2 && !$moon3) return $args;

	switch($hookname){
	case "newday-runonce":
		$changed=false;
		if ($moon1) {
			$place = get_module_setting("moon1place");
			$max = get_module_setting("moon1cycle");
			$place++;
			if ($place > $max) $place = 1;
			set_module_setting("moon1place", $place);
			$changed=true;
		}
		if ($moon2) {
			$place = get_module_setting("moon2place");
			$max = get_module_setting("moon2cycle");
			$place++;
			if ($place > $max) $place = 1;
			set_module_setting("moon2place", $place);
			$changed=true;
		}
		if ($moon3) {
			$place = get_module_setting("moon3place");
			$max = get_module_setting("moon3cycle");
			$place++;
			if ($place > $max) $place = 1;
			set_module_setting("moon3place", $place);
			$changed=true;
		}
		//tracking changed just in case a bojo has moons in place but all disabled.
		if ($changed) modulehook("moon-cyclechange");
		break;
	case "newday":
		output_notl("`n");
		$msg = "`^The moon `&%s`^ is %s`^.`0`n";
		if ($moon1) {
			$place = get_module_setting("moon1place");
			$max = get_module_setting("moon1cycle");
			output($msg, get_module_setting("moon1name"),
					moons_phase($place, $max));
		}
		if ($moon2) {
			$place = get_module_setting("moon2place");
			$max = get_module_setting("moon2cycle");
			output($msg, get_module_setting("moon2name"),
					moons_phase($place, $max));
		}
		if ($moon3) {
			$place = get_module_setting("moon3place");
			$max = get_module_setting("moon3cycle");
			output($msg, get_module_setting("moon3name"),
					moons_phase($place, $max));
		}
		break;
	case "village-desc":
	case "forest-desc":
	case "journey-desc":
		// The dwarven town is underground, so let's handle that specially.
		// If we are inside the town and the dwarf modules is active AND
	    // then, if this user is in the dwarf town, show no moons.
		if ($hookname == "village-desc" && is_module_active('racedwarf')) {
			if (get_module_setting('villagename', 'racedwarf') ==
					$session['user']['location']) {
				break;
			}
		}
		output_notl("`n");
		$count = 0;
		if ($moon1) $count++;
		if ($moon2) $count++;
		if ($moon3) $count++;
		// Okay, we're above ground.
		$m = "moon is";
		if ($count > 1) $m = "%s moons are";
		if ($hookname=="village-desc") {
			$prefix = "`&Over the buildings of the town, the ".$m." visible.";
		} else {
			$prefix = "`&High above the trees around you, the ".$m." visible.";
		}
		if ($count == 1) {
			output($prefix);
		} else {
			output($prefix, $count);
		}

		$msg = "`&The moon `#%s`& is %s`&.`0";
		if ($moon1) {
			$place = get_module_setting("moon1place");
			$max = get_module_setting("moon1cycle");
			output($msg, get_module_setting("moon1name"),
					moons_phase($place, $max));
		}
		if ($moon2) {
			$place = get_module_setting("moon2place");
			$max = get_module_setting("moon2cycle");
			output($msg, get_module_setting("moon2name"),
					moons_phase($place, $max));
		}
		if ($moon3) {
			$place = get_module_setting("moon3place");
			$max = get_module_setting("moon3cycle");
			output($msg, get_module_setting("moon3name"),
					moons_phase($place, $max));
		}
		output_notl("`n");

		break;
	}

	return $args;
}

function moons_runevent($type){
}

function moons_run(){
}

?>

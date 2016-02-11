<?php
// addnews ready
// mail ready
// translator ready

require_once("lib/villagenav.php");
require_once("lib/commentary.php");
require_once("lib/http.php");

function cemetery_getmoduleinfo(){
	$info = array(
		"name"=>"Cemetery Spook Module",
		"author"=>"JT Traub & S Brown",
		"category"=>"Village",
		"version"=>"1.0",
		"download"=>"core_module",
		"settings"=>array(
			"Cemetery Settings,title",
			"cemeteryloc"=>"Where does the cemetery appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"requires"=>array(
			"cities"=>"1.0|Eric Stevens, part of the core distribution"
		),
	);
	return $info;
}

function cemetery_install(){
	module_addhook("footer-shades");
	module_addhook("village");
	module_addhook("commentary");
	module_addhook("changesetting");
	return true;
}

function cemetery_uninstall(){
	debug("Uninstalling module.");
	return true;
}

function cemetery_dohook($hookname, $args) {
	global $session;

	switch($hookname){
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("cemeteryloc")) {
				set_module_setting("cemeteryloc", $args['new']);
			}
		}
		break;
	case "footer-shades":
		$cemeteryloc=get_module_setting("cemeteryloc");
		addnav("Places");
		addnav(array("G?Haunt %s",$cemeteryloc),
				"runmodule.php?module=cemetery&op=deadspeak&area=village-ghosttown&village=$cemeteryloc");
		break;
	case "village":
		if ($session['user']['location']==get_module_setting("cemeteryloc")) {
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			addnav("C?The Old Cemetery",
				"runmodule.php?module=cemetery&op=cemetery&area=shade");
		}
		break;
	}
	return $args;
}

function cemetery_run(){
	global $session;
	addcommentary();
	$area = httpget("area");
	$village = httpget("village");
	$op = httpget("op");
	if ($op=="cemetery") {
		page_header("Cemetery");
		output("`&`c`bThe Old Cemetery`b`c");
		output("`n`)At the edge of the old town is a long-abandoned cemetery of broken headstones and sickly weeds.");
		output("In this place you can hear the whispers and moans of the dead.`n`n");
		commentdisplay("", $area,"Whisper to the dead",25,"whispers");
		villagenav();
		page_footer();
	}elseif ($op=="deadspeak") {
		page_header("$village");
		output("`&`c`bThe Town of %s`b`c", $village);
		output("`n`)You are standing once again in the deserted ghost town, %s.", $village);
		output("The strange silence no longer worries you.`n`n");
		output("Several tourists clutch each other in frightened silence, sensing rather than seeing you.`n`n");
		output("You move among them, almost invisible.`n`n");
		commentdisplay("", $area,"Speak from the afterworld",25,"eerily moans");
		addnav("Return to the Shades", "shades.php");
		page_footer();
	}
}

?>

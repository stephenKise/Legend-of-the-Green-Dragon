<?php

// this module blocks all navs of players who have put off choosing a new name
// once the player hits either the "village" or "forest" module hooks

require_once("lib/names.php");

function changenamealert_getmoduleinfo(){
	$info = array(
		"name"=>"Name Change Alert",
		"version"=>"0.1",
		"author"=>"dying",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Name Change Alert Settings,title",
			"alertnum"=>"Alert players if their names consist of a new name stem appended with a number less than or equal to this, int|0",
			"It is recommended that this module be deactivated when no players need to be alerted.,note"
		),
		"prefs"=>array(
			"Name Change Alert Preferences,title",
			"alerthit"=>"Has user seen the alert?,viewonly|No"
		),
		"requires"=>array(
			"changename"=>"1.13|by Shannon Brown, distributed with the core code"
		)
	);
	return $info;
}

function changenamealert_install(){
	// only these two hooks are added because there really isn't any need
	// to add more load to the server when the vast majority of users will
	// have to hit one of these hooks in order to do anything constructive
	module_addhook("village");
	module_addhook("forest");
	return true;
}

function changenamealert_uninstall(){
	return true;
}

function changenamealert_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "village":
	case "forest":
		$name = get_player_basename();
		$basename = get_module_setting("basename", "changename");
		$maxnum = get_module_setting("alertnum");

		if (strstr($name,$basename) == $name) {
			$suffix = substr($name, strlen($basename));
			if (preg_match("/[^1234567890]/", $suffix) == 0) {
				if (isset($suffix[0])) {
					if ($suffix[0] != '0') {
						if ($suffix <= $maxnum) {
							$pfhstring = translate_inline("Petition for Help");
							output("`b`\$WARNING`&: Although your name has been changed some time ago, you either do not appear to be aware of it, or have decided to not file a petition requesting a new name.  Instead of deleting your account, the staff has decided to give you one more chance to request a valid name.  Please %sPetition for Help%s as soon as possible.  Thank you.`0`b`n", "<a href='petition.php' target='_blank'>", "</a>", true);
							set_module_pref("alerthit", "Yes");
							blocknav("", true);   // block all navs
							// intentionally prematurely cut rest of page off
							page_footer();
						}
					}
				}
			}
		}
	}
	return $args;
}

?>

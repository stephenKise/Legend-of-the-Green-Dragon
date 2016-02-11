<?php
// translator ready
// mail ready
// addnews ready
function forestturn_getmoduleinfo(){
	$info = array(
		"name"=>"Forest Turn win/lose",
		"version"=>"1.0",
		"author"=>"JT Traub<br>based on code from 4winz",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Forest Turn Event Settings,title",
			"percentgain"=>"Chance to gain a turn (otherwise lose),range,0,100,1|55"
		),
	);
	return $info;
}

function forestturn_install(){
	module_addeventhook("forest", "return 100;");
	return true;
}

function forestturn_uninstall(){
	return true;
}

function forestturn_dohook($hookname,$args){
	return $args;
}

function forestturn_runevent($type)
{
	global $session;
	// The only type of event we care about are the forest.
	$chance = get_module_setting("percentgain");
	$roll = e_rand(1, 100);
	if ($roll <= $chance) {
		output("`^You discover a skin of liquid hanging over the limb of a tree.");
		output("Since the sigils inscribed on the skin are those of someone you recognize as a very powerful warrior from the village, you decide that it would be safe to take a sip.`n`n`n");
		output("Man is that stuff potent!");
		output("You feel `!hyper`^.`n`n");
		output("You `%receive one`^ extra turn!`0");
		$session['user']['turns']++;
	} else {
		output("`^Walking along a path in the forest, you come upon a field of flowers.");
		output("Stopping to pick one, you inhale its scent.`n`n");
		output("`\$Yawn!");
		output("`^Man, that flower scent made you really sleepy.");
		output("You stumble off the path to take a nap.`n`n");
		if ($session['user']['turns'] > 0) {
			output("You `%lose one `^turn!`0");
			$session['user']['turns']--;
		}
	}
}

function forestturn_run(){
}
?>

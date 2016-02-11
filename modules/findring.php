<?php
// translator ready
// mail ready
// addnews ready
function findring_getmoduleinfo(){
	$info = array(
		"name"=>"Find Ring",
		"version"=>"1.1",
		"author"=>"Atrus",
		"category"=>"Forest Specials",
		"download"=>"core_module",
	);
	return $info;
}

function findring_install(){
	module_addeventhook("travel",
			"return (is_module_active('cities')?100:0);");
	module_addeventhook("forest",
			"return (is_module_active('cities')?0:100);");
	return true;
}

function findring_uninstall(){
	return true;
}

function findring_dohook($hookname,$args){
	return $args;
}

function findring_runevent($type,$link) {
	global $session;
	$from = $link;
	$op = httpget('op');
	$session['user']['specialinc'] = "module:findring";
	if ($op==""){
		output("`2You stumble upon a small pearl ring concealed under some leaves.`n`n");
		output("You know that the forest is full of surprises, some of them nasty.");
		output("Will you pick it up?`0");
		addnav("Pick it up",$from."op=pickup");
		addnav("Leave it Alone",$from."op=no");
	} elseif ($op=="no") {
		output("`2You don't think it's worth your time to pick up the ring, and you travel on your way.`0");
		$session['user']['specialinc'] = "";
	}  else {
		output("`2You lean down and place the ring on one finger.`n`n`0");
		$dk = $session['user']['dragonkills'];
		if ($dk == 0) $dk = 1;
		$dkchance = max(5,(ceil($dk / 5)));
		if (e_rand(0,$dkchance) <= 1) {
			output("`^You feel charming!`0");
			$session['user']['charm']++;
			$session['user']['specialinc'] = "";
		} else {
			output("`4A strange feeling comes over you.`n`n");
			output("Before you have a chance to remove it, the ring burns into your flesh.");
			output("The pain is intense, but in a moment is gone, and as you look down, you see that the ring is gone too.`n`n");
			output("`#You `\$lose `#some hitpoints!`n`n`0");
			$amt = $session['user']['hitpoints'];
			$amt = round($amt*0.05, 0);
			$session['user']['hitpoints']-=$amt;
			if ($session['user']['hitpoints'] < 0)
				$session['user']['hitpoints'] = 1;
			$session['user']['specialinc'] = "";
		}
	}
}

function findring_run(){
}
?>
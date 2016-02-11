<?php
// mail ready
// addnews ready
// translator ready
function fairy_getmoduleinfo(){
	$info = array(
		"name"=>"Forest Fairy",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Fairy Forest Event Settings,title",
			"carrydk"=>"Do max hitpoints gained carry across DKs?,bool|1",
			"hptoaward"=>"How many HP are given by the fairy?,range,1,5,1|1",
			"fftoaward"=>"How many FFs are given by the fairy?,range,1,5,1|1",
		),
		"prefs"=>array(
			"Fairy Forest Event User Preferences,title",
			"extrahps"=>"How many extra hitpoints has the user gained?,int",
		),
	);
	return $info;
}

function fairy_install(){
	module_addeventhook("forest", "return 100;");
	module_addhook("hprecalc");
	return true;
}

function fairy_uninstall(){
	return true;
}

function fairy_dohook($hookname,$args){
	switch($hookname){
	case "hprecalc":
		$args['total'] -= get_module_pref("extrahps");
		if (!get_module_setting("carrydk")) {
			$args['extra'] -= get_module_pref("extrahps");
			set_module_pref("extrahps", 0);
		}
		break;
	}
	return $args;
}

function fairy_runevent($type)
{
	require_once("lib/increment_specialty.php");
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:fairy";

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`%You encounter a fairy in the forest.");
		output("\"`^Give me a gem!`%\" she demands.");
		output("What do you do?");
		addnav("Give her a gem", $from."op=give");
		addnav("Don't give her a gem", $from."op=dont");
	}elseif ($op=="give"){
		$session['user']['specialinc'] = "";
		if ($session['user']['gems']>0){
			output("`%You give the fairy one of your hard-earned gems.");
			output("She looks at it, squeals with delight, and promises a gift in return.");
			output("She hovers over your head, sprinkles golden fairy dust down on you before flitting away.");
			output("You discover that ...`n`n`^");
			$session['user']['gems']--;
			debuglog("gave 1 gem to a fairy");
			switch(e_rand(1,7)){
			case 1:
				$extra = get_module_setting("fftoaward");
				if ($extra == 1) output("You receive an extra forest fight!");
				else output("You receive %s extra forest fights!", $extra);
				$session['user']['turns'] += $extra;
				break;
			case 2:
			case 3:
				output("You feel perceptive and notice `%TWO gems`^ nearby!");
				$session['user']['gems']+=2;
				debuglog("found 2 gem from a fairy");
				break;
			case 4:
			case 5:
				$hptype = "permanently";
				if (!get_module_setting("carrydk") ||
						(is_module_active("globalhp") &&
						 !get_module_setting("carrydk", "globalhp")))
					$hptype = "temporarily";
				$hptype = translate_inline($hptype);

				$extra = get_module_setting("hptoaward");
				output("Your maximum hitpoints are `b%s`b increased by %d!",
						$hptype, $extra);

				$session['user']['maxhitpoints'] += $extra;
				$session['user']['hitpoints'] += $extra;
				set_module_pref("extrahps",
						get_module_pref("extrahps")+$extra);
				break;
			case 6:
			case 7:
				increment_specialty("`^");
				break;
			}
		}else{
			output("`%You promise to give the fairy a gem, however, when you open your purse, you discover that you have none.");
			output("The tiny fairy floats before you, tapping her foot on the air as you try to explain why it is that you lied to her.`n`n");
			output("Having had enough of your mumblings, she sprinkles some angry red fairy dust on you.");
			output("Your vision blacks out, and when you wake again, you cannot tell where you are.");
			output("You spend enough time searching for the way back to the village that you lose time for a forest fight.");
			$session['user']['turns']--;
		}
		output("`0");
	}else{
		if ($session['user']['gems']) {
			output("`%Not wanting to part with one of your precious precious gems, you swat the tiny creature to the ground and walk away.`0");
		} else {
			output("`%Not having any gems to part with, you swat the tiny creature to the ground and walk away.`0");
		}
		$session['user']['specialinc'] = "";
	}
}

function fairy_run(){
}
?>

<?php
// a special thanks to Talisman, Sneakabout, Red and Sichae for guiding me in
// my first module and for their patience in explaining things to me.
// version 2.0 - added tie in to Gardens and GardenParty
// version 2.1 - (JT) made the 'party' preference actually do something
// version 2.2 - added a check to case 7 to determine if multiple cities is activated

function pinata_getmoduleinfo(){
	$info = array(
		"name"=>"Pinata",
		"version"=>"2.2",
		"author"=>"`@Elessa and `4Talisman",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Pinata - Garden Settings (Forest is always active),title",
			"active"=>"Always active in the Gardens?,bool|0",
			"maxhit"=>"How many times can a player hit the pinata in the gardens each day?,int|1",
			"party"=>"Active in Gardens during Garden Parties?,bool|1",
			"This setting will be automagically overridden if the garden party module is not active,note",
		),
		"prefs"=>array(
			"Pinata User Prefs, title",
			"hittoday"=>"Times the player has hit the pinata today,int|0",
		)			
	);
	return $info;
}

function pinata_install(){
	module_addeventhook("forest", "return 100;");
	module_addhook("gardens");
	module_addhook("newday");
	return true;
}

function pinata_uninstall(){
	return true;
}

function pinata_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("hittoday",0);
		break;
	case "gardens":
		if ((is_module_active("gardenparty") && get_module_setting("party") == 1) || get_module_setting("active")==1) {
			if (is_module_active("gardenparty")) {
				$start = strtotime(get_module_setting("partystart","gardenparty"));
				$end = strtotime(get_module_setting("partyduration","gardenparty"),
						$start);
				$now = time();
			} else {
				$start = 0;
				$now = 2;
				$end = 1;
			}
			
			if (get_module_setting("active")==1 ||
					($start <= $now && $end >= $now)) {
				if (get_module_pref("hittoday") < get_module_setting("maxhit")){
					output("`n`#You notice a crowd of partiers gathered around a `@Green Dragon shaped pinata`#, swinging awkwardly at it with a long pole. ");
					output("They gesture you over for a turn, offering you the stick and a challenge to do better.`n`n");
					if ($start <= $now && $end >= $now) {
						addnav("Party Treats!");
					}
					addnav("Swat the Pinata", "runmodule.php?module=pinata");
				}else{
					output("You notice the remnants of the broken pinata, and a child sorting through looking for a missed prize.");
				}
			}
		}
		break;
	}	
	return $args;
}

function pinata_runevent($type,$link)
{
	global $session;
	$from = $link;
	$session['user']['specialinc']="module:pinata";

	$op = httpget('op');
	if ($op==""){
		output("`#You discover a small `@bright green dragon pinata `#hanging from the branch of an old oak tree.");
		output("As it twists and turns slightly in the gentle breeze, you wonder what treats may be contained within.`n`n");
		output("Looking for something to swing at the `@pinata, `#you see leaning against the tree a stout branch, stripped of leaves.");
		output("Breaking the `@pinata `#may yield precious resources, or it may result in exhaustion in the attempt to break it.`n`n");
		output("`%Do you wish to take a swing?`0");
		addnav("Swing", $from . "op=swing");
		addnav("Don't Swing", $from . "op=noswing");
	}elseif ($op=="swing"){
		output("`#Knowing that trying to break the `@pinata `#could yield disappointment, you decide to take your chances.`n`n");
		output("Picking up the stout branch in both hands, you take a deep breath as you step up to swing.");
		output("`#You plant your feet and bring your arms back.`n`n");
		output("`i`^The silence around you is broken by the whistle of the branch cutting the air`i.`0`n`n");
		pinata_hit();
		$session['user']['specialinc']="";
	}else{
		$session['user']['specialinc']="";
		output("`&You are not confident in your ability to strike and break the `@pinata, `&so you return to the forest and leave it gently swaying from the branch waiting for the next warrior to come by.`0");
	}
}

function pinata_run(){
	global $session;
	page_header("Pinata in the Gardens");
	addnav("Return to the Gardens","gardens.php");
	output("`#As you grasp the pole in your hands, someone blindfolds you before other hands turn you around until you are dizzy.");
	output("You settle your mind into battlemode, not wishing to miss the target with so many watching.`n`n");
	output("You blindly take several swipes into the air before feeling contact with the `@pinata `#and hear the sound of paper bursting.`n`n");
	pinata_hit();
	$swats = get_module_pref("hittoday");
	$swats ++;
	set_module_pref("hittoday",$swats);
	page_footer();
}

function pinata_hit(){
	global $session;
	$rand = e_rand(1,9);
	switch ($rand){
	case 1:	
		output("`#You stumble as you take the swing and `&MISS!`n`n");
		output("`#You fall to the ground with a twisted ankle and lay looking up at the `@pinata `#as it continues to sway in the gentle breeze.`n`n");
		$curhp = $session['user']['hitpoints'];
		$newhp = round($session['user']['hitpoints']*.1, 0);
		if ($newhp < 1) $newhp = 1;

		if ($session['user']['turns'] > 0 && $curhp > $newhp) {
			output("`&The pain in your twisted, swollen ankle causes you to lose a forest fight, and most of your hitpoints.");
		} elseif ($session['user']['turns'] > 0) {
			output("`&The pain in your twisted, swollen ankle causes you to lose a forest fight.");
		} elseif ($curhp > $newhp) {
			output("`&The pain in your twisted, swollen ankle causes you to lose most of your hitpoints.");
		} else {
			output("`&The pain in your twisted, swollen ankle quickly subsides, to your immense relief.");
		}

		if ($session['user']['turns']>0) $session['user']['turns']--;
		if ($curhp > $newhp) {
			$session['user']['hitpoints'] = $newhp;
		}
		break;
	case 2:
	case 3:
		output("`#The branch hits the `@pinata `#with a resounding thud.");
		output("There is an explosion of `@green `#paper as the `@pinata  `#disintegrates causing sweet candies to fall to the ground.");
		output("You quickly pick them up and pop some in your mouth.`n`n");
		output("You feel `&INVIGORATED!`0`n`n");
		output("`^Your hitpoints have been restored to full, and you feel the energy for another turn in the forest.");
		if ($session['user']['hitpoints'] < $session['user']['maxhitpoints'])
			$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
		$session['user']['turns']++;
		break;
	case 4:
		output("`#The branch hits the `@pinata `#with a resounding thud.");
		output("There is an explosion of `@green `#paper as the `@pinata  `#disintegrates causing sweet candies to fall to the ground.`n`n");			
 		output("You feel `&PERCEPTIVE!`0`n`n");
		output("You notice something glittering under some of the paper as you pick up the candy.`n`n");
		output("`^You find a `%GEM`^!");
		$session['user']['gems']++;
		debuglog("found 1 gem under the pinata");
		break;
	case 5:
		output("You stumble as you take the swing and MISS!`n`n");
		output("`#You fall to the ground and lay looking up at the `@pinata `#as it continues to sway in the gentle breeze.`n`n");
		output("`7In your tumble your gem pouch opens.`n`n");
		if ($session['user']['gems'] > 0) {
			$session['user']['gems']--;
			output("`^You lost a `%gem`^!");
			debuglog("lost 1 gem under the pinata");
		} else {
			output("`^Fortunately, you don't seem to have lost anything!");
		}
		break;
	case 6:
		output("You stumble as you take the swing and MISS!`n`n");
		output("`#You fall to the ground and lay looking up at the `@pinata `#as it continues to sway in the gentle breeze.`n`n");
		output("`^In your tumble your gold pouch opens.`n`n");
		$goldlost = round($session['user']['gold'] * .1, 0);
		if ($goldlost) {
			output("`^You lost %s `6gold`^.",$goldlost);
			$session['user']['gold']-=$goldlost;
			debuglog("lost $goldlost gold under the pinata");
		} else {
			output("`^Fortunately, you don't seem to have lost anything!");
		}
		break;
	case 7:
	If (is_module_active("cities")){ 
		output("`#The branch hits the `@pinata `#with a resounding thud.");
		output("There is an explosion of `@green `#paper as the `@pinata  `#disintegrates causing sweet candies to fall to the ground.`n`n");
		output("You pick up a very large piece of candy and put it in your mouth.`n`n");
		output("`#You feel `&BOLD!`0`n`n");
		output("`^You receive an extra `&Travel `^today!`0`n`n");	
		set_module_pref("traveltoday",
				get_module_pref("traveltoday","cities")-1,"cities");
		break;
		}
	case 8:	case 9:
		output("`#The branch hits the `@pinata `#with a resounding thud.");
		output("There is an explosion of `@green `#paper as the `@pinata  `#disintegrates causing sweet candies to fall to the ground.`n`n");
		output("You pick up a small piece of candy and put it in your mouth.`n`n");
		output("You feel `&ENERGETIC!`0`n`n");
		output("`^You receive an extra forest fight!");
		$session['user']['turns']++;
		break;
	}
	output("`0");
}

?>

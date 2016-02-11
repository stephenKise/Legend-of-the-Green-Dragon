<?php
// addnews ready
// translator ready
// mail ready
/*
 * Just a silly little village special that is mainly for the "what the heck
 * was that?" response. Generally a gem or gold is found, with a slight
 * chance of something bad or good happening.
 *
 * Version history
 * 0.1 Initial spatter of code
 * 0.2 Functioning code
 * 0.3 Added Bonemarrow Beast, cache discovery, and maximum visits per day
 * 0.4 Added a warning after defeating the Bonemarrow Beast that there might be more
 * 0.5 Added charm loss for defeat if no ff's left.
       Removed the faulty taunt section.
 * 0.6 Corrected several grammar mistakes.
 * 0.7 Corrected an additional grammar mistake.
*/

require_once("lib/fightnav.php");

function spookygold_getmoduleinfo(){
	$info = array(
		"name"=>"Spooky Gold",
		"version"=>"0.7",
		"author"=>"Dan Norton",
		"category"=>"Village Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Spooky Gold - Settings,title",
			"cowardicechance"=>"Percentage of times running away for something bad to happen,range,0,100,5|10",
			"visitmax"=>"Number of times allowed to visit the alley per day,int|3",
			"beastchance"=>"Percentage of times that the beast will attack,range,0,100,5|10",
			"rawchance"=>"Raw chance of seeing the alley,range,5,50,5|25",
			"cachechance"=>"Chance of finding a cache of gems or gold,range,0,100,5|10",
		),
		"prefs"=>array(
			"Spooky Gold - Preferences,title",
			"visits"=>"How many visits to the alley has the player made today?,bool|0",
		),
	);
	return $info;
}

function spookygold_seentest(){
	$visits=get_module_pref("visits","spookygold");
	$visitmax=get_module_setting("visitmax","spookygold");
	$chance=get_module_setting("rawchance","spookygold")*(($visits)<($visitmax));
	return($chance);
}

function spookygold_install(){
	module_addeventhook("village", "require_once(\"modules/spookygold.php\"); return spookygold_seentest();");
	module_addhook("newday");
}

function spookygold_uninstall(){
	return true;
}

function spookygold_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("visits",0);
		break;
	}
	return $args;
}

function spookygold_runevent($type)
{
	global $session;
	$from = "village.php?";
	$session['user']['specialinc'] = "module:spookygold";
	$op = httpget('op');

	require_once("lib/partner.php");
	$partner = get_partner();

	switch($op){
	case "":
		output("`n`7Walking along through the village, your keen skills as a %s pick up an alley between the shops that you have never noticed before.`n", $session['user']['race']);
		output("`nDo you want to go down this dark alley?`n");
		addnav("Go down the alley",$from."op=alley");
		addnav("Ignore the alley",$from."op=walkaway");
		break;
	case "alley":
		$award = (e_rand(0,1) ? "gem":"gold piece");
		output("`n`7Walking down the alley, you notice that there are many dark spots, capable of hiding thieves, or evil creatures whose only desire is to eat the `4marrow`7 from your `&bones`7.");
		output("You get a bad feeling about this place.`n`n");
		output("At the end of the alley, next to some seemingly empty crates, you spot a %s lying on the ground, in plain sight.`n", translate_inline($award));
		output("`nWould you like to step forward and pick up the %s?`n", translate_inline($award));
		if($award=="gem"){
			addnav("Pick up the gem",$from."op=pickupgem");
		}else{
			addnav("Pick up the gold",$from."op=pickupgold");
		}
		addnav("Turn tail and run",$from."op=dontpickup");
		break;
	case "pickupgem":
		$diceroll=e_rand(1,100);
		$beastchance=get_module_setting("beastchance");
		$cachechance=get_module_setting("cachechance");
		if($diceroll>$beastchance&&$diceroll<(100-$cachechance)){
			output("`nYou quickly pick up the gem, turn tail and run out of the alley as fast as you can.`n");
			output("`n`^You've gained `%1 gem`^.`0`n");
			debuglog("found a gem in the spooky alley");
			$session['user']['gems']++;
			$session['user']['specialinc'] = "";
			set_module_pref("visits",get_module_pref("visits")+1);
		}elseif($diceroll<=$beastchance){
			output("`nFrom out of nowhere, a large beast from your nightmares lunges out to attack!`n");
			spookygold_fight();
		}else{
			output("`nYou reach down to pick up the gem, and realize that next to it, there is another gem, and next to that, another gem, and so on.");
			output("After a few moments of hoarding, you realize that a pair of red eyes are watching you.");
			output("You quickly turn tail and run out of the alley as fast as you can.`n");
			output("`n`^You've gained `%5 gems`^.`0`n");
			debuglog("found a cache of 5 gems in the spooky alley");
			$session['user']['gems']+=5;
			$session['user']['specialinc'] = "";
			set_module_pref("visits",get_module_pref("visits")+1);
		}
		break;
	case "pickupgold":
		$diceroll=e_rand(1,100);
		$cachechance=get_module_setting("cachechance");
		if($diceroll>$cachechance){
			output("`nYou quickly pick up the gold piece, turn tail and run out of the alley as fast as you can.`n");
			output("`n`^You've gained 1 gold.`0`n");
			debuglog("found a gold piece in the spooky alley");
			$session['user']['gold']++;
			$session['user']['specialinc'] = "";
			set_module_pref("visits",get_module_pref("visits")+1);
		}else{
			output("`nYou reach down to pick up the gold piece, and realize that next to it, there is another gold piece, and next to that, another gold piece, and so on.");
			output("After a few moments of hoarding, you realize that a pair of red eyes are watching you.");
			output("You quickly turn tail and run out of the alley as fast as you can.`n");
			$gold = $session['user']['level'] * e_rand(159, 211);
			output("`n`^You've gained %s gold.`0`n",$gold);
			debuglog("found a cache of $gold in the spooky alley");
			$session['user']['gold']+=$gold;
			$session['user']['specialinc'] = "";
			set_module_pref("visits",get_module_pref("visits")+1);
		}
		break;
	case "dontpickup":
		output("`nKnowing that no reward is worth your life, you quickly turn tail and run out of the alley as fast as you can.`n");
		$session['user']['specialinc'] = "";
		$cowardicechance=get_module_setting("cowardicechance");
		$wimpychance=e_rand(1,100);
		if($wimpychance<=$cowardicechance){
			output("`nWaiting at the end of the alley is %s`0, watching you run comically away from a small glittering thing on the ground.`n",$partner);
			output("`n`^You feel less charming.`0`n");
			if($session['user']['charm']>=2){
				$session['user']['charm']-=2;
			}else{
				$session['user']['charm']=0;
			}
		}
		set_module_pref("visits",get_module_pref("visits")+1);
		break;
	case "walkaway":
		output("`nYou decide that going down that alley is just not worth it, and continue on with your way.`n");
		$session['user']['specialinc'] = "";
		set_module_pref("visits",get_module_pref("visits")+1);
		break;
	case "fight":
	case "run":
		spookygold_fight();
		break;
	}
}

function spookygold_fight() {
	$op = httpget("op");
	global $session;
	$from = "village.php?";

	if ($op=="pickupgem"){
		$badguy = array(
			"creaturename"=>"`\$Bonemarrow Beast`0",
			"creaturelevel"=>$session['user']['level']+2,
			"creatureweapon"=>"Dripping Fangs",
			"creatureattack"=>$session['user']['attack'],
			"creaturedefense"=>$session['user']['defense'],
			"creaturehealth"=>round($session['user']['maxhitpoints'],0),
			"diddamage"=>0,
			"type"=>"bonemarrow");
		$session['user']['badguy']=createstring($badguy);
		$op="fight";
		httpset('op', "fight");
	}
	if ($op=="run"){
		output("You are backed into a corner and cannot escape!");
		$op="fight";
		httpset('op', "fight");
	}
	if ($op=="fight"){
		$battle=true;
	}
	if ($battle){
		require_once("battle.php");
		if ($victory){
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^With the last of your energy, you press a piece of cloth to your wounds, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			output("`0Having won this tremendous battle, the Bonemarrow Beast laying at your feet, you step forward to retrieve your prize, but you can't help but wonder if there are any more beasts lurking in the shadows.");
			output("`nDo you want to step forward and pick up the gem, or turn tail and run away?");
			addnav("Pick up the gem",$from."op=pickupgem");
			addnav("Run away",$from."op=dontpickup");
		}elseif ($defeat){
			addnews("`%%s`6's `&broken and bloody body was seen lying in an alley.",$session['user']['name']);
			debuglog("lost to Bonemarrow Beast");
			$session['user']['hitpoints']=1;
			output("The beast stops short of killing you, but instead starts ripping your arms and legs open, crunching the bones and eating the marrow. All the while it is careful to keep you from dying, as it prefers to eat its meals from living prey.");
			output("The pain is unbearable, and you pass out, with the image of the beast's drooling jaws still before your eyes.`n");
			output("`nSome time later that day, you awaken, and drag your near-lifeless body out of the alley.");
			if($session['user']['turns']>5){
				$session['user']['turns']-=5;
				output("`^You lost 5 forest fights while unconscious.");
			}elseif($session['user']['charm']>0){
				output("`^You survey your bent and broken body and realize that you'll probably never heal quite right to be as attractive as you once were.");
				$session['user']['charm']--;
			}else{
				output("`^You survey your bent and broken body and realize that you'll probably never heal quite right.  At least you can take solace in the fact that you cannot get any less attractive than you were before the fight.");
			}
			$session['user']['specialinc']="";
			$session['user']['specialmisc']="";
		}else{
			fightnav(true,true);
		}
	}
}

function spookygold_run(){
}
?>

<?php
/*
Details:
 * This is a module for the Forest
 * It makes the Dragon randomly move around
 * It will show up as an empty cave
History Log:
 v1.0:
 o Seems to be Stable
 v1.1:
 o Each user has their dragon in a different place
 o Thanks to the suggestion by Kendaer:)
 v1.2:
 o Cleaned up the wording on some things a bit
 o Fixed some logical flaws
 v1.3:
 o Make use of the validforestloc hook so that only a valid and reachable
   city which has forest for the dragon to be in can be chosen.
 v1.31:
 o Added a parameter to control the chance of the dragon moving each day.
   It defaults to 100 so previous behaviour is retained until admin changed.
 o Configurable if dragon moves on resurrection. Default no for compat.
 v1.32;
 o Added a second cave location for players under a configurable number of
   DKs
*/
require_once('lib/e_rand.php');
require_once('lib/forest.php');

function dragonplace_getmoduleinfo(){
	$info = array(
		"name"=>"Dragon Place",
		"version"=>"1.32",
		"author"=>"`@CortalUX",
		"category"=>"Forest",
		"download"=>"core_module",
		// This module makes no sense without multiple cities, so.. require
		// them
		"requires"=>array(
			"cities"=>"1.0|By Eric Stevens, part of the core download",
		),
		"settings"=>array(
			"Dragon Location - General,title",
			"chance"=>"Chance of dragon moving to a new cave on new day?,range,0,100,5|100",
			"Note: 0 means the dragon will only move once slain.,note",
			"mvres"=>"Can the dragon move on a resurrection new day?,bool|0",
			"dkmin"=>"At what DK does the dragon become rarer? (0 for always rare),int|0",
			"sdrag"=>"Does finding an empty cave count for seeing the dragon?,bool|0",
			"idrag"=>"Does running to the inn count as seeing the dragon?,bool|1",
			'maxsearch'=>"Max dragon searches per day,range,0,10,1|1",
			"Max searches can be 0 for unlimited and is only useful if the previous options are true,note",
			"Dragon Location - Reward,title",
			"gold"=>"How much gold a player can find in an empty cave?,int|0",
			"gems"=>"How many gems a player can find in an empty cave?,int|0",
			"hp"=>"How many hitpoints a player can lose in an empty cave?,int|0",
			"(just set any of these to greater than 0 to take effect),note",
			"Dragon Location - Scrying,title",
			"scrygems"=>"Cost in gems to scry for the dragon?,int|0",
			"Note: Setting this to 0 disables the ability to scry from the Gypsy,note",
		),
		"prefs"=>array(
			"Dragon Location - Reward,title",
			"dragonloc"=>"Where is the Dragon currently?,location|".getsetting("villagename", LOCATION_FIELDS),
			"dragonloc2"=>"(for lower dks) Where else is the Dragon currently?,location|".getsetting("villagename", LOCATION_FIELDS),
			"gold"=>"Has the player found gold in a cave already?,bool|0",
			"gems"=>"Has the player found gems in a cave already?,bool|0",
			"hp"=>"Has he player lost hitpoints in a cave already??,bool|0",
			"(Note: these booleans are only useful if the number of searches is unlimited.),note",
			"search"=>"How many times has the user searched today?,int|0",
			"reset"=>"Force a reset of the the dragon locations,bool|1",
		),
	);
	return $info;
}

function dragonplace_install(){
	global $session;
	module_addhook("forest");
	module_addhook("newday");
	module_addhook("dragonkill");
	module_addhook("gypsy");
	return true;
}

function dragonplace_uninstall(){
	return true;
}

function dragonplace_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "gypsy":
		// No scry in the tent unless you can hunt down the dragon.
		if ($session['user']['level'] < 15) break;

		$cost = get_module_setting("scrygems");
		$g = translate_inline($cost==1?"gem":"gems");
		if ($cost > 0) {
			addnav("Dragon's Eye");
			addnav(array("Search (%s %s)", $cost, $g),
					"runmodule.php?module=dragonplace&op=scry");
			output("`n`nOn a low table, you see a glimmering orb, with a placard nearby stating that the orb is the eye of an ancient dragon.`n");
			output("You vaguely recall a tale about how one could look into a dragon's eye and see what its kin sees!`n`n");
			output("You move toward the table, and the voice of the old crone stops you, \"`!What?? You think that's free?  It'll cost you %s %s to look through that eyeball!`5\"", $cost, $g);
		}
		break;
	case "forest":
		blocknav("forest.php?op=dragon",false);
		// Move the check for the location into the cave code so that
		// the unwary don't get a clue as to whether the dragon is there.
		$max = get_module_setting("maxsearch");
		if ($session['user']['level']>=15 &&
				(!$max || (get_module_pref("search") < $max))) {

			// Make sure we have a dragon cave
			if (get_module_pref("dragonloc") == getsetting("villagename", LOCATION_FIELDS)) {
				dragonplace_choose();
			}
			tlschema("forest");
			addnav("Fight");
			addnav("G?`@Seek out the Green Dragon",
					"runmodule.php?module=dragonplace&op=dragon");
			tlschema();
		}
		break;
	case "newday":
		if ($session['user']['level'] < 15) break;
		$didchoose = 0;

		if (get_module_pref("reset")) {
			dragonplace_choose();
			set_module_pref("reset", 0);
			debuglog("Forcing dragon randomization");
			$didchoose = 1;
		}

		set_module_pref("search", 0);
		// Don't move on resurrection if we shouldn't
		if (!$didchoose &&
				($args['resurrection']==="true") &&
				!get_module_setting("mvres")) {
			debuglog("Dragon didn't move due to resurrection.");
			break;
		}
		// Check chance of moving.
		if (!$didchoose && (e_rand(1, 100) > get_module_setting("chance"))) {
			debuglog("Dragon didn't move today.");
			break;
		}
		// If we chose on above because of the reset, no need to do it again.
		if (!$didchoose) dragonplace_choose();
		debuglog("Dragon location was randomized..");
		output("`n`b`^The `@Green Dragon`^ wanders to a new cave...`b`n");
		break;
	case "footer-inn":
		if (httpget('op')=="fleedragon"&&get_module_setting('idrag')==1) {
			$session['user']['seendragon']=1;
			set_module_pref("search", get_module_pref("search")+1);
		}
		break;
	case "dragonkill":
		set_module_pref('gems',0);
		set_module_pref('gold',0);
		set_module_pref('hp',0);
		set_module_pref("search", 0);
		output("`n`@All those empty caves, all that searching, just for the Dragon to return...");
		// Pick a new location now since the dragon might not move on new day.
		set_module_pref("dragonloc", '');
		set_module_pref("dragonloc2", '');
		dragonplace_choose();
		break;
	}
	return $args;
}

function dragonplace_run(){
	global $session;

	require_once("lib/partner.php");
	$partner = get_partner();

	$op = httpget('op');
	page_header('The Forest');
	switch ($op) {
	case "scry":
		$cost = get_module_setting("scrygems");
		if ($session['user']['gems'] < $cost) {
			page_header("Gypsy Seer's tent");
			villagenav();
			addnav("Continue looking around", "gypsy.php");
			if ($session['user']['gems'] == 0) {
				output("`5You turn out your pockets looking for gems, but don't find any.`n");
			} else {
				output("`5You turn out your pockets looking for gems, but only find %s, which is not enough.`n", $session['user']['gems']);
			}
			output("Disenheartened, you step away from the dragon's eye.");
			page_footer();
		} else {
			$session['user']['gems'] -= $cost;
			page_header("Gypsy Seer's tent");
			villagenav();
			addnav("Continue looking around", "gypsy.php");

			$vloc = modulehook("validforestloc", array());
			while (1) {
				// Don't look at the same place twice in a row.
				$vil = array_rand($vloc);
				if ($vil != get_module_pref("lastscry")) break;
			}
			set_module_pref("lastscry", $vil);

			output("`5Bending forward, you peer intently into the eye of the dragon.`n");
			output("As you stare, the tent around you seems to fade away and is replaced by a vision of %s.`n`n", $vil);
			if ($vil == get_module_pref("dragonloc") ||
					$vil == get_module_pref("dragonloc2")) {
				output("%sFrom high above, you see the villagers of %s walking to and fro, and even some adventurers entering and leaving the gates to go into the forest.`n", "`\$", $vil);
				output("As you continue to watch, the villagers get closer and closer and then one of them is right in front of you as you swoop down into a forest clearing outside a cave.");
				output("You see the villager turn and scream just before being engulfed in flames!!`5`n`n");
				output("Stunned at the death you have just witnessed, you lift your eyes from the orb and the tent regains focus.");
			} else {
				output("%sFrom high above, you see the villagers of %s walking to and fro, and even some adventurers entering and leaving the gates to go into the forest.`n", "`^", $vil);
				output("As you continue to watch, the village disappears in the distance behind you.`5`n");
				output("Obviously the dragon isn't hunting in %s today.`n`n", $vil);
				output("You lift your eyes from the orb, and the tent regains focus.");
			}
			page_footer();
		}
	case "dragon":
		addnav("Enter the cave","runmodule.php?module=dragonplace&op=cave");
		addnav("Run away like a baby","inn.php?op=fleedragon");
		output("`\$You approach the blackened entrance of a cave deep in the forest, though the trees are scorched to stumps for a hundred yards all around.");
		output("A thin tendril of smoke escapes the roof of the cave's entrance, and is whisked away by a suddenly cold and brisk wind.");
		output("The mouth of the cave lies up a dozen feet from the forest floor, set in the side of a cliff, with debris making a conical ramp to the opening.");
		output("Stalactites and stalagmites near the entrance trigger your imagination to inspire thoughts that the opening is really the mouth of a great leech.`n`n");
		output("You cautiously approach the entrance of the cave, and as you do, you hear, or perhaps feel a deep rumble that lasts thirty seconds or so, before silencing to a breeze of sulfur-air which wafts out of the cave.");
		output("The sound starts again, and stops again in a regular rhythm.`n`n");
		output("You clamber up the debris pile leading to the mouth of the cave, your feet crunching on the apparent remains of previous heroes, or perhaps hors d'oeuvres.`n`n");
		output("Every instinct in your body wants to run, and run quickly, back to the warm inn, and the even warmer %s`\$.",$partner);
		output("What do you do?`0");
		if (get_module_setting('sdrag')) {
			$session['user']['seendragon']=1;
			set_module_pref("search", get_module_pref("search")+1);
		}
		break;
	case "cave":
		// Okay, if this is the REAL dragon cave, redirect them to dragon.php
		if ($session['user']['location']==get_module_pref('dragonloc') ||
				$session['user']['location']==get_module_pref('dragonloc2')) {
			redirect('dragon.php');
		}
		// We could get here from a module (peerpressure) so let's make sure
		// that since they have gone into the cave, we are nice to them and
		// don't force them back in again and again.
		$session['user']['specialinc']="";
		// Otherwise, empty cave.
		output("`@You enter the cave.... `%and it's empty!`n");
		output("It may have been a dragon's cave once, but now... there are only a few bones, and a flaming pile of logs.`n");
		output("A giant Stag runs out of the cave.... so much for heavy breathing!");
		output("`n`@`iMaybe in another village?`i");
		debuglog("found an empty dragon cave in ".$session['user']['location']);
		$num = e_rand(1,4);
		$found = false;
		$max = get_module_setting("maxsearch");
		switch ($num) {
		case 1:
			if (get_module_setting('gold') >0 &&
					(get_module_pref('gold')==0 || $max)) {
				$gold = get_module_setting('gold');
				output("`n`n`c`^You find %s gold!`c", $gold);
				$session['user']['gold']+=$gold;
				set_module_pref('gold',1);
				$found = true;
				debuglog("found $gold gold in an empty dragon cave");
			}
			break;
		case 2:
			if (get_module_setting('gems')>0 &&
					(get_module_pref('gems')==0 || $max)) {
				$gems = get_module_setting('gems');
				if ($gems==1) {
					output("`n`n`c`^You find a `%gem`^!`c");
				} else {
					output("`n`n`c`^You find `%%s gems`^!`c", $gems);
				}
				$session['user']['gems']+=$gems;
				set_module_pref('gems',1);
				debuglog("found $gems gems in an empty dragon cave");
				$found = true;
			}
			break;
		case 3:
			if (get_module_setting('hp')>0 &&
					(get_module_pref('hp')==0 || $max)) {
				output("`n`n`c`^You trip over a bone, and lose some hitpoints!`c");
				$session['user']['hitpoints']-=get_module_setting('hp');
				if ($session['user']['hitpoints']<=1) {
					$session['user']['hitpoints']=1;
				}
				set_module_pref('hp',1);
				$found = true;
			}
			break;
		}
		if (!$found) {
			output("`n`n`c`\$Nothing happens...`c");
		}
		$isforest = 0;
		$vloc = modulehook('validforestloc', array());
		foreach ($vloc as $i=>$l) {
			if ($l == $session['user']['location']) {
				$isforest = 1;
				break;
			}
		}
		if ($isforest) forest(true);
		else {
			require_once("lib/villagenav.php");
			villagenav();
		}
		break;
	}
	page_footer();
}

function dragonplace_choose() {
	global $session;

	// The main village has no way of getting to the dragon, so no need to
	// include it in the list!
	$vloc = array();
	$vloc = modulehook('validforestloc', $vloc);
	$vloc = array_keys($vloc);

	// Since we are only picking 1 element (the default), array_rand returns
	// the key itself, not an array.
	require_once("lib/e_rand.php");
	$i = e_rand(0, count($vloc)-1);
	set_module_pref('dragonloc',$vloc[$i]);

	// Okay, we now have the dragonloc, force the dragonloc2 to be the same.
	set_module_pref("dragonloc2", $vloc[$i]);
	debuglog("Setting dragon to be found in " . $vloc[$i]);

	// Now, IF we have a dkmin and the players DKs are less than dkmin, pick
	// a second loc.
	$min = get_module_setting("dkmin");
	if (count($vloc) > 1 && $min != 0 &&
			$session['user']['dragonkills'] < $min) {
		$i2 = $i;
		while($i == $i2 && count($vloc) > 1) {
			$i2 = e_rand(0, count($vloc)-1);
		}
		set_module_pref("dragonloc2", $vloc[$i2]);
		debuglog("NEWBIE: Setting dragon to also be found in " . $vloc[$i2]);
	}
}
?>

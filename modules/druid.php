<?php
/**ss**********************ss***************
 Druid (Forest Special) version 1.61
  released for LotGD 0.9.8
  Enhanced by JT (DragonCat)

  Instructions: LotGD 0.9.8
  Just drop into your /modules/ folder and install

  1.62 - Fixed issue with druid taking too many hitpoints
  		 when tempstat buff have been applied (XChrisX)

 By Strider & Talisman
 8.20.04 -scs-
**ss**************************ss************/
function druid_getmoduleinfo()
{
	$info = array (
		"name" => "Druid",
		"version" => "1.62",
		"author" => "Strider and Talisman<br>of Legendgard / Dragonprime",
		"category" => "Forest Specials",
		"download" =>"core_module",
		"settings"=>array(
			"Druid Forest Event Settings,title",
			"carrydk"=>"Do max hitpoints gained carry across DKs?,bool|0",
		),
		"prefs"=>array(
			"Druid Forest Event User Preferences,title",
			"extrahps"=>"How many extra max hitpoints has the user gained?,int",
		),
	);
	return $info;
}

function druid_install()
{
	module_addeventhook ("forest", "return 100;");
	module_addhook("hprecalc");
	return true;
}

function druid_uninstall()
{
	return true;
}

function druid_dohook($hookname, $args)
{
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

function druid_runevent ($type, $link)
{
	global $session;
	$session['user']['specialinc'] = "module:druid";

	$op = httpget ('op');
	if ($op == "" || $op == "search") {
		output("`n`2You enter a meadow, dominated by the largest `@Oak Tree `2you have ever seen.`n`n");
		output("`6Beneath the tree is a small cottage, an ancient figure standing before it.`n");
		output("`6Despite his elfin features, you instinctively know him to be a `%Druid Priest.`n`n");
		output("`@He offers you a bowl of `^Hearty Broth.");
		output("`@Unsure of his benevolence, what do you do?");
		addnav ("Accept the Broth", "forest.php?op=take");
		addnav ("Refuse the Broth", "forest.php?op=dont");
	} elseif ($op == "take") {
		$session['user']['specialinc'] = "";
		output("`n`@You take the `^Bowl of Broth`@ and raise it to your lips.");
		output("After a brief hesitation, you drink deeply.");
		output ("`@ You feel the `^Broth `@burning within you.`n`n ");
		switch (e_rand (1, 10)) {
		case 1:
			output("`6The broth fills you with ENERGY.`n`n");
			output("You receive `^two forest fights!`n");
			$session['user']['turns'] += 2;
			break;
		case 2:
			output("`6The broth enhances your vision.`n`n");
			output("You find `^2 Gems `6on the ground!`n");
			debuglog ("gained 2 Gems from Druid");
			$session['user']['gems'] += 2;
			break;
		case 3:
			output ("`6The broth must have had some oysters in it!`n`n");
			output ("`^You gain `^5 charm!");
			$session['user']['charm'] += 5;
			break;
		case 4:
			output ("`6The broth fills you with strength!`n`n");
			$hptype = "permanently";
			if (!get_module_setting("carrydk") ||
					(is_module_active("globalhp") &&
					 !get_module_setting("carrydk", "globalhp")))
				$hptype = "temporarily";
			$hptype = translate_inline($hptype);

			output("`6Your maximum hitpoints are `b%s`b `&increased`6 by 1!",
					$hptype);
			$session['user']['maxhitpoints'] += 1;
			$session['user']['hitpoints'] += 1;
			set_module_pref("extrahps", get_module_pref("extrahps") + 1);
			break;
		case 5:
		case 6:
			output ("`6You gag on the foul tasting liquid!`n`n");
			$dkhp=0;
			while(list($key,$val)=each($session['user']['dragonpoints'])){
				if ($val=="hp") $dkhp++;
			}
			$maxhitpoints = 10 * $session['user']['level'] + $dkhp * 5;
			suspend_temp_stats();
			if ($session['user']['maxhitpoints'] > $maxhitpoints) {
				$hptype = "permanently";
				if (!get_module_setting("carrydk") ||
						(is_module_active("globalhp") &&
						 !get_module_setting("carrydk", "globalhp")))
					$hptype = "temporarily";
				$hptype = translate_inline($hptype);
				$session['user']['maxhitpoints'] -= 1;
				set_module_pref("extrahps", get_module_pref("extrahps") - 1);
				output("`6Your maximum hitpoints are `b%s`b `\$decreased`6 by 1!`n", $hptype);
			}
			if ($session['user']['hitpoints'] > 3) {
				$session['user']['hitpoints'] -= 2;
				output("`6You `\$lose 2 `6hitpoints and gain some rather bad breath!");
			}
			restore_temp_stats();
			break;
		case 7:
			$expgain = round($session['user']['experience'] * .1, 0);
			if ($expgain < 10) $expgain = 10;
			$session['user']['experience'] += $expgain;
			output ("`6 Your faith was well placed, pilgrim!`n`n");
			output ("`6You gain `^%s experience`6!`n", $expgain);
			break;
		case 8:
			if ($session['user']['charm'] > 3) {
				output("`6The broth spills down your new tunic, detracting from your charm!`n`n");
				output("`6You `\$lose 2 `6charm!");
				$session['user']['charm'] -= 2;
			} else {
				output("`6The Elvish broth warms your body and gives your skin a healthy glow.");
				output("You `^gain 2 `6charm!");
				$session['user']['charm'] += 2;
			}
			break;
		case 9:
			$loss = round ($session['user']['hitpoints'] * .7, 0);
			if ($loss > 0 && $session['user']['hitpoints'] > $loss) {
				output("`6The broth leaves you feeling weakened.`n`n");
				output("You `\$lose `^%s `6hitpoints!", $loss);
				$session['user']['hitpoints'] -= $loss;
				break;
			}
		case 10:
			if($session['user']['turns'] >= 1) {
				output("`6The broth drains your ENERGY.`n`n");
				output("You `\$lose 1 `6forest fight!`n");
				$session['user']['turns'] -= 1;
			}
			else {
				output("`6Strangely, the broth has no effect on your tired body.`n`n");
			}
			break;
		}
	} else {
		$session['user']['specialinc'] = "";
		output("`n`@You simply don't trust the old druid and you decline his offer.");
		switch (e_rand (1, 4)) {
		case 1:
			$expgain = round ($session['user']['experience'] * .05, 0);
			if ($expgain < 3) $expgain = 3;
			$session['user']['experience'] += $expgain;
			output("As you turn to walk away, the old man begins to take a great offense.");
			output("Before he can fume about your refusal, you act quickly to ease the tension.");
			output("You humbly bow to the Druid, offering soft words of praise and peace as you back away from his sacred place.");
			output("You certainly don't want to upset him and you make your exit as polite as possible!`n`n");
			output ("`6You gain `^%s`6 experience from avoiding danger!`n",
					$expgain);
			break;
		case 2:
			if ($session['user']['charm'] > 3) {
				output("As you turn to walk away, the Druid is insulted by your refusal and throws the bowl of scalding soup after you.");
				output("You can almost hear the trees laughing as your clothing is stained with the foul broth.`n`n");
				output ("`6You `\$lose 2 `6charm!");
				$session['user']['charm'] -= 2;
			} else {
				output("As you turn to walk away, the Druid goes back to enjoying his broth and simply ignores your passing.");
			}
			break;
		case 3:
			$loss = round ($session['user']['hitpoints'] * .1, 0);
			output("As you turn to walk away, a loud voice booms in the air around you.");
			output("You dash into the forest, not waiting to see what powers you've offended.");
			if ($loss > 0 && $loss < $session['user']['hitpoints']) {
				$session['user']['hitpoints'] -= $loss;
				output("Recklessly trying to escape, you trip on some roots and injure yourself in a graceless fall.");
				output ("`n`6You `\$lose `^%s`6 hitpoints!`n", $loss);
			}
			break;
		case 4:
			output("`n`n`6Fearful for your safety, you run for hours.");
			if ($session['user']['turns'] > 0) {
				output("`n`nYou `\$lose 1 `6forest fight!`n");
				$session['user']['turns'] -= 1;
			}
			break;
		}
	}
}

function druid_run ()
{
}

/***ss*******************************
  Druid version 1.61 notes

TODO:
Work on making mods even more translator friendly.

ChangeLog-
Version 1.61 (JT & Strider)
- JT took the Red Marker to the Module
- Changed from Mac to UNIX Format Line Breakings (just for you little people :-P)
- Made Druid a little "Translator Friendly" and cleaned up the color some (more JT requests)

/ Version History:
Ver 1.6 (Strider)
- Upgraded Druid for LotGD 0.9.8
- Fixed some point accounting when the broth weakens you
- Now it won't kill you if you don't have HP to lose.

Ver 1.5 by Strider (of Legendgard)
- cleaned up a few errors
- rounded the numbers for clarity.
- added debugging for tracking
Ver 1 - First created 5Jan2004  -  By Talisman and Strider
- - - - - Special thanks to Robert - - - - - -

// -Contributors: Strider, Talisman, Robert
// Aug 2004  - 1.61 - released on DRAGONPRIME.NET
 **************************************ss***/
?>

<?php
// addnews ready
// mail ready
// translator ready
require_once("lib/constants.php");

//addnews ready
function oldman_getmoduleinfo(){
	$info = array(
		"name"=>"Old Man",
		"version"=>"1.1",
		"author"=>"Eric Stevens<br>Necromancer by Colin Harvie",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Old Man Forest Event Settings,title",
			"carrydk"=>"Do max hitpoints lost carry across DKs?,bool|1",
		),
		"prefs"=>array(
			"Old Man Forest Event User Preferences,title",
			"extrahps"=>"How many extra hitpoints has the user lost?,int",
		),
	);
	return $info;
}

function oldman_install(){
	module_addeventhook("forest", "return 100;");
	module_addhook("hprecalc");
	return true;
}

function oldman_uninstall(){
	return true;
}

function oldman_dohook($hookname,$args){
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

function oldman_bettinggame($from)
{
	global $session;
	$bet = abs((int)httpget('bet') + (int)httppost('bet'));
	if ($bet<=0){
		output("`@\"`#You have 6 tries to guess the number I am thinking of, from 1 to 100.  Each time I will tell you if you are too high or too low.`@\"`n`n");
		output("`@\"`#How much would you bet young %s?`@\"",
				translate_inline($session['user']['sex']?"lady":"man"));
		rawoutput("<form action='".$from."op=game' method='POST'>");
		rawoutput("<input name='bet' id='bet'>");
		$b = translate_inline("Bet");
		rawoutput("<input type='submit' class='button' value='$b'>");
		rawoutput("</form>");
		rawoutput("<script language='JavaScript'>document.getElementById('bet').focus();</script>");
		addnav("",$from."op=game");
		$session['user']['specialmisc']=e_rand(1,100);
	}elseif($bet>$session['user']['gold']){
		$session['user']['specialinc']="";
		$session['user']['specialmisc']="";
		output("`@The old man reaches out with his stick and pokes your coin purse.  \"`#I don't believe you have `^%s`# gold!`@\" he declares.`n`n", $bet);
		output("`@Desperate to really show him good, you open up your purse and spill out its contents: `^%s`@ gold.`n`n", (int)$session['user']['gold']);
		output("Embarrassed, you think you'll head back into the forest.");
	}else{
		$guess = (int)httppost('guess');
		$try = (int)httpget('try');
		if ($guess!==0 || $try >= 1){
			if ($guess==$session['user']['specialmisc']){
				if ($try == 1) {
					output("`@\"`#INCREDIBLE!!!!`@\" the old man shouts, \"`#You guessed the number in only `^one try`#! Well, congratulations to you, and I am thoroughly impressed! It is almost as if you read my mind.`@\"");
					output("He looks at you suspiciously and thinks about trying to make off with your winnings, but remembers your seemingly psychic abilities and hands over the `^%s`@ gold that he owes you.", $bet);
				} else {
					output("`@\"`#AAAH!!!!`@\" the old man shouts, \"`#You guessed the number in only %s tries!  It was `^%s`#!!  Well, congratulations to you, I think I'll just be going now... `@\" he says as he heads for the underbrush.`n`n", $try, $session['user']['specialmisc']);
					output("A swift blow from your `^%s`@ knocks him unconscious.`n`n", $session['user']['weapon']);
					output("You help yourself to his coinpurse, retrieving the `^%s`@ gold that he owes you.", $bet);
				}
				$session['user']['gold']+=$bet;
				debuglog("won $bet gold from the old man in the forest");
				$session['user']['specialinc']="";
				$session['user']['specialmisc']="";
			}else{
				if ($try>=6&&($guess>=0&&$guess<=100)){
					output("`@The old man chuckles.  \"`#The number was `^%s`#,`@\" he says.", $session['user']['specialmisc']);
					output("You, being the honorable citizen that you are, give the man the `^%s`@ gold that you owe him, ready to be away from here.", $bet);
					$session['user']['specialinc']="";
					$session['user']['specialmisc']="";
					$session['user']['gold']-=$bet;
					debuglog("lost $bet gold to the old man in the forest");
				}else{
					if($guess>100||$guess<0||!$guess){
						$try--;
						output("`@The old man chuckles, \"`#This will be like taking a sword from a baby if you think %s is between one and one hundred!`@\"`n", $guess);
						if(6-$try == 1) {
							output("`@\"`#You have `^1`# try left.`@\"`n");
						} else {
							output("`@\"`#You have `^%s`# tries left.`@\"`n", 6-$try);
						}
					} elseif ($guess>$session['user']['specialmisc']){
						output("`@\"`#Nope, not `^%s`#, it's lower than that!  That was try `^%s`#.`@\"`n`n", $guess, $try);
					}else{
						output("`@\"`#Nope, not `^%s`#, it's higher than that!  That was try `^%s`#.`@\"`n`n", $guess, $try);
					}
					output("`@You have bet `^%s`@.  What is your guess?", $bet);
					rawoutput("<form action='".$from."op=game&bet=$bet&try=".(++$try)."' method='POST'>");
					rawoutput("<input name='guess' id='guess'>");
					$g = translate_inline("Guess");
					rawoutput("<input type='submit' class='button' value='$g'>");
					rawoutput("</form>");
					rawoutput("<script language='JavaScript'>document.getElementById('guess').focus();</script>",true);
					addnav("",$from."op=game&bet=$bet&try=$try");
				}
			}
		}else{
			output("`@You have bet `^%s`@.  What is your guess?",$bet);
			rawoutput("<form action='".$from."op=game&bet=$bet&try=1' method='POST'>");
			rawoutput("<input name='guess' id='guess'>");
			$g = translate_inline("Guess");
			rawoutput("<input type='submit' class='button' value='$g'>");
			rawoutput("</form>");
			rawoutput("<script language='JavaScript'>document.getElementById('guess').focus();</script>",true);
			addnav("",$from."op=game&bet=$bet&try=1");
		}
	}
}

function oldman_runevent($type)
{
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:oldman";
	require_once("lib/partner.php");
	$partner = get_partner();

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`@You encounter a strange old man!`n`n");
		output("He beckons you over to talk with you.");
		output("He looks harmless enough, but you have heard tales of the evil creatures which lurk in this forest disguised as normal people.`n`n");
		addnav("Old Man");
		addnav("Talk with him", $from . "op=talk");
		addnav("Back away", $from . "op=chicken");
	} elseif ($op == "chicken") {
		$session['user']['specialinc'] = "";
		output("`@You back away slowly, and then when you are out of sight, turn and move quickly to another part of the forest.`n`n");
		if (e_rand(1,2)==2) {
			output("`@You are quite sure that your paranoia saved your life today.");
		} else {
			output("`@You are quite sure that %s`@ would think you are a wuss for being scared of an old man.", $partner);
			if ($session['user']['sex'] == SEX_MALE) {
				output("Fortunately, she isn't here to see your cowardice.");
			} else {
				output("Fortunately, he isn't here to see your cowardice.");
			}
		}
	} elseif ($op == "talk") {
		// Okay.. now we get to have fun.  Which old man do they get?
		switch (e_rand(1,5)) {
		case 1:
			// This is the pretty stick.
			$session['user']['specialinc'] = "";
			output("`@As you approach, he pulls out his Pretty Stick, whacks you on the temple, giggles, and runs away!`n`n");
			output("`^You `%gain one`^ charm point!");
			$session['user']['charm']++;
			break;
		case 2:
			// The ugly stick
			$session['user']['specialinc'] = "";
			if ($session['user']['charm'] > 0) {
				output("`@As you approach, he pulls out his Ugly Stick, whacks you on the nose, giggles, and runs away!`n`n");
				output("`^You `%lose one`^ charm point!");
				$session['user']['charm']--;
			} else {
				output("`@As you approach, he pulls out his Ugly Stick, whacks you on the nose, then gasps as his stick `%loses one`@ charm point.`n`n");
				output("`@He quickly recovers his composure and runs away!`n`n");
				output("Dang! You're even uglier than his Ugly Stick!");
			}
			break;
		case 3:
			// The lost man.
			output("`@\"`#I am lost,`@\" he says, \"`#can you lead me back to town?`@\"`n`n");
			output("You know that if you do, you will lose time for a forest fight for today.`n`n");
			output("Will you help out this poor old man?");
			addnav("Old Man");
			addnav("Walk him to town", $from."op=walk");
			addnav("Leave him here",$from."op=leavehim");
			break;
		case 4:
			// The betting game
			output("`@\"`#Would you like to play a little guessing game?`@\", he asks.");
			output("Knowing his sort, you know he will probably insist on a small wager if you do.`n`n");
			output("`@Do you wish to play his game?`n`n");
			addnav("Old Man");
			addnav("Play game", $from . "op=game");
			addnav("Leave", $from . "op=nogame");
			break;
		case 5:
			// The necromancer
			output("`@As you approach the old man, his face twists into a maniacal, evil grin.");
			switch (e_rand(1, 15)) {
			case 1:
				$session['user']['specialinc']="";
				output("`@When you reach him, he mutters, \"`#Didn't y' own mother teach you never t' talk t' strangers?`@\"");
				output("The old necromancer cackles and pulls out a black wand, waving it quickly over your head.`n`n");
				output("`@You feel a searing pain as your soul is forcibly ripped from your body and cast into the underworld to fuel his evil spells!`n`n");
				output("`^Your spirit has been ripped from your body!`n");
				output("`^That treacherous old man searches your body and takes all of your gold.`n");
				output("You lose 5% of your experience!`n");
				output("You may continue playing again tomorrow.");
				$session['user']['alive']=false;
				$session['user']['hitpoints']=0;
				$session['user']['experience']*=.95;
				$session['user']['gold']=0;
				addnav("Daily News", "news.php");
				addnews("The body of %s was found in the woods, stripped of all gold and with dark symbols drawn upon it.",$session['user']['name']);
				break;
			case 2:
			case 3:
				$session['user']['specialinc']="";
				output("`@When you reach him, he mutters, \"`#Aye, me %s, come a wee bit closer.  That's it, just a bit CLOSER!`@\"`n`n", translate_inline($session['user']['sex']?"lass":"lad"));
				output("As the old necromancer screams that last word, he pulls out a black wand and your body twists in agony as if molten fire has replaced your blood.`n`n");
				output("Your vision goes dark and you feel the hand of %s`@ closing around your heart.", getsetting("deathoverlord", '`$Ramius'));
				output("Just as you are sure you will die, the pain stops as quickly as it began.`n`n");
				output("You climb to your feet, shaking and weak.");
				output("The old necromancer is nowhere to be seen.`n`n");
				output("`@You feel you will never be quite the %s you were before.`n`n", translate_inline($session['user']['sex']?"woman":"man"));
				if ($session['user']['maxhitpoints'] >
						$session['user']['level'] * 10) {
					$session['user']['maxhitpoints']--;
					set_module_pref("extrahps", get_module_pref("extrahps")-1);
					$hptype = "permanently";
					if (!get_module_setting("carrydk") ||
							(is_module_active("globalhp") &&
							 !get_module_setting("carrydk", "globalhp")))
						$hptype = "temporarily";
					$hptype = translate_inline($hptype);

					output("`^You `b%s`b `\$lose`^ one hitpoint!`n", $hptype);
				}
				$loss = round($session['user']['maxhitpoints'] * .25, 0);
				if ($loss > $session['user']['hitpoints'])
					$loss = $session['user']['hitpoints']-1;
				output("`^You have taken `\$%s`^ damage from wounds.", $loss);
				$session['user']['hitpoints'] -= $loss;
				if ($session['user']['sex']) {
					$msg = "%s came home from the forest, a bit less the woman than she was before.";
				} else {
					$msg = "%s came home from the forest, a bit less the man than he was before.";
				}
				addnews($msg, $session['user']['name']);
				break;
			case 4:
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
			case 10:
				output("`@When you reach him, he mutters, \"`#A gem for me own self, yes?`@\"`n`n");
				output("Do you give him a gem?");
				addnav("Old Man");
				addnav("Give him a gem", $from . "op=givegem");
				addnav("Keep your gems", $from . "op=keepgem");
				break;
			case 11:
			case 12:
			case 13:
			case 14:
			case 15:
				$session['user']['specialinc']="";
				output("`@You are almost in front of him, when you hear a crashing sound coming from your right.`n`n");
				output("You turn to look and see one of the bodyguards from the Inn walking through the woods heading your direction.");
				output("You turn back to the old man, but he seems to have vanished.`n`n");
				output("Oh well, you'll never know what he wanted now.");
				break;
			}
			break;
		}
	} elseif ($op == "walk") {
		// Walking the oldman (#3) to town
		$session['user']['turns']--;
		output("`@You take the time to lead the old man back to town.`n`n");
		if (e_rand(0, 1) == 0) {
			output("`@In exchange, he whacks you with his Pretty Stick and you `%gain one`@ charm point.");
			$session['user']['charm']++;
		} else {
			output("`@In exchange, he gives you `%a gem`@!");
			$session['user']['gems']++;
			debuglog("gained 1 gem for walking old man to village");
		}
	} elseif ($op == "leavehim") {
		$session['user']['specialinc'] = "";
		// Being a cruel insensitive clod who hates old men.
		output("`@You tell the old man that you are far too busy to aid him.`n`n");
		output("`@Not a big deal, he should be able to find his way back to town on his own, he made his way out here, didn't he?");
		output("A wolf howls in the distance to your left, and a few seconds later one howls somewhere closer to your right.");
		output("Yep, he should be fine.");
	} elseif ($op == "nogame") {
		// Penny-pincher :)
		$session['user']['specialinc']="";
		output("`@Afraid to part with your precious precious money, you decline the old man his game.`n`n");
		output("There wasn't much point to it anyhow, as you certainly would have won.`n`n");
		output("Yep, definitely not afraid of the old man, nope.");
	} elseif ($op == "game") {
		if ($session['user']['gold'] <= 0) {
			$session['user']['specialinc']="";
			output("`@The old man reaches out with his stick and pokes your coin purse.  \"`#Empty?!?!  How can you bet with no money??`@\" he shouts.");
			output("With that, he turns with a HARUMPH, and disappears into the underbrush.");
		} else {
			oldman_bettinggame($from);
		}
	} elseif ($op == "givegem") {
		if ($session['user']['gems'] <= 0) {
			$session['user']['specialinc']="";
			output("`@You reach into your pack to find that you have no gems.");
			output("The old man looks at you expectantly.`n`n");
			output("When he sees you have no gems, he starts to frown.");
			output("Sensing trouble, you turn to flee toward the forest.`n`n");
			output("From behind you, you hear an evil laugh and feel a sharp pain in your back!`n`n");
			$loss = round($session['user']['maxhitpoints'] * .2, 0);
			if ($loss > $session['user']['hitpoints'])
				$loss = $session['user']['hitpoints']-1;
			output("`^You have taken `\$%s`^ damage from wounds.", $loss);
			$session['user']['hitpoints'] -= $loss;
		} else {
			$session['user']['specialinc']="";
			output("`@Feeling sorry for the old man, you reach into your pack and extract a gem which he snatches eagerly from your hand.`n");
			$session['user']['gems']--;
			switch (e_rand(1,6)) {
			case 1:
			case 2:
			case 3:
				output("`@He cackles with glee.");
				output("He turns to you and says, \"`#Since y' made such a fine bargain w' me, me %s, I'll be puttin' in a good word for y' with m' ol' friend %s`#.`@\"`n`n", translate_inline($session['user']['sex']?"deary":"lad"), getsetting("deathoverlord", '`$Ramius'));
				output("`@The necromancer pulls out a black wand, raps you thrice on the head, and runs off into the forest.`n`n");
				$favor = e_rand(5, 20);
				output("`^You gain `&%s`^ favor with `\$Ramius`^.", $favor);
				$session['user']['deathpower']+=$favor;
				break;
			case 4:
			case 5:
				if (is_module_active("specialtydarkarts")) {
					output("`@He cackles with glee.");
					output("He turns to you and says, \"`#Since y' made such a fine bargain w' me, me %s, I'll be teachin' y' a bit of m' art!`@\"`n`n", translate_inline($session['user']['sex']?"deary":"lad"));
					output("`@The necromancer pulls out a black wand, raps you thrice on the head, and runs off into the forest.`n`n");
					output("`^You feel knowledge of the `\$Dark Arts`^ settle into your brain like a stain of blood into straw.`n");
					require_once("lib/increment_specialty.php");
					increment_specialty('`$', 'DA');
					break;
				}
				// Fall through if we don't have dark arts enabled.
			case 6:
				output("`@He runs off into the forest.`n`n");
				output("That greedy old man stole your gem!");
			}
		}
	} elseif ($op == "keepgem") {
		$session['user']['specialinc']="";
		output("`@Not wanting to part with one of your precious gems, you turn around and march back toward the forest.`n`n");
		output("From behind you, you hear an evil laugh and feel a sharp pain in your back!`n`n");
		$loss = round($session['user']['maxhitpoints'] * .1, 0);
		if ($loss > $session['user']['hitpoints'])
			$loss = $session['user']['hitpoints']-1;
		output("`^You have taken `\$%s`^ damage from wounds.", $loss);
		$session['user']['hitpoints'] -= $loss;
	}
	output("`0");
}

function oldman_run(){
}
?>

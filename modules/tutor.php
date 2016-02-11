<?php
// addnews ready
// mail ready
// translator ready

function tutor_getmoduleinfo(){
	$info = array(
		"name"=>"In-game tutor",
		"author"=>"Booger & Shannon Brown & JT Traub",
		"version"=>"1.0",
		"category"=>"Administrative",
		"download"=>"core_module",
		"prefs"=>array(
			"In-Game tutor User Preferences,title",
			"user_ignore"=>"Turn off the tutor help?,bool|0",
			"seenforest"=>"Has the player seen the forest instructions,bool|0",
			),
		);
	return $info;
}

function tutor_install(){
	module_addhook("everyheader-loggedin");
	module_addhook("newday");
	module_addhook("village");
	module_addhook("battle");
	return true;
}

function tutor_uninstall(){
	return true;
}

function tutor_dohook($hookname,$args){
	global $session;
	$age = $session['user']['age'];
	$ignore = get_module_pref("user_ignore");

	// If this person is already well out of tutoring range, just return
	if ($session['user']['dragonkills'] || $ignore || $age >= 11) {
		return $args;
	}

	switch($hookname){
	case "newday":
		set_module_pref("seenforest", 0);
		break;
	case "village":
		if ($age < 11){
			tlschema($args['schemas']['gatenav']);
			addnav($args["gatenav"]);
			tlschema();
			addnav("*?`\$Help Me, I'm Lost!", "runmodule.php?module=tutor&op=helpfiles");
			unblocknav("runmodule.php?module=tutor&op=helpfiles");
		};
		break;
	case "battle":
		global $options;
		$badguy = $args[0];
		$tutormsg = "";
		if ($badguy['creaturehealth'] > 0 && $badguy['creaturelevel'] > $session['user']['level'] && $options['type'] == 'forest'){
			$tutormsg = translate_inline("`#Eibwen`0 looks agitated!  \"`\$Look out!`3 This creature looks like it is a higher level than you!  You might want to `^run away`3! You might not be successful, but keep trying and hope you get away before you're turned into forest fertilizer!`0\"`n");
		}
		if ($tutormsg) tutor_talk("%s", $tutormsg);
	case "everyheader-loggedin":
		$adef = $session['user']['armordef'];
		$wdam = $session['user']['weapondmg'];
		$gold = $session['user']['gold'];
		$goldinbank = $session['user']['goldinbank'];
		$goldtotal = $gold+$goldinbank;
		if(!isset($args['script']) || !$args['script']) break;
		switch($args['script']){
		case "newday":
			if ($age > 1) break;
			if ((!$session['user']['race'] ||
						$session['user']['race']==RACE_UNKNOWN) &&
					httpget("setrace")==""){
				if (is_module_active("racetroll"))
					$troll=translate_inline("Troll");
				if (is_module_active("racedwarf"))
					$dwarf=translate_inline("Dwarf");
				if (is_module_active("racehuman"))
					$human=translate_inline("Human");
				if (is_module_active("raceelf"))
					$elf=translate_inline("Elf");
				if ($troll || $dwarf || $human || $elf) {
					$tutormsg = translate_inline("`0A tiny `#aqua-colored imp`0 flies up and buzzes beside your head for a moment.`n`n\"`&Wha-wha-wha...`0\" you stammer.`n`n\"`#Oh, hush up you.  You're supposed to listen to me, not talk!`0\" the imp squeaks.`n`n\"`#Now, I'm here to help you get familiar with these realms, so you better listen close to what I've got to say.`0\"`n`nYou nod dumbly for a moment then give this being your attention.`n`n\"`#Now,`0\" it says,\" `#you're only young, and maybe you don't remember where you grew up. If you've never been in here before, choosing one of these is probably easiest!`0\" He jumps about excitedly, waiting for your decision, and waves a list of suggestions in front of you.`n");
					tutor_talk("%s`c`b`#%s`n%s`n%s`n%s`n`b`c", $tutormsg, $troll, $elf, $human, $dwarf);
				};
			}elseif ($session['user']['specialty']=="" && !httpget("setrace")){
				if (is_module_active("specialtydarkarts"))
					$da=translate_inline("Dark Arts");
				if (is_module_active("specialtymysticpower"))
					$mp=translate_inline("Mystical Powers");
				if (is_module_active("specialtythiefskills"))
					$ts=translate_inline("Thieving Skills");
				if ($da || $mp || $ts){
					$tutormsg = translate_inline("`0The bug flutters about you, no matter how much you try to swat him from view. A moment later his piercing chatter returns.`n`n\"`#Oh, look, more decisions! I suppose you want some career counseling now?`0\"`n`nHe buzzes about, before imparting, \"`#Why not try one of these first, so you won't trip over your own shoelaces?`0\"`n`nHe holds a small scroll before you, embossed with small script, and awaits your decision.`n");
					tutor_talk("%s`c`b`#%s`n%s`n%s`b`c", $tutormsg, $da, $mp, $ts);
				}
			}
			break;
		case "village":
			$tutormsg = "";
			if ($wdam == 0 && $gold >= 48){
				$tutormsg = translate_inline("\"`3You really should get a weapon, to make you stronger. You can buy one at the `^weapon shop`3. I'll meet you there!`0\"`n");
			}elseif($wdam == 0 && $goldtotal >= 48){
				$tutormsg = translate_inline("\"`3We need to withdraw some gold from `^the bank`3 to buy a weapon, Come with me!`0\"`n");
			}elseif ($adef == 0 && $gold >= 48){
				$tutormsg = translate_inline("\"`3You won't be very safe without any armor! The `^armor shop`3 has a nice selection. Let's go!`0\"`n");
			}elseif ($adef == 0 && $goldtotal >= 48){
				$tutormsg = translate_inline("\"`3We need to withdraw some gold from `^the bank`3, so we can buy some armor!`0\"`n");
			}elseif (!$session['user']['experience']){
				$tutormsg = translate_inline("\"`3The `^forest`3 is worth visiting, too. That's where you gain experience and gold!`0\"`n");
			}elseif ($session['user']['experience'] > 100 && $session['user']['level'] == 1 && !$session['user']['seenmaster']){
				$tutormsg = translate_inline("\"`3Holy smokes!  You're advancing so fast!  You have enough experience to reach level 2.  You should find the `^warrior training`3, and challenge your master!  After you've done that, you'll find you're much more powerful.`0\"`n");
			}
			if ($tutormsg) tutor_talk("%s", $tutormsg);
			break;
		case "forest":
			$tutormsg = "";
			if ($goldtotal >= 48 && $wdam == 0){
				$tutormsg = translate_inline("\"`3Hey, you have enough gold to buy a weapon. It might be a good idea to visit `^the town`3 now and go shopping!`0\"`n");
			}elseif($goldtotal >= 48 && $adef == 0){
				$tutormsg = translate_inline("\"`3Hey, you have enough gold to buy some armor. It might be a good idea to visit `^the town`3 now and go shopping!`0\"`n");
			}elseif (!$session['user']['experience'] && !get_module_pref("seenforest")){
				$tutormsg = translate_inline("`#Eibwen`& flies in loops around your head. \"`3Not much to say here.  Fight monsters, gain gold, heal when you need to.  Most of all, have fun!`0\"`n`nHe flies off back toward the village.`n`nOver his shoulder, he calls out, \"`3Before I go, please read the FAQs... and the Message of the Day is something you should check each time you log in. Don't be afraid to explore, but don't be afraid to run away either! And just remember, dying is part of life!`0\"`n");
				set_module_pref("seenforest", 1);
			};
			if ($tutormsg) tutor_talk("%s", $tutormsg);
			break;
		}
		break;
	}
	return $args;
}

function tutor_talk() {
	rawoutput("<style type='text/css'>
		.tutor {
			background-color: #444444;
			border-color: #0099ff;
			border-style: double;
			border-width: medium;
			color: #CCCCCC;
		}
		.tutor .colDkBlue	{ color: #0000B0; }
		.tutor .colDkGreen   { color: #00B000; }
		.tutor .colDkCyan	{ color: #00B0B0; }
		.tutor .colDkRed	 { color: #B00000; }
		.tutor .colDkMagenta { color: #B000CC; }
		.tutor .colDkYellow  { color: #B0B000; }
		.tutor .colDkWhite   { color: #B0B0B0; }
		.tutor .colLtBlue	{ color: #0000FF; }
		.tutor .colLtGreen   { color: #00FF00; }
		.tutor .colLtCyan	{ color: #00FFFF; }
		.tutor .colLtRed	 { color: #FF0000; }
		.tutor .colLtMagenta { color: #FF00FF; }
		.tutor .colLtYellow  { color: #FFFF00; }
		.tutor .colLtWhite   { color: #FFFFFF; }
		.tutor .colLtBlack   { color: #999999; }
		.tutor .colDkOrange  { color: #994400; }
		.tutor .colLtOrange  { color: #FF9900; }
		</style>");
	$args = func_get_args();
	$args[0] = translate($args[0]);
	$text = call_user_func_array("sprintf", $args);
	rawoutput("<div class='tutor'>");
	rawoutput(tlbutton_clear().appoencode($text));
	rawoutput("</div>");
}

function tutor_runevent($type){
}

function tutor_run(){
	global $session;
	$op = httpget("op");
	$city= getsetting("villagename", LOCATION_FIELDS); // name of capital city
	$iname = getsetting("innname", LOCATION_INN); // name of capital's inn
	$age = $session['user']['age'];
	if ($op=="helpfiles") {
		page_header("Help!");
		output("`%`c`bHelp Me, I'm Lost!`b`c`n");
		output("`@Feeling lost?`n`n");
		output("`#Legend of the Green Dragon started out small, but with time it has collected many new things to explore.`n`n");
		output("To a newcomer, it can be a little bit daunting.`n`n");
		output("To help new players, the Central staff created Eibwen, the imp.");
		output("He's the little blue guy who told you to buy weapons when you first joined, and helped you choose a race.");
		output("But what happens next, where should you go, and what are all the doors, alleys, and shops for?`n`n");
		output("First of all: The game is about discovery and adventure.");
		output("For this reason, you won't find all the answers to every little question.");
		output("For most things, you should read the FAQs, or just try them and see.`n`n");
		output("But we recognize that some things aren't at all obvious.");
		output("So while we won't tell you what everything does, we've put together a list of things that you might want to try first, and that new players commonly ask us.`n`n");
		output("Please understand that these hints are spoilers.");
		output("If you'd rather discover on your own, don't read any further.`n`n");
		output("`%What are all those things in my Vital Info, and Personal Info, I'm confused?");
		output("A lot of it you don't need to worry about for the most part.");
		output("The ones you should watch carefully are your hitpoints, and your experience.");
		output("Ideally, you should keep that hitpoint bar green.");
		output("And beware if it begins to turn yellow, or worse still, red.");
		output("That tells you that death is near.");
		output("Sometimes running would be smarter than risking death.");
		output("Perhaps there's someone close by who can help you feel better.`n`n");
		output("Lower down is the experience bar, which starts all red, and will gradually fill up with white.");
		output("Wait until it goes blue before you challenge your master.");
		output("If you can't see a blue bar, you aren't ready yet!`n`n");
		output("Looking for someone you know?");
		output("The List Warriors area will tell you if your friend is online right now or not.");
		output("If they are, Ye Olde Mail is a good way to contact them.`n`n");
		output("What are gems for?");
		output("Hang onto these and be careful how you spend them.");
		output("There are some things that you can only obtain with gems.`n`n");
		output("Have you been into %s, in %s? Perhaps you'd like to try a drink, listen to some entertainment, or chat to people.",$iname, $city);
		output("It's also a good idea to get to know the characters in the %s, because they can be quite helpful to a young warrior.",$iname);
		output("You might even decide that sleeping in %s would be safer than in the fields.`n`n",$iname);
		output("Travelling can be dangerous.");
		output("Make sure you've placed your valuables somewhere safe, and that you're feeling healthy before you leave.`n`n");
		output("Hungry, tired, feeling adventurous, or looking for a pet?");
		output("The Spa, the Kitchen, the Tattoo Parlor, and the Stables are all places you might want to visit.");
		output("These things are just some of the shops in different towns.");
		output("Some of them give turns, charm or energy, and some take it away.`n`n");
		output("Where's the dragon?");
		output("They all ask this.");
		output("You'll see her when you are ready to fight her, and not before, and you will need to be patient and build your strength while you wait.`n`n");
		output("`QIf you have any questions which are not covered in the FAQ, you may wish to Petition for Help - bear in mind that the staff won't give you the answer if it will spoil the game for you.");
		villagenav();
		page_footer();
	}
}
?>

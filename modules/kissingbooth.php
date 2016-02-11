<?php

/* Kissing Booth */
/* ver 1.1 by Chris Murray */
/* 19th Sep 2005 */
/* thanks dan */

function kissingbooth_getmoduleinfo()
{
	$info = array(
		"name"=>"The Kissing Booth",
		"version"=>"1.1",
		"author"=>"Chris Murray",
		"category"=>"Village", // or, y'know, not
		"download"=>"core_module",
		"settings"=>array(
			"Kissing Booth - Settings,title",
			"gardens"=>"Does the booth appear in the gardens? (setting yes nullifies city selector below),bool|0",
			"kissingboothloc"=>"Where does the booth appear,location|".getsetting ("villagename", LOCATION_FIELDS),
			"cost"=>"How much gold do you have to pay per level to make with the kissing,int|5",
			"smackloss"=>"Percent of hitpoints that can be lost upon being smacked,range,2,100,2|10"
		),
		"prefs"=>array(
			"Kissing Booth - User Preferences,title",
			"kissedtoday"=>"Has the user been kissed already today?,bool|0"
		)
	);
	return $info;
}

function kissingbooth_install()
{
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
	module_addhook("gardens");
	return true;
}

function kissingbooth_uninstall()
{
	return true;
}

function kissingbooth_dohook($hookname,$args)
{
	global $session;
	$gardens=get_module_setting("gardens");
	switch($hookname){
	case "newday":
		set_module_pref("kissedtoday",0);
		set_module_pref("kissee","");
		break;
	case "changesetting":
		if (!$gardens && $args['setting'] == "villagename") {
			if ($args['old'] = get_module_setting("kissingboothloc")) {
				set_module_setting("kissingboothloc", $args['new']);
			}
		}
		break;
	case "village":
		if (!$gardens && $session['user']['location']==get_module_setting('kissingboothloc')) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("The Kissing Booth","runmodule.php?module=kissingbooth");
		}
		break;
	case "gardens":
		if ($gardens) {
			addnav("Kissing Booth","runmodule.php?module=kissingbooth");
		}
		break;
	}
	return $args;
}

function kissingbooth_run()
{
	global $session;

	require_once("lib/villagenav.php");
	$gardens=get_module_setting("gardens");
	$smackloss = get_module_setting("smackloss");
	$cost = get_module_setting("cost");
	$totalcost = $cost*$session['user']['level'];
	page_header("The Kissing Booth");
	$op = httpget('op');
	$kissedtoday=get_module_pref("kissedtoday");
	$kissee=get_module_pref("kissee");
	output("`c`b`QThe `\$Kissing`Q Booth`0`b`c`n");
	if ($op == "") {
		while ($kissee == "") {
			$guys = array(getsetting("barkeep", '`tCedrik'), "`!Lonestrider", "`@JR`3Min`!ga", "`6Smiythe", getsetting("bard", "`^Seth"), "`&Oliver", "`#MightyE", "`%Kendaer");
			$girls = array("`)Heidi", "`7Petra", "`QSaucy`\$Wench", "`@Foil`&wench", getsetting('barmaid', "`%Violet"), "`^Ella");
			if ($session['user']['sex']!=SEX_MALE) {
				$kissee = $guys[e_rand(0, count($guys)-1)];
			} else {
				$kissee = $girls[e_rand(0, count($girls)-1)];
			}
			set_module_pref("kissee",$kissee);
		}

		// will change this if it's in a town (which town? romar? glorfy?
		// d'burg? rather than a special area (carnival, esoterron (ew!))

		output("`QYou walk towards the booth marked \"`@LOGD Kissing Booth`Q\".`n");
		output("`QThere seems to be a decent line, possibly because %s`Q is behind the booth today!`n", $kissee);

		if (!$kissedtoday) {
			output("`QYou find yourself shuffled into the line, which seems to suddenly be moving fairly quickly.`n");
			output("`QFinally, you reach the front!");
			output("`QYour eyes dart to the jar marked \"`@Donations`Q\", and then meet the gaze of %s`Q, who looks up at you expectantly.`n", $kissee);
			if ($totalcost > $session['user']['gold']) {
				output("`!You can't afford the donation at the moment.");
				output("%s`! smiles at you sadly as you slink off, dejected.",
						$kissee);
				if ($gardens) addnav("G?Return to the Gardens","gardens.php");
				else villagenav();
			} else {
				output("`QWhat will you do?");
				addnav(array("Make donation (%s gold)", $totalcost),
						"runmodule.php?module=kissingbooth&op=stay");
				addnav("Chicken out",
						"runmodule.php?module=kissingbooth&op=flee");
			}
		} else {
			output("`Q`nYou know, that line seems really long, and you've already taken your turn today.`n");
			output("`QMaybe you should wait until later, and let somebody else have their fun.");
			if ($gardens) addnav("G?Return to the Gardens","gardens.php");
			else villagenav();
		}
	}elseif ($op=="flee") {
		output("`#You freeze with embarrasment at the thought of kissing %s`#.`n", $kissee);
		output("`#After about a minute has passed and you're still standing there stammering, %s`# sighs and beckons the person behind you to come forward and take your place.`n", $kissee);
		output("`#A helper gently takes you aside and seats you under a nearby tree to recover.");
		if ($gardens) addnav("Leave","gardens.php");
		else addnav("Leave","village.php");
	}elseif ($op=="stay") {
		set_module_pref("kissedtoday",1);
		$session['user']['gold'] -= $totalcost;
		if ($session['user']['sex']!=SEX_MALE){
			$heshe = "he";
			$Cheshe = "He"; // "capital he/she." capital idea, wot?
			$hisher = "his";
		}else{
			$heshe = "she";
			$Cheshe = "She";
			$hisher = "her";
		}
		$rnd = e_rand(1,16);
		switch ($rnd){
		case 1: case 2: case 3: case 4: case 5:
			//charm++ (1-5)
			output("`6You drop the coins in the jar.`n");
			output("%s`6 leans forward and kisses you gently on the lips.",
					$kissee);
			output("$Cheshe holds it slightly longer than you expected and pulls back with an impressed look on $hisher face.`n");
			output("\"`#Not bad`6\", $heshe says, \"come back anytime!\"`n");
			output("You blush slightly, but find a new confidence in your step as you walk away.`n");
			output("You feel `\$charming`6!");
			$session['user']['charm']++;
			break;
		case 6: case 7: case 8: case 9:
			//hp++ (6-9) (tee hee)
			output("`6Stepping forward, you deposit your coins in the jar.`n");
			output("%s`6 leans forward and kisses you softly but warmly.`n",
					$kissee);
			output("`6The warmth floods your body, and eases your pain.`n");
			output("%s`6 smiles and waves as you wander off.", $kissee);
			if ($session['user']['hitpoints'] <
					$session['user']['maxhitpoints']) {
				$session['user']['hitpoints'] =
					$session['user']['maxhitpoints'];
			}else{
				$session['user']['hitpoints'] =
					($session['user']['hitpoints']*1.1);
			}
			break;
		case 10: case 11: case 12:
			//ff++ (10-12)
			output("`6You place your donation in the jar.`n");
			output("%s`6's eyes light up as you approach, and $heshe kisses you with enthusiasm.`n", $kissee);
			output("`6You feel an energy spread throughout your entire body, making you `^tingle!`n`n");
			output("`6As you walk away, you feel a light tap on your bottom!`n");
			output("`6You turn around to see %s`6 winking at you! \"`#Go get 'em, tiger!`6\", $heshe says.`n", $kissee);
			output("`6The tingling fills you, and you think you can take on another forest creature!`n");
			$session['user']['turns']++;
			break;
		case 13: case 14:
			//charm-- (13-14)
			output("`6You eagerly place your donation in the jar and rush forward to kiss %s.`n", $kissee);
			output("`6However, in your enthusiasm, you fumble the kiss and end up bumping noses!`n");
			output("`6You look up and see nearby people trying to stifle giggles!`n");
			output("`6You wander off, feeling somewhat embarrassed.`n`n");
			output("`6However, looking down as you walk off, you spot something `#shiny`6 in the dust!`n");
			output("`QYou lose charm, but you gain a gem!`n");
			if ($session['user']['charm'] > 0){
				$session['user']['charm']--;
			}
			$session['user']['gems']++;
			debuglog("gained a gem at the kissing booth");
			break;
		case 15:
			//hp-- (15)
			output("`6You drop your coins in the jar and reach forward to kiss %s.`n", $kissee);
			output("`6\"`#Hey! Not so hard!`6\", $heshe yells, and smacks you across the face!`n");
			$loss = round($session['user']['maxhitpoints'] *
					($smackloss/100), 0);
			if ($loss >= $session['user']['hitpoints'])
				$loss = $session['user']['hitpoints'] - 1;
			output("`QYou `\$lose %s `Q hitpoints!`n", $loss);
			$session['user']['hitpoints']-=$loss;
			if ($session['user']['hitpoints']<=0){
				$session['user']['hitpoints']=1;
			}
			break;
		case 16:
			//ff-- (16)
			output("`6As you walk forward and place your donation in the jar, your foot finds a banana peel thrown by an errant reveller.`n");
			output("`6You attempt to maintain your balance, but pitch forward and hit your head on one of the posts holding up the booth!`n");
			output("`6When you come to, %s`6 is cradling your head in $hisher lap.`n", $kissee);
			output("$Cheshe `6bends down and kisses you on the forehead, and sets you on your feet.`n");
			if ($session['user']['turns'] > 0) {
				output("`QYou `\$lost`Q a forest fight while unconscious!`n");
				$session['user']['turns']--;
			}
			break;
		}
		if ($gardens) addnav("G?Return to the Gardens","gardens.php");
		else villagenav();
	}
	page_footer();
}
?>

<?php
// addnews ready
// mail ready
function golinda_getmoduleinfo(){
	$info = array(
		"name"=>"Golinda",
		"author"=>"JT Traub, slightly modified by Christian Rutsch",
		"version"=>"1.1",
		"category"=>"Lodge",
		"download"=>"htpp://www.dragonprime.net/users/XChrisX/golinda.zip",
		"settings"=>array(
			"Golinda Module Settings,title",
			"points"=>"How many points are needed to see Golinda during a day?,int|50",
			"costpercent"=>"How much of the normal healing cost is Golinda?,range,0,100,5|50",
			"blockhealer"=>"Block the Healer's nav if Golinda is available?,bool|0",
		),
		"prefs"=>array(
			"Golinda Module User Preferences,title",
			"paidtoday"=>"Has user already paid donation points today?,bool|0",
		),
	);
	return $info;
}

function golinda_install(){
	module_addhook("lodge");
	module_addhook("newday");
	module_addhook("pointsdesc");
	module_addhook("forest");
	return true;
}
function golinda_uninstall(){
	return true;
}

function golinda_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("The ability to visit Golinda, the Lodge Healer costs %s points per day.  Golinda's rates are %s%% those of the normal healer.");
		$str = sprintf($str, get_module_setting("points"),
			get_module_setting("costpercent"));
		output($format, $str, true);
		break;
	case "newday":
		set_module_pref("paidtoday", 0);
		break;
	case "forest":
		if (get_module_pref("paidtoday")) {
			if (get_module_setting("blockhealer")) blocknav("healer.php", true);
			addnav("Heal");
			addnav("Visit Golinda (already paid)","runmodule.php?module=golinda&op=enter&from=forest");
		}
		break;
	case "lodge":
		// Reimburse people points spent for old version of Golinda
		$config = unserialize($session['user']['donationconfig']);
		if (!is_array($config)) $config = array();
		if (array_key_exists('healer', $config) && $config['healer']) {
			$points = 150*round($config['healer']/10, 0);
			$points += 20*round($config['healer']%10, 0);
			if ($points > $session['user']['donationspent'])
				$points = $session['user']['donationspent'];
			$session['user']['donationspent'] -= $points;
			unset($config['healer']);
			if ($points) {
				output("`^Due to changes in the way in which Golinda is handled, you have been refunded %s donation points!`n`n", $points);
			}
		}
		$session['user']['donationconfig'] = serialize($config);

		$cost = get_module_setting("points");
		if (get_module_pref("paidtoday")) {
			addnav("Visit Golinda (already paid)","runmodule.php?module=golinda&op=enter");
		} else {
			addnav(array("Visit Golinda (%s points)", $cost),"runmodule.php?module=golinda&op=enter");
		}
		break;
	}
	return $args;
}

function golinda_potionnav($cost)
{
	$from = httpget('from');
	addnav("Potions");
	addnav("`^Complete Healing`0", "runmodule.php?module=golinda&op=buy&pct=100");
	for($i=90;$i>0;$i-=10) {
		addnav(array("%s%% - %s gold", $i, round($cost*$i/100,0)), "runmodule.php?module=golinda&op=buy&pct=$i&from=$from");
	}
	addnav("Return");
}

function golinda_run(){
	global $session;
	$op = httpget("op");

	// Reimburse people points spent for old version of Golinda
	$config = unserialize($session['user']['donationconfig']);
	if (!is_array($config)) $config = array();
	if (isset($config['healer']) && $config['healer']) {
		$points = 150*round($config['healer']/10, 0);
		$points += 20*round($config['healer']%10, 0);
		if ($points > $session['user']['donationspent'])
			$points = $session['user']['donationspent'];
		$session['user']['donationspent'] -= $points;
		unset($config['healer']);
		if ($points) {
			output("`^Due to changes in the way in which Golinda is handled, you have been refunded %s donation points!`n`n", $points);
		}
	}
	$session['user']['donationconfig'] = serialize($config);

	page_header("Golinda's Infirmary");
	$cost = get_module_setting("points");
	$percent = get_module_setting("costpercent")/100;
	$loglev = log($session['user']['level']);
	$healcost = ($loglev * ($session['user']['maxhitpoints']-$session['user']['hitpoints']))+($loglev*10);
	$result=modulehook("healmultiply",array("alterpct"=>1.0));
	$healcost*=$result['alterpct'];
	$healcost = round($healcost * $percent, 0);
	$from = httpget('from');
	if ($op=="enter"){
		$pointsavailable = $session['user']['donation'] -
			$session['user']['donationspent'];
		output("`3You step through the door at the back of the Lodge leading to Golinda's infirmary.`n`n");
		output("As you enter, a young and very beautiful elven female looks up from the desk.  \"`^Good day %s`^! Golinda will be free to see you in a moment.  If you will just let me see your membership card so that we can make sure you are all paid up on your Lodge dues, we'll get you in to see her in a moment or three.`3\"`n`n", $session['user']['name']);
		output("`3With a small chuckle you hand over your J. C. Petersen Lodge membership card and she checks it quickly.`n`n");
		if ($pointsavailable < $cost && !get_module_pref("paidtoday")) {
			output("She smiles at you again. \"`^I'm sorry, but you don't seem to be fully paid up on your Lodge dues.  Perhaps you could come back later?`3\"  The elven female turns back to what she was doing, clearly dismissing you.`n`n");
		} else {
			output("\"`^Ah good, you are all current on your membership dues.  Miss Golinda will see you as soon as you are ready.  Remember that you will be charged a fee of %s Lodge points per day%s in addition to her cheaper healing rates.`3\"  The young elf giggles, \"`^I'm sure you'll be feeling MUCH better soon!`3\"", $cost, get_module_pref("paidtoday")?translate_inline(", which you've already paid for today,"):"");
			addnav("See Golinda", "runmodule.php?module=golinda&op=healer&from=$from");
		}
	} elseif ($op=="healer") {
		output("`3A very petite and beautiful brunette looks up as you enter.  \"`6Ahh.. You must be %s.`6  My assistant told me you were here.  Come in.. come in!`3\" she exclaims.`n`n", $session['user']['name']);
		if ($session['user']['hitpoints']<$session['user']['maxhitpoints']){
			output("`3\"`6Now.. Let's see here.  Hmmm. Hmmm. You're a bit banged up it seems.`3\"`n`n\"`5Uh, yeah.  I guess.  What will this cost me?`3\" you ask, looking sheepish. \"`5I don't normally get this hurt you know.`3\"`n`n\"`6I know.  I know.  None of you `^ever`6 does.  Anyhow, I can set you right as rain for `$`b%s`b`6 gold pieces.  I can also give you partial doses at a lower price if you cannot afford a full potion,`3\" says Golinda, smiling.", $healcost);
			output("`n`n`3\"`6Regardless of which potion you choose of course, there will be the fee of %s Lodge points.`3\"`n`n", $cost);
			golinda_potionnav($healcost);
		} elseif ($session['user']['hitpoints'] == $session['user']['maxhitpoints']) {
			 output("`3Golinda looks you over carefully.  \"`6Well, you do have that hangnail there, but other than that, you seem in perfect health! `^I`6 think you just came in here because you were lonely,`3\" she chuckles.`n`nRealizing that she is right, and that you are keeping her from other patients, you wander back out to the Lodge main room.");
		} else {
			output("`3Golinda looks you over carefully.  \"`6My, my! You don't even have a hangnail for me to fix!  You are a perfect speciman of %s!  Do come back if you get hurt, please,`3\" she says, turning back to her potion mixing.`n`n\"`6I will,`3\"you stammer, unaccountably embarrased as you head back out to the Lodge main room.", ($session['user']['sex'] ? translate_inline("womanhood") : translate_inline("manhood")));
		}
	} elseif ($op == "buy") {
		$pct = httpget("pct");
		$newcost = round($pct*$healcost/100, 0);
		if ($session['user']['gold'] >= $newcost) {
			$session['user']['gold']-= $newcost;
			debuglog("spent $newcost gold on healing");
			$diff = round(($session['user']['maxhitpoints']-$session['user']['hitpoints'])*$pct/100, 0);
			$session['user']['hitpoints']+=$diff;
			output("`3Expecting a foul concoction you begin to up-end the potion.  As it slides down your throat however, you taste cinnamon, honey, and a fruit flavor.  You feel warmth spread throughout your body as your muscles knit themselves back together.  Clear-headed and feeling much better, you hand Golinda the gold you owe and head back out to the Lodge main room.");
			output("`n`n`#You have been healed for %s points!", $diff);
			if (get_module_pref("paidtoday") == 0) {
				$session['user']['donationspent']+= $cost;
				set_module_pref("paidtoday", 1);
				output("`n`n`^Since this is your first visit to Golinda today, you have been charged `#%s`^ Lodge points.", $cost);
			}
		} else {
			output("`3\"`6Tsk, tsk!`3\" Golinda murmers.  \"`6Maybe you should go visit the Bank and return when you have `\$%s`6 gold?`3\" she asks.`n`nYou stand there feeling sheepish for having wasted her time.`n`n\"`6Or maybe a cheaper potion would suit you better?`3\" she suggests kindly.", $newcost);
			golinda_potionnav($healcost);
		}
	}
	if ($from == 'forest')
		addnav("F?Return to the Forest", "forest.php");
	else
		addnav("L?Return to the Lodge","lodge.php");
	page_footer();
}
?>

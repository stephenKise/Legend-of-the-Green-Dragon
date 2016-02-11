<?php
// translator ready
// mail ready
// addnews ready

// Heidi, the well-wisher module. Gifting to players
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 29 Nov 2004 */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function heidi_getmoduleinfo(){
	$info = array(
		"name"=>"Well-wisher module",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Heidi's Hut Settings,title",
			"changeallowed"=>"How many PvP may players exchange per day?,range,0,".getsetting("pvpday", 3) . ",1|1",
			"allowgift"=>"Allow gifting to other players?,bool|1",
			"allowdp"=>"Allow dragon point exchange?,bool|0",
			"findperc"=>"Percent of time a player will find a gift by their pillow?,range,0,100,5|25",
			"turnmult"=>"Multiplier for the extra turns?,floatrange,0,50,2.5|10",
			"turnadd"=>"Addition for the extra turns?,range,0,10,1|3",
			"heidiloc"=>"Where does Heidi appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
			"Heidi's Hut User Preferences,title",
			"pvpchange"=>"How many changes have been done today?,int|0",
			"newdayturns"=>"How many turns did the player have at new day?,int|0",
			"addgold"=>"Donation amount set for this player for today,int|0",
			"pendingdp"=>"Does the user have a dk point reallocation pending?,bool|0",
			"gemspaid"=>"How many gems has the player paid to Heidi for dk point reallocation?,int|0",
		)
	);
	return $info;
}

function heidi_install(){
	module_addhook("changesetting");
	module_addhook("village");
	module_addhook("newday");
	return true;
}

function heidi_uninstall(){
	return true;
}

function heidi_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("heidiloc")) {
				set_module_setting("heidiloc", $args['new']);
			}
		}
		break;
	case "village":
		$allowgift=get_module_setting("allowgift");
		$allowdp =get_module_setting("allowdp");
		$changeallowed=get_module_setting("changeallowed");
		// disable for farmies lower than level 10
		if (($session['user']['location']==get_module_setting("heidiloc")) &&
				($allowgift || $allowdp || $changeallowed > 0) &&
				(($session['user']['dragonkills'] > 0) ||
				 $session['user']['level'] >= 10)) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("H?Heidi's Place","runmodule.php?module=heidi");
		}
		break;
	case "newday":
		if ($args['resurrection'] != 'true')
			set_module_pref("pvpchange",0);
		set_module_pref("pendingdp",0);
		$turns = getsetting("turns",10) + $session['user']['spirits'];
		reset($session['user']['dragonpoints']);
		while(list($key,$val)=each($session['user']['dragonpoints'])){
			if ($val=="ff") $turns++;
		}
		set_module_pref("newdayturns",$turns);
		$echance=e_rand(0, 100);
		$mult=e_rand(200,400);
		$addgold=round($mult*($session['user']['level']/max(10,$session['user']['dragonkills'])));
		if ($session['user']['dragonkills']<6) $addgold*1.5;
		if ($echance >= get_module_setting("findperc"))
			$addgold=0;
		set_module_pref("addgold",$addgold);
		if ($addgold>1){
			// they are a recipient
			$session['user']['gold']+=$addgold;
			output("`n`5Beside your pillow is a small leather bag containing %s gold, and a note: `^Blessings to ye, child, for someone cared enough to send ye a gift.", $addgold);
			output("`5Wondering who it is from, you add it to your purse.`n");
			debuglog("gained $addgold gold from an anonymous gift.");
		}
		break;
	}
	return $args;
}

function heidi_run(){
	global $session;

	$op = httpget("op");
	$addgold=get_module_pref("addgold");
	$addgold*=round(min(1,$session['user']['dragonkills'])*max(1,$session['user']['dragonkills']*0.5/$session['user']['maxhitpoints']));
	$addgold-=max(1000,$session['user']['maxhitpoints']);
	if ($addgold<=0) $addgold=10*$session['user']['level'];
	$pvpchange=get_module_pref("pvpchange");
	$changeallowed=get_module_setting("changeallowed");
	$allowgift=get_module_setting("allowgift");
	$allowdp=get_module_setting("allowdp");
	$turnadd=get_module_setting("turnadd");
	$turnmult=get_module_setting("turnmult");
	$newdayturns=get_module_pref("newdayturns");
	$turngain=ceil($newdayturns*$turnmult/100)+$turnadd;
	$gemspaid = get_module_pref("gemspaid");
	$dk = $session['user']['dragonkills'];

	page_header("Heidi's Place");

	villagenav();
	output("`&`c`bHeidi, the Well-wisher`b`c`n");
	if ($changeallowed) {
		addnav("Burn Blue Candle - lose 1 PvP",
				"runmodule.php?module=heidi&op=blue");
	}
	if ($allowdp && $session['user']['dragonkills'] > 10) {
		addnav("O?Ask about the Orange candles",
				"runmodule.php?module=heidi&op=orange");
	}
	if ($allowgift) {
		addnav(array("Make a Donation - %s gold",$addgold),
				"runmodule.php?module=heidi&op=give");
	}

	modulehook("heidi-start",
			array("changeallowed"=>$changeallowed,
				"allowdp"=>($allowdp && $session['user']['dragonkills']>10),
				"allowgift"=>$allowgift,
				"giftgold"=>$addgold));

	if ($op==""){
		output("`7You step into Heidi's small hut, gazing around at the multitude of colored candles that flicker from the shelves on every wall.`n`n");
		output("`7After a moment you are greeted by a pleasant-looking felyne with a smile on her face.`n`n");
		output("\"`&It is so lovely to see you, %s!",$session['user']['name']);
		output("`&Welcome, then, and reflect on the beauty of the well.`7\"`n`n");
		output("`7A small fountain trickles into what looks more like a pool than a well.");
		output("`7As Heidi whistles softly, crystals in the bottom of the pool begin to glow, eventually forming the numbers: %s.`n`n",$addgold);
		output("`7\"`&Perhaps ye have come to give! Or to burn a candle for a spell?`7\"");
	}elseif ($op=="give" && $session['user']['gold']<$addgold && $allowgift){
		// you don't have enough money.
		output("`7Heidi eyes you with concern.`n`n");
		output("\"`&Child, ye be trying to give, when ye have not the affluence to give.`7`n`n");
		output("`&Take comfort, for the mother sees inside thy heart today.`7\"`n`n");
		output("You're not so sure you understand who this mother is, but you realize that you haven't enough gold to donate what the well requests, and so you leave quietly.`n`n");
	} elseif ($op=="give" && $allowgift) {
		output("`7You lean forward, and place %s gold into the well.`n`n",$addgold);
		output("Heidi concentrates on a small candle for several moments, whispering words you cannot understand.`n`n");
		output("\"`&Somewhere, come the morning fair, someone less fortunate shall wake up with a gift of gold.`7`n`n");
		output("`&The pleasure in giving, be within you today!`7\" she exclaims.`n`n");
		debuglog("gave $addgold gold as an anonymous gift to someone less fortunate.");
		$session['user']['gold']-=$addgold;
		apply_buff('heidi', array(
			"name"=>"`QUnselfishness`0",
			"rounds"=>15,
			"defmod"=>1.05,
			"survivenewday"=>1,
			"roundmsg"=>"`QGiving to others makes you feel empowered.`0",
			"schema"=>"module-heidi",
		));
	} elseif ($op=="orange") {
		output("`7Heidi smiles and motions to the small box of orange candles on a shelf nearby.`n`n");
		output("\"`&Orange is the color of change!");
		output("What's done in life is done, but we can mend bridges if we wish to make the change in ourselves.`7\"`n`n");
		if (!get_module_pref("pendingdp")) {
			output("`7For a cost of %s gems, you will be able to rechoose your dragon point allocation after new day.`n`n",$dk);
		}

		$gemsremain = $dk - $gemspaid;
		if (get_module_pref("pendingdp")) {
			output("`7Heidi looks at you deeply for a moment and then smiles tenderly, \"`&Child, child, you must give time for the past changes to take effect before trying again.`7\"`n`n");
		} elseif ($session['user']['gems']) {
			addnav("Payment");
			// User has enough to pay in full
			if ($session['user']['gems'] >= $gemsremain) {
				// You can always pay what you still owe if you have it.
				addnav(array("Pay in full (%s %s)", $gemsremain,
							translate_inline($gemsremain == 1?"gem":"gems")),
						"runmodule.php?module=heidi&op=orangepay&amt=$gemsremain");
			}
			// Don't make user pay in full if he doesn't want to
			if ($session['user']['gems'] < $gemsremain) {
				addnav(array("Pay an installment (%s %s)",
							$session['user']['gems'],
							translate_inline($session['user']['gems']==1 ?
								"gem":"gems")),
						"runmodule.php?module=heidi&op=orangepay&amt=".$session['user']['gems']);
			}
			if ($gemsremain > 25 && $session['user']['gems'] >= 25) {
				addnav(array("Pay an installment (%s %s)", 25,
							translate_inline("gems")),
						"runmodule.php?module=heidi&op=orangepay&amt=25");
			}
			// Give some text about the installment
			if ($gemspaid == 0 && ($dk > 25 || $session['user']['gems'] < $gemsremain)) {
				output("`7As your total price is %s gems you might not wish to pay in full immediately, and instead pay in installments.`n`n",$dk);
				output("`\$You will not be able to rechoose your dragon point allocation until the whole amount has been paid, and you cannot regain any gems you have already given once you start to make the installments.`n`n");
				output("`\$Once you pay the full cost, you will lose any hitpoints, attack or defense that you have purchased with dragonpoints until the next new day when you will be able to once again choose their allocation.`n`n");
			} elseif ($gemspaid) {
				output("`^You have paid %s gems so far and have %s remaining in order to rechoose your dragon points at new day.`n`n",$gemspaid,$gemsremain);
				output("`\$You will not be able to rechoose your dragon point allocation until the whole amount has been paid, and you cannot regain any gems you have already given once you start to make the installments.`n`n");
				output("`\$Once you pay the full cost, you will lose any hitpoints, attack or defense that you have purchased with dragonpoints until the next new day when you will be able to once again choose their allocation.`n`n");
				output("`\$Additionally, any buffs you have from visiting Tynan will be immediately reset.  Such is the cost of change.`n`n");
			}
		} else {
			// User doesn't have any gems on him.
			output("`7You don't have enough gems to pay right now.`n`n");
		}
	} elseif ($op=="orangepay") {
		$amt = httpget("amt");
		$gemspaid += $amt;
		$gemsremain = $dk - $gemspaid;
		set_module_pref("gemspaid", $gemspaid);
		if ($gemsremain) {
			debuglog("Spent $amt gems on dp reallocation installment with Heidi. $gemspaid spent in total.  $gemsremain left to pay.");
		} else {
			debuglog("Spent $amt gems to pay remaining amount on dp reallocation with Heidi.  $dk spent total.");
		}
		$session['user']['gems'] -= $amt;
		if ($gemsremain) {
			output("`7Heidi thanks you for the gems and reminds you that you have now paid %s of the %s total gems you owe.`n`n",$gemspaid, $dk);
		} else {
			output("`7Heidi thanks you for the payment and smiles.");
			output("She takes a small, orange candle, lights it and places it in a small silver holder, before whispering words that seem foreign to your ears.");
			output("She finally opens her eyes and reminds you that you will be able to rechoose your dragon point allocation at new day.`n`n");
			set_module_pref("gemspaid",0);
			$hpcount = 0;
			$atcount = 0;
			$defcount = 0;
			reset($session['user']['dragonpoints']);
			while(list($key,$val)=each($session['user']['dragonpoints'])){
				if ($val == "hp") $hpcount += 5;
				if ($val == "at") $atcount ++;
				if ($val == "de") $defcount ++;
			}
			restore_buff_fields();
			$session['user']['maxhitpoints'] -= $hpcount;
			$session['user']['hitpoints'] -= $hpcount;
			$session['user']['attack'] -= $atcount;
			$session['user']['defense'] -= $defcount;
			set_module_pref("attack", 0, "tynan");
			set_module_pref("defense", 0, "tynan");
			set_module_pref("hitpoints", 0, "tynan");
			strip_buff("tynanSTAT");
			if ($session['user']['hitpoints'] <= 1)
				$session['user']['hitpoints'] = 1;
			// call the reset hook before we wipe the array just in case
			// something cares!
			modulehook("dkpointspentreset");
			$session['user']['dragonpoints'] = array();
			set_module_pref("pendingdp", 1);
			calculate_buff_fields();
		}
	} elseif ($session['user']['playerfights']==0 ||
			$pvpchange >= $changeallowed){
		// you have no PvP left today or have already burned the blue
		// enough today
		output("`7Heidi eyes you with a smile.`n`n");
		if ($session['user']['playerfights']==0) {
			output("\"`&'Tis all very well to want peace, when one has none extra to give!`7\"`n`n");
		} else {
			// No more burning allowed today.
			output("\"`&Your desire for peace is noble, but is misplaced at this time.`7\"`n`n");
		}
		output("\"`&Perhaps tomorrow ye will come to see me, before ye slay your enemies in fury?`7\"`n`n");
	} else {
		output("`7Heidi takes a small, sky-blue candle, lights it and places it in a small silver holder.");
		output("She regards you with a smile.`n`n");
		output("\"`&Your gesture of kindness to your fellow beings shall reward you.");
		output("Go in peace, warrior.`7\"`n`n");
		output("`6You `@gain`6 %s turns!`n`n",$turngain);
		$session['user']['turns']+=$turngain;
		$session['user']['playerfights']--;
		$pvpchange++;
		set_module_pref("pvpchange",$pvpchange);
		debuglog("exchanged one PvP fight for $turngain forest fights.");
		$newdayturns*=0.75; // second and subsequent burns have lower benefit
		set_module_pref("newdayturns",$newdayturns);
	}

	modulehook("heidi-end",
			array("changeallowed"=>$changeallowed,
				"allowdp"=>($allowdp && $session['user']['dragonkills']>10),
				"allowgift"=>$allowgift,
				"giftgold"=>$addgold));
	page_footer();
}

?>

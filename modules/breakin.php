<?php
// translator ready
// addnews ready
// mail ready

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* includes creative and technical input by JT Traub */
/* 24 Aug 2004 */
/* breakin module */

// 3rd Sept ver 1.1 Also interfaces with Matthias, the Astute

require_once("lib/http.php");
require_once("lib/villagenav.php");

function breakin_getmoduleinfo(){
	$info = array(
		"name"=>"Breakin Module",
		"version"=>"1.1",
		"author"=>"Shannon Brown",
		"category"=>"Inn",
		"download"=>"core_module",
		"settings"=>array(
			"danger"=>"Chance of being caught?,range,5,100,5|25",
			"thisID"=>"Who is the ID caught breaking into the Inn?,int|0",
			"wipepvp"=>"Does getting caught wipe PvP fights?,bool|1",
			"guilt"=>"Will the player feel guitly and avoid the inn?,bool|1",
			"stocks"=>"Will the person caught be thrown in the stocks?,bool|1",
			"losecharm"=>"Do you lose charm for being caught?,bool|1",
			"hploss"=>"How much percent of your HP do you lose when caught?,range,0,100,5|50",
			"robloss"=>"How much of your gems and gold will the gaurds take?,range,0,100,5|0"
		),
		"prefs"=>array(
			"breaktoday"=>"Have they snuck upstairs today,bool|0",
			"guilt"=>"How many more days will they feel guilty,int|0",
			"ring"=>"Has the player found the diamond ring,bool|0",
		)
	);
	return $info;
}

function breakin_install(){
	module_addhook("newday");
	module_addhook("stables-nav");
	module_addhook("stables-desc");
	module_addhook("village");
	module_addhook("dragonkill");
	module_addhook("inn");
	return true;
}

function breakin_uninstall(){
	return true;
}

function breakin_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		$guilt = get_module_pref("guilt");
		if ($guilt > 1) {
			if (get_module_pref("guiltydk")) {
				output("`n`3You start to feel bad about something relating to the inn that you cannot quite remember.");
				output("You are quite sure that, whatever it was,  you don't want to face Cedrik quite yet.");
			} else {
				output("`n`3You start to feel bad about breaking into the inn recently, and you aren't sure you can face Cedrik quite yet.");
			}
			if ($guilt == 2) {
				output("Perhaps tomorrow....`n");
			} elseif ($guilt == 3) {
				output("Perhaps the day after tomorrow....`n");
			}
			$guilt--;
			set_module_pref("guilt",$guilt);
		} elseif ($guilt ==1) {
			set_module_pref("guilt",0);
			set_module_pref("guiltydk", 0);
		}
		set_module_pref("breaktoday",0);
		break;
	case "dragonkill":
		if (get_module_pref("guilt") && get_module_setting("guilt")) {
			set_module_pref("guiltydk", 1);
		}
		break;
	case "inn":
		if (get_module_pref("guilt") && get_module_setting("guilt")) {
			if (get_module_pref("guiltydk")) {
				output("`n`3For some reason you just cannot explain nor remember you are absolutely unable to bear remaining in the inn for another moment!`0`n");
			} else {
				output("`n`3You remember forcing that door, and the damage you caused to the lock and door frame, and you just can't bear to be in here a minute longer!`0`n");
			}
			blockmodule("dag");
			blockmodule("lottery");
			blockmodule("lovers");
			blockmodule("inncoupons");
			blockmodule("sethsong");
			blocknav("inn.php?op=bartender");
			blocknav("inn.php?op=converse");
			blocknav("inn.php?op=seth");
			blocknav("inn.php?op=seth&subop=hear");
			blocknav("inn.php?op=converse");
			blocknav("inn.php?op=room");
		}
		break;
	case "stables-desc":
		if (getsetting("villagename", LOCATION_FIELDS) == $session['user']['location']) {
			output("`n`n`3As you look around the stables, you spy a door on the building next door, mostly hidden by dense ivy.`0`n");
			$nouse = 0;
			if (get_module_setting("guilt") && get_module_pref("guilt"))
				$nouse = 1;
			if (get_module_pref("breaktoday")) $nouse = 1;
			if (!$session['user']['playerfights']) $nouse = 1;
			if ($nouse) {
				output("You briefly consider investigating it, but decide not to press your luck at this moment.`n");
			}
		}
		break;
	case "stables-nav":
		// only in Merick's stables, not in Bertold's and only if they can
		// use it
		if (getsetting("villagename", LOCATION_FIELDS) == $session['user']['location']) {
			$nouse = 0;
			if (get_module_setting("guilt") && get_module_pref("guilt"))
				$nouse = 1;
			if (get_module_pref("breaktoday")) $nouse = 1;
			if (!$session['user']['playerfights']) $nouse = 1;
			if (!$nouse)
				addnav("Examine Door","runmodule.php?module=breakin");
		}
		break;
	case "village":
		if (!get_module_setting("stocks") || !is_module_active("stocks")) {
			set_module_setting("thisID", 0);
			break;
		}

		$thisID= get_module_setting("thisID");
		if (!$thisID) break;

		$lastStock = get_module_setting("victim", "stocks");
		if ($thisID != $lastStock) {
			set_module_setting("thisID", 0);
			break;
		}

		// No output if we're not in the capital
		if ($session['user']['location'] != getsetting("villagename", LOCATION_FIELDS)) break;

		if ($thisID == $session['user']['acctid']) {
			output("`n`7You are serving time in the stocks for trying to break into the Inn!`0`n");
		} else {
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$thisID'";
			$result = db_query_cached($sql, "stocks");
			$row = db_fetch_assoc($result);
			output("`n`&%s`7 was put into the stocks for trying to break into the Inn!`0`n",$row['name']);
		}
		break;
	}
	return $args;
}

function breakin_run(){
	global $session, $pvptimeout, $pvptime;
	$op = httpget("op");
	$danger = get_module_setting("danger");

	require_once("lib/partner.php");
	$partner = get_partner();

	// Need to include this in here so we pick up the pvptimeout/etc in
	// the right scope.
	require_once("lib/pvplist.php");

	// this is a variable in case an admin changes the name of the inn
	$iname = getsetting("innname", LOCATION_INN);
	page_header($iname);
	output("`&`c`bThe Ivy-Covered Door`b`c");
	if ($op==""){
		addnav("D?Force the Door","runmodule.php?module=breakin&op=force");
		addnav("F?Forget it","runmodule.php?module=breakin&op=leave");
		output("`7As you approach the door, you hear the sounds of drunken revelry.");
		output("You realize that this door must be a back entrance to the Inn.`n`n");
		output("`7As you pull some of the vines away, you discover that the door is flimsy and moves as you push at it.");
		output("The realization dawns that you could sneak upstairs for free and slay another player as they sleep!");
		output("`7Merick tends to his animals and pays you no attention.");
		output("Will you break in?`n`n");
	} elseif ($op=="leave") {
		addnav("Return from whence you came","stables.php");
		output("`7You really don't want to break the door down, so you quietly walk back over to the stables.`n`n");
	} elseif ($op=="go") {
		addnav("Return from whence you came","stables.php");
		output("`7You really don't think it is worth the risk, so you quietly go back outside and walk over to the stables.`n`n");
	} elseif ($op=="ledger-safe") {
		output("You begin to scan the names in the ledger, trying to decide who to attack.`n`n");
		pvplist($iname,"pvp.php", "?act=attack&inn=1");
		addnav("List Warriors","runmodule.php?module=breakin&op=ledger-safe");
		addnav("Forget it","runmodule.php?module=breakin&op=go");
	} elseif ($op=="ledger") {
		$danger=get_module_setting("danger");
		$bchance = (e_rand(0,100));
		output("You begin to scan the names in the ledger, trying to decide who to attack.`n`n");
		if ($bchance>$danger) {
			// I hate doing a redirect here, but if we don't, the ledger list
			// won't be 'safe' if you view a bio and then return.
			redirect("runmodule.php?module=breakin&op=ledger-safe");
		} else {
			output("Before you can do anything, a powerful blow to the head knocks you to the ground.");
			output("Cedrik stands over you, a wine bottle in one hand, scowling angrily.`n`n");
			if (get_module_setting("stocks")) {
				output("He yells for a guard, and before you can collect your senses, the two of them have hoisted you out the front door of the Inn, and into the stocks.`n");
				set_module_setting("victim",$session['user']['acctid'], "stocks");
				set_module_setting("thisID",$session['user']['acctid']);
				invalidatedatacache("stocks");
			} else {
				output("He yells for a guard, and before you can collect your senses, the two of them have 'escorted' you out the front door of the Inn.`n");
			}
			if (get_module_setting("robloss")) {
				output("As you struggle to retain conciousness, you feel the guard relieving you of some of your possessions.`n");
				$losspercent = get_module_setting("robloss")/100;
				$gems = round($session['user']['gems'] * $losspercent, 0);
				$gold = round($session['user']['gold'] * $losspercent, 0);
				if ($gems) {
					if ($gems == 1) {
						output("`&The guard `\$takes `%%s`& gem.`n", $gems);
					} else {
						output("`&The guard `\$takes `%%s`& gems.`n", $gems);
					}
					$session['user']['gems'] -= $gems;
					debuglog("lost $gems gems to the gaurd when caught breaking in to the Inn");
				}
				if ($gold) {
					output("`&The guard `\$takes `^%s`& gold.`n", $gold);
					$session['user']['gold'] -= $gold;
					debuglog("lost $gold gold to the gaurd when caught breaking in to the Inn");
				}
			}

			if (get_module_setting("losecharm")) {
				output("`7%s`7 watches the whole thing with a frown.`n`n",$partner);
				output("`^You `\$lose`^ some charm!`n");
				if ($session['user']['charm']>=3) {
					$session['user']['charm']-=3;
				} else {
					$session['user']['charm']=0;
				}
			}
			if (get_module_setting("hploss")) {
				output("`^Your head throbs, and you feel weak.`n");
				$hp = round($session['user']['hitpoints'] * get_module_setting("hploss")/100, 0);
				$session['user']['hitpoints'] -= $hp;
				if ($session['user']['hitpoints'] < 1)
					$session['user']['hitpoints'] = 1;
			}
			output("Moments later, you pass out from the pain in your head.`n`n");
			if (get_module_setting("wipepvp")) {
				$session['user']['playerfights'] = 0;
				output("`n`nYou don't feel like you can attack anyone today.`n`n");
				debuglog("lost all their player fights after being caught breaking into the Inn");
			}
			addnews("`&%s `7tried to break into the Inn, and was caught by Cedrik.", $session['user']['name']);
			addnav("Wake Up","village.php");
			if (get_module_setting("guilt")) set_module_pref("guilt",2);
		}
	} else {
		set_module_pref("breaktoday",1);
		if (get_module_setting("guilt")) set_module_pref("guilt",2);
		output("`7You lean on the door heavily with one shoulder, and it gives way with a crack as the wood splinters around the lock.`n`n");
		$ringchance=e_rand(1,20);
		output("You find yourself in a small office.");
		if (get_module_pref("ring")==0 && $ringchance==1) {
			output("On the desk in front of you, there is a set of labeled keys, a diamond ring, and a small ledger listing the guests in each room. ");
			set_module_pref("ring",1);
			output("You take the ring and slip it quickly into your pocket.`n`n");
			// need to check if module exists on this server
			if (is_module_active("matthias")){
				$astute=get_module_pref("astuteness","matthias");
				$astute++;
				set_module_pref("astuteness",$astute,"matthias");
			}
		} else {
			output("On the desk in front of you, there is a set of labeled keys and a small ledger listing the guests in each room. ");
		}
		output("Beyond an open doorway to one side, you hear the inn patrons talking, and you realize how dangerous this idea is!`n`n");

		addnav("Forget it","runmodule.php?module=breakin&op=go");
//		if (is_module_active("pvpimmunity") && !get_module_pref("check_willing","pvpimmunity")){
//			output("You are repelled by the idea of sneaking into somebody else's room, so you find nothing else of interest here.`n`n");
//		} else {
			addnav("Look at the Ledger","runmodule.php?module=breakin&op=ledger");
//		}
		// Help the superusers debug
		if ($session['user']['superuser'] & SU_EDIT_USERS) {
			addnav("Safe look", "runmodule.php?module=breakin&op=ledger-safe");
		}
	}
	page_footer();
}
?>

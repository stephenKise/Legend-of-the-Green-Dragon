<?php
// addnews ready
// mail ready
// translator ready

/* ver 1.3 by Booger - bigredx (a) sci -dot- fi */
/* 23 Aug 2004 */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function spa_getmoduleinfo(){
	$info = array(
		"name"=>"Trollish Spa",
		"version"=>"1.3",
		"author"=>"Booger",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"visitsallowed"=>"Times players are allowed visit per day,int|1",
			"cost"=>"How much gold does a visit cost per level,int|30",
		),
		"prefs"=>array(
			"visitstoday"=>"Number of visits today,int|0",
		)
	);
	return $info;
}


function spa_install(){
	$vname = get_module_setting("villagename", "racetroll");
	if ($vname === NULL) {
		output("Please install a trollish town before installing this module!");
		return false;
	}
	set_module_setting("town", $vname);
	module_addhook("changesetting");
	module_addhook("village");
	module_addhook("newday");
	return true;
}

function spa_uninstall(){
	return true;
}

function spa_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "village":
		if($session['user']['location'] == get_module_setting("town")){
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("Booger's Spa","runmodule.php?module=spa&op=relax");
		}
		break;
	case "newday":
		set_module_pref("visitstoday",0);
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("town")){
				set_module_setting("town", $args['new']);
			}
		}
		break;
	}
	return $args;
}

function spa_run(){
	global $session;

	$from = "village.php?";
	$visitstoday = get_module_pref("visitstoday");
	$visitsallowed = get_module_setting("visitsallowed");
	$cost = get_module_setting("cost");
	$visitcost = $cost*$session['user']['level'];
	$op = httpget("op");

	page_header("Booger's Trollish Spa");

	output("`&`c`bBooger's Trollish Spa`b`c");

	if ($op=="" || $op=="relax"){
		villagenav();
		addnav(array("T?Take a Mudbath (`^%s gold`0)",$visitcost),"runmodule.php?module=spa&op=mudbath");
		addnav(array("G?Get a Massage (`^%s gold`0)",$visitcost),"runmodule.php?module=spa&op=massage");
		$vname = get_module_setting("town");
		output("`7You enter the magnificent establishment and look around in awe.");
		output("You really didn't expect to find this kind of luxury in a trollish town, but spas are a big business and %s IS famous for the mud...`n`n", $vname);
		output("`7There is a large green-brown mound vaguely resembling a man behind the counter.");
		output("Hesitantly, you approach the huge troll, and when you get to the counter you realise you're standing in front of `2B`@`boo`b`2ger`7, the owner of the spa.`n`n");
		output("`7\"`2hhGRHhwWwhhoOODDdyyywNNNTtt?`7\"`n`n");
		output("`7You blink a few times before you suddenly realise you were asked a question.");
		output("`2B`@`boo`b`2ger`7 doesn't seem very patient, so you'd better make your decision quickly, whatever it is you are supposed to decide...`n`n");
	} else {
		if ($visitstoday>=$visitsallowed) {
			villagenav();
			output("`7You are about to tell `2B`@`boo`b`2ger`7 what you want, when his eyes suddenly turn blood red!");
			output("He begins to roar.`n`n\"`\$hHYYYyGRroOUUuHH!`7\"`n`n");
			output("You guess he's had enough of you today... and get out of there quickly!!!!`n`n");
		} elseif ($session['user']['turns']==0){
			villagenav();
			output("`7You don't have time for this today.");
			output("You decide to go back to town and find a place to sleep.");
		} elseif ($session['user']['gold']<$visitcost){
			villagenav();
			output("`7You go through your pockets, searching for money, but you don't have enough.");
			output("After a moment of intense searching, `2B`@`boo`b`2ger`7 starts to growl in a deep voice, and you decide to leave before he gets really angry.`n`n");
		} else {
			villagenav();
			$visits=get_module_pref("visitstoday")+1;
			set_module_pref("visitstoday",$visits);
			$session['user']['gold']-=$visitcost;
			debuglog("spent $visitcost gold at the spa");
			$timespent=(e_rand(1,10));
			$buffbonus=1.2+($timespent/50);
			if ($op=="mudbath"){
				output("`7You give `2B`@`boo`b`2ger`^ %s gold`7.", $visitcost);
				output("He nods, and another troll appears from a side door to escort you to the mudbaths.`n`n");
				apply_buff('spa',
					array(
						"name"=>"`qMudbath",
						"rounds"=>15,
						"wearoff"=>"You feel a bit weaker.",
						"atkmod"=>$buffbonus,
						"schema"=>"module-spa",
					)
				);
				if ($timespent==10 && $session['user']['turns']>2){
					output("`7You fall asleep in the mud and wake up later than you intended.");
					output("You feel strong and the mud really did your skin good too!`n`n");
					$session['user']['turns']-=3;
					output("`\$You lose three forest fights!`n");
					$session['user']['charm']++;
					output("`^You gain a charm point!");
				} elseif ($timespent>6 && $session['user']['turns']>1){
					output("`7You really enjoy the mudbath.");
					output("After some time you finally leave with new strength.`n`n");
					$session['user']['turns']-=2;
					output("`\$You lose two forest fights!");
				} else {
					output("`7You sure got dirty.");
					output("After cleaning yourself up you feel pretty good though.`n`n");
					$session['user']['turns']-=1;
					output("`\$You lose a forest fight.");
				}
			} else {
				output("`7You give `2B`@`boo`b`2ger`^ %s gold`7.", $visitcost);
				output("He nods, and another troll appears from a side door and leads you over to a massage table.`n`n");
				apply_buff('spa',
					array(
						"name"=>"`QMassage",
						"rounds"=>15,
						"wearoff"=>"You feel tense again.",
						"defmod"=>$buffbonus,
						"schema"=>"module-spa",
					)
				);
				if ($timespent==10 && $session['user']['turns']>2){
					output("`7The massage feels so good that you lose track of time.");
					output("Your muscles feel really flexible though and you might have even lost a wrinkle or two!`n`n");
					$session['user']['turns']-=3;
					output("`\$You lose three forest fights!`n");
					$session['user']['charm']++;
					output("`^You gain a charm point!");
				} elseif ($timespent>6 && $session['user']['turns']>1){
					output("`7That massage really hit the spot.");
					output("Your muscles feel flexible as you head back to town.`n`n");
					$session['user']['turns']-=2;
					output("`\$You lose two forest fights!");
				} else {
					output("`7You don't usually pay for a beating like this but that masseur sure knew what he was doing.`n`n");
					$session['user']['turns']-=1;
					output("`\$You lose a forest fight.");
				}
			}
		}
	}
	page_footer();
}

?>

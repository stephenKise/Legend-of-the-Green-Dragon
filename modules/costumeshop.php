<?php
// addnews ready
// mail ready
// translator ready
/* Haunter's Lodge - Costume Shop */
/* ver 1.3 by Booger - bigredx (a) sci -dot- fi */
/* 6 Dec 2004 */
/* ver 1.1 - Added custom weapon and armour for the costumes */
/* ver 1.2 - Cleaned up the code, prevented too long costume names and added a special costume for Hunter's Lodge members*/
/* ver 1.3 - Added optional Christmas outputs */
/* ver 1.4 - Made costuming a user from the User Editor less of a manual task (added by Catscradler) */

require_once("lib/http.php");
require_once("lib/villagenav.php");
require_once("lib/names.php");

function costumeshop_getmoduleinfo(){
	$info = array(
		"name"=>"Costume Shop",
		"version"=>"1.4",
		"author"=>"Booger",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"General,title",
			"Close the shop here instead of disabling it to prevent players from getting stuck with their costumes.,note",
			"open"=>"Is the shop open,bool|0",
			"shoploc"=>"Where does the costume shop appear,location|".getsetting("villagename", LOCATION_FIELDS),
			"rentaltime"=>"How many game days can the player keep the costume,int|2",
			"keepdk"=>"Will the player keep the costume after a dragon kill,bool|0",
			"cost"=>"How much gold do you have to pay per level to rent a costume,int|10",
			"christmastxt"=>"Christmas decorations for the shop,bool|0",
			"Costumes,title",
			"You can set one or more costumes empty to have less costumes available.,note",
			"If all costumes are set empty there will be a `\$C`&l`@o`&w`\$n`0 costume available.,note",
			"You can also leave the weapon and armor fields empty if you don't want custom equipment for the costumes.,note",
			"-- Costume 1 --,note",
			"costume1"=>"First costume (max 25 chars)|`7Ghost`&",
			"weapon1"=>"Custom weapon for the fist costume|Ball and Chain",
			"armor1"=>"Custom armor for the fist costume|Sheet",
			"-- Costume 2 --,note",
			"costume2"=>"Second costume (max 25 chars)|`QPirate`&",
			"weapon2"=>"Custom weapon for the second costume|Pirate's Cutlass",
			"armor2"=>"Custom armor for the second costume|Eyepatch",
			"-- Costume 3 --,note",
			"costume3"=>"Third costume (max 25 chars)|`&Skeleton`&",
			"weapon3"=>"Custom weapon for the third costume|Skeletal Sword",
			"armor3"=>"Custom armor for the third costume|Skeletal Helmet",
			"-- Costume 4 --,note",
			"costume4"=>"Fourth costume (max 25 chars)|`)Vampire`&",
			"weapon4"=>"Custom weapon for the fourth costume|Fangs",
			"armor4"=>"Custom armor for the fourth costume|Cape",
			"-- Costume 5 --,note",
			"costume5"=>"Fifth costume (max 25 chars)|`2Zombie`&",
			"weapon5"=>"Custom weapon for the fifth costume|Filthy Hands",
			"armor5"=>"Custom armor for the fifth costume|Ragged Clothes",
            "-- Special costume for donators --,note",
			"lodgecostume"=>"Special costume available only to donators|",
			"lodgeweapon"=>"Custom weapon for the special costume|",
			"lodgearmor"=>"Custom armor for the special costume|",
		),
		"prefs"=>array(
			"To remove the player's costume set days left to 0.,note",
			"rentleft"=>"How many days left until returning the costume,int|0",
			"newtitle"=>"Which costume player has rented|",
			"weapon"=>"Weapon for the costume|",
			"armor"=>"Armor for the costume|",
			"oldtitle"=>"Original custom title before renting a costume,viewonly",
			"oldweapon"=>"Current actual weapon,viewonly",
			"oldarmor"=>"Current actual armor,viewonly",
		)
	);
	return $info;
}

function costumeshop_install(){
	module_addhook("village");
	module_addhook("village-desc");
	module_addhook("footer-weapons");
	module_addhook("footer-armor");
	module_addhook("newday");
	module_addhook("changesetting");
	module_addhook("dragonkilltext");
	module_addhook("validateprefs");
	return true;
}

function costumeshop_uninstall(){
	return true;
}

function costumeshop_changetitle($newtitle){
	global $session;
	if ($newtitle){
		set_module_pref("oldtitle",$session['user']['ctitle']);
		set_module_pref("newtitle",$newtitle);
	} else {
		$newtitle = get_module_pref("oldtitle");
		set_module_pref("oldtitle","");
		set_module_pref("newtitle","");
	}
	$newname = change_player_ctitle($newtitle);
	$session['user']['ctitle'] = $newtitle;
	$session['user']['name'] = $newname;
	return $newname;
}

function costumeshop_changeweapon($newweapon){
	global $session;
	if ($newweapon){
		$reset = false;
	} else {
		$reset = true;
		$newweapon = get_module_pref("oldweapon");
		if (!$newweapon) return false;
	}
	$oldweapon = $session['user']['weapon'];
	$upgraded = strpos($oldweapon," +1")!==false ? true : false;
	$downgraded = strpos($oldweapon," -1")!==false ? true : false;
	$changed = strpos($oldweapon, $newweapon)!==false ? false : true;
	if (!$changed && !$reset) return false;
	if ($upgraded && $changed){
		$session['user']['weapon'] = $newweapon." +1";
		$oldweapon = str_replace(" +1","",$oldweapon);
	} elseif ($downgraded && $changed){
		$session['user']['weapon'] = $newweapon." -1";
		$oldweapon = str_replace(" -1","",$oldweapon);
	} elseif ($changed){
		$session['user']['weapon'] = $newweapon;
	}
	if ($reset){
		set_module_pref("oldweapon","");
		set_module_pref("weapon","");
	} else {
		set_module_pref("oldweapon",$oldweapon);
		set_module_pref("weapon",$newweapon);
	}
	return true;
}

function costumeshop_changearmor($newarmor){
	global $session;
	if ($newarmor){
		$reset = false;
	} else {
		$reset = true;
		$newarmor = get_module_pref("oldarmor");
		if (!$newarmor) return false;
	}
	$oldarmor = $session['user']['armor'];
	$upgraded = strpos($oldarmor," +1")!==false ? true : false;
	$downgraded = strpos($oldarmor," -1")!==false ? true : false;
	$changed = strpos($oldarmor, $newarmor)!==false ? false : true;
	if (!$changed && !$reset) return false;
	if ($upgraded && $changed){
		$session['user']['armor'] = $newarmor." +1";
		$oldarmor = str_replace(" +1","",$oldarmor);
	} elseif ($downgraded && $changed){
		$session['user']['armor'] = $newarmor." -1";
		$oldarmor = str_replace(" -1","",$oldarmor);
	} elseif ($changed){
		$session['user']['armor'] = $newarmor;
	}
	if ($reset){
		set_module_pref("oldarmor","");
		set_module_pref("armor","");
	} else {
		set_module_pref("oldarmor",$oldarmor);
		set_module_pref("armor",$newarmor);
	}
	return true;
}

function costumeshop_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "village":
		if($session['user']['location'] == get_module_setting("shoploc") &&
				get_module_setting("open")){
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			if (get_module_setting("christmastxt")){
				addnav("C?Costume Shop","runmodule.php?module=costumeshop&op=shop");
			}else{
				addnav("L?Haunter's Lodge","runmodule.php?module=costumeshop&op=shop");
			}
		}
		break;
	case "village-desc":
		if($session['user']['location'] == get_module_setting("shoploc") &&
				get_module_setting("open")){
			if (get_module_setting("christmastxt")){
				output("`n`&Laughing people are emerging from the `\$Costume Shop`& wearing fancy costumes!`0`n");
			}else{
				output("`n`7Visitors are emerging from the `\$Haunter's Lodge`7 wearing fancy costumes!`0`n");
			}
		}
		break;
	case "footer-weapons":
		$op = httpget("op");
		$weapon = get_module_pref("weapon");
		if ($weapon && $session['user']['weapon'] != $weapon && $op == "buy") costumeshop_changeweapon($weapon);
		break;
	case "footer-armor":
		$op = httpget("op");
		$armor = get_module_pref("armor");
		if ($armor && $session['user']['armor'] != $armor && $op == "buy") costumeshop_changearmor($armor);
		break;
	case "newday":
		$rleft = get_module_pref("rentleft");
		$costume = get_module_pref("newtitle");
		if ($costume != $session['user']['ctitle'] && $rleft >= 1){
			$rleft = 0;
			set_module_pref("rentleft",$rleft);
			set_module_pref("oldtitle","");
			set_module_pref("newtitle","");
			costumeshop_changeweapon("");
			costumeshop_changearmor("");
			output("`n`7You decide to send the costume back to the shop before it's due.`n");
		} elseif ($rleft >= 2 && $args['resurrection'] != 'true'){
			$rleft--;
			set_module_pref("rentleft",$rleft);
		} elseif ($rleft == 1 && $args['resurrection'] != 'true'){
			$rleft = 0;
			set_module_pref("rentleft",$rleft);
			$oldname = costumeshop_changetitle("");
			costumeshop_changeweapon("");
			costumeshop_changearmor("");
			debuglog("changed name to $oldname due to returning a costume");
			output("`n`7You send the costume back to the shop.`n");
		} elseif ($rleft < 0){
			$rleft = 0;
			set_module_pref("rentleft",$rleft);
			output("`n`7You didn't want to risk ruining the costume while fighting the dragon, so you sent it back to the shop.`n");
		}
		break;
	case "changesetting":
		if ($args['setting'] == "villagename"){
			if ($args['old'] == get_module_setting("shoploc")){
				set_module_setting("shoploc", $args['new']);
			}
		}
		$iscostume = strpos($args['setting'],"costume")!==false ? true : false;
		if ($iscostume && $args['module'] == "costumeshop"){
			$changed = false;
			if (strlen($args['new']) > 23 && $args['new']{24} == "`"){
				$changed = substr_replace($args['new'], "", 24);
				set_module_setting($args['setting'], $changed);
			} elseif (strlen($args['new']) > 25){
				$changed = substr_replace($args['new'], "", 25);
				set_module_setting($args['setting'], $changed);
			}
			if ($changed) output("`7%s was truncated to %s`7 due to being too long.`n", $args['setting'], $changed);
		}
		break;
	case "dragonkilltext":
		$rleft = get_module_pref("rentleft");
		if ($rleft == 0){
			break;
		} elseif (get_module_setting("keepdk")){
			$weapon = get_module_pref("weapon");
			if ($weapon) costumeshop_changeweapon($weapon);
			$armor = get_module_pref("armor");
			if ($armor) costumeshop_changearmor($armor);
			$rleft++;
		} elseif ($rleft >= 2){
			$rleft = -1;
			set_module_pref("rentleft",$rleft);
			$oldname = costumeshop_changetitle("");
			costumeshop_changeweapon("");
			costumeshop_changearmor("");
			debuglog("changed name to $oldname due to returning a costume");
		}
		set_module_pref("rentleft",$rleft);
		break;
	case "validateprefs":
		$userid=httpget("userid");
		$sql = "SELECT name,title,ctitle,weapon,armor FROM " .
			db_prefix("accounts") . " WHERE acctid=$userid";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$oldprefs=get_all_module_prefs("costumeshop",$userid);
		if ($args['rentleft']<1) {
			//Remove the player's costume
			output("Removing player's costume.`n");
			if ($oldprefs['oldweapon']) {
				$newname=change_player_ctitle($oldprefs['oldtitle'],$row);
				$sql = "UPDATE " . db_prefix("accounts") .
					" SET ctitle=\"" .  $oldprefs['oldtitle'] .
					"\", name=\"$newname\", weapon=\"" .
					$oldprefs['oldweapon'] . "\", armor=\"" .
					$oldprefs['oldarmor'] . "\" WHERE acctid=$userid";
				db_query($sql);
				if ($session['user']['acctid']==$userid) {
					$session['user']['ctitle']=$oldprefs['oldtitle'];
					$session['user']['name']=$newname;
					$session['user']['weapon']=$oldprefs['oldweapon'];
					$session['user']['armor']=$oldprefs['oldarmor'];
				}
				$args['rentleft']=0;
				$args['newtitle']="";
				$args['weapon']="";
				$args['armor']="";
				$args['oldtitle']="";
				$args['oldweapon']="";
				$args['oldarmor']="";
			} else {
				$args['validation_error'] =
					"`#Cannot remove costume; player isn't wearing one.";
			}
		} elseif (!$args['weapon'] || !$args['armor']) {
			//Things might break with blank armor or weapon.
			$args['validation_error'] =
				"`#User must have both armor and a weapon!";
		} elseif ($args['weapon']!=$row['weapon'] ||
				$args['armor']!=$row['armor'] ||
				$args['newtitle']!=$row['ctitle']){
			//Only update the database if something's changed
			if (!($oldprefs['oldtitle'] || $oldprefs['oldweapon'] ||
						$oldprefs['oldarmor'])) {
				$args['oldtitle']=addslashes($row['ctitle']);
				$args['oldweapon']=addslashes($row['weapon']);
				$args['oldarmor']=addslashes($row['armor']);
			}
			$newname = change_player_ctitle($args['newtitle'],$row);
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET ctitle=\"" . $args['newtitle'] .
				"\", name=\"$newname\", weapon=\"" . $args['weapon'] .
				"\", armor=\"" . $args['armor'] . "\" WHERE acctid=$userid";
			db_query($sql);
			if ($session['user']['acctid']==$userid) {
				$session['user']['ctitle']=stripslashes($args['newtitle']);
				$session['user']['name']=stripslashes($newname);
				$session['user']['weapon']=stripslashes($args['weapon']);
				$session['user']['armor']=stripslashes($args['armor']);
			}
		}
		break;
	}
	return $args;
}

function costumeshop_run(){
	global $session;
	$rleft = get_module_pref("rentleft");
	$rtime = get_module_setting("rentaltime");
	$cost = get_module_setting("cost");
	$totalcost = $cost*$session['user']['level'];
	$op = httpget("op");

	page_header("Costume Shop");
	villagenav();
	if (get_module_setting("christmastxt")){
		output("`c`b`\$Count Nesretep's Costume Shop`b`c`n");
	}else{
		output("`c`b`\$Count Nesretep's Haunter's Lodge`b`c`n");
	}

	if ($rleft > 0){
		output("`7As you admire the different costumes you wonder if you could change your current suit to another.");
		output("However `\$The Count`7 looks busy at the counter, so you decide to come back another time.`n`n");
	} elseif ($session['user']['gold'] < $totalcost){
		output("`7You walk around the shop looking at the different costumes for a while.");
		output("Unfortunately, all of the costumes are too expensive for you, so you leave the shop a bit disappointed.`n`n");
	} elseif ($op=="rent"){
		$session['user']['gold']-=$totalcost;
		debuglog("spent $totalcost gold renting a costume");
		$choice = httpget("costume");
		set_module_pref("rentleft",$rtime);
		if ($choice > 0 && $choice < 6){
			$costume = get_module_setting("costume".$choice);
			$weapon = get_module_setting("weapon".$choice);
			$armor = get_module_setting("armor".$choice);
		} elseif ($choice == "lodge"){
			$costume = get_module_setting("lodgecostume");
			$weapon = get_module_setting("lodgeweapon");
			$armor = get_module_setting("lodgearmor");
		} else {
			$costume = "`\$C`&l`@o`&w`\$n`&";
			$weapon = "Cream Pie";
			$armor = "Clown Nose";
		}
		$costumename = costumeshop_changetitle($costume);
		costumeshop_changeweapon($weapon);
		costumeshop_changearmor($armor);
		debuglog("changed name to $costumename due to renting a costume");
		output("`\$The Count`7 hands the suit to you. \"`)Ah, what an excellent choice!");
		if ($weapon || $armor){
			output(" Let me complete the costume with this %s`) and this %s`).", $weapon, $armor);
		} elseif ($weapon){
			output(" Let me complete the costume with this %s`).", $weapon);
		} elseif ($armor){
			output(" Let me complete the costume with this %s`).", $armor);
		}
		output("`7\"`n`n");
		output("`7With a big smile on your face, you take the %s`7 costume and put it on.`n`n", $costume);
	} else {
		$n = 0;
		for ($i = 1; $i <= 5; $i++){
			$costume = get_module_setting("costume".$i);
			if ($costume != ""){
				addnav(array("%s`0 costume (`^%s`0 gold)",$costume,$totalcost),"runmodule.php?module=costumeshop&op=rent&costume=".$i);
				$n++;
			}
		}
		if ($n == 0){
			addnav(array("`\$C`&l`@o`&w`\$n`0 costume (`^%s`0 gold)",$totalcost),"runmodule.php?module=costumeshop&op=rent&costume=clown");
			$n++;
		}
		if (get_module_setting("christmastxt")){
			output("`7You enter the brightly lit shop! You see huge wreaths, brightly colored glass orbs and other ornaments wherever you turn your gaze!");
			output(" You wander through the aisles, jaw agape. Suddenly you bump into a huge man, dressed as `\$Santa Claus`7.`n`n");
			output("`7The man laughs heartily and says, \"`)Happy Holidays, and welcome to my little shop! I am `\$Count Nesretep`). Please, look around and see if you find something you like!`7\"`n`n");
		}else{
			output("`7You enter the dimly lit shop, filled from wall to wall with hundreds of costumes.");
			output(" As you wander through the aisles of suits and cobwebs, you suddenly meet a tall, thin man, dressed in a black suit and cape.`n`n");
			output("`7\"`)Greetings traveller! I am `\$Count Nesretep`), and welcome to my shop!`7\"`n`n");
		}
		if ($session['user']['donation'] > 0){
			$costume = get_module_setting("lodgecostume");
			if ($costume != ""){
				addnav(array("%s`0 costume (`^%s`0 gold)",$costume,$totalcost),"runmodule.php?module=costumeshop&op=rent&costume=lodge");
				output("`7Suddenly he seems to recognize you and adds, \"`)Ah, a fellow Lodge member! I might have a special %s`) costume in storage if you are interested...`7\"`n`n", $costume);
			}
		}
		if (get_module_setting("christmastxt")){
			output("`7He laughs loudly as he leaves you. You start to browse the different costumes, still wondering how such a large man can move so fast.`n`n");
		}else{
			output("`7He vanishes into the shadows as suddenly as he appeared.`n`n");
		}
		output("`7A note on the wall says that the rental time is %s days.`n`n", $rtime);
		output("`7With so many costumes, you're having a hard time deciding which one you like the most.");
		output("Finally you make your decision.`n`n");
	}
	page_footer();
}
?>

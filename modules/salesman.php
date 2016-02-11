<?php
// addnews ready
// mail ready
// translator ready
/*
Shady Salesman
File:    salesman.php
Author:  Randy Yates aka Deimos
Date:    8/31/2004
Version: 1.5 (8/21/2005)

Shady Salesman is a special event module for the inn, wherein the Salesman
offers the player a deal.
The deal is that in exchange for gold*level the player purchases the mystery
item.
The mystery item is one of many things from the game, with a positive or
negative result.
Each result has a 1:4 chance of being negative.
The shady salesman will only offer a player this "great opportunity" once
per game day.
Also included is a rare gold coin item, which either Saucy or I will do
something with at a later point.

v1.1
Added interface to Saucy's Matthias module.
Yay teamwork!

v1.2
Removed return to inn navs so as to be dropped back into the inn navs automatically. (Thanks Kendaer)

v1.21
Changed default rawchance to 50.

v1.3
Added chance for the salesman to steal the cost of his item from the player 1:4 times if they turn down his offer.
Also removed Matthias module interface, since it'll eventually work the other way around.

v1.4
Fixed some typos and coding stylings, and a big error on the case numbering.

v1.5
Modified so that Cedrick's potions will give as much bonus as the ones he sells.
*/
function salesman_getmoduleinfo(){
	$info = array(
		"name"=>"Shady Salesman",
		"version"=>"1.5",
		"author"=>"`\$Red Yates",
		"category"=>"Inn Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Shady Salesman Settings, title",
			"rawchance"=>"Raw chance of encountering the salesman,range,5,100,1|50",
			"costperlevel"=>"Gold per level to buy from salesman,int|25",
			"carrydk"=>"Do max hitpoitns gained/lost carry across DKs?,bool|1",
		),
		"prefs"=>array(
			"Shady Salesman User Prefs, title",
			"seentoday"=>"Has the player encountered the salesman today,bool|0",
			"hascoin"=>"Does the player have the rare gold coin,bool|0",
			"extrahps"=>"How many extra hitpoints has the user gained/lost?,int",
		)
	);
	return $info;
}

function salesman_seentest(){
	$test=get_module_pref("seentoday","salesman");
	$rawchance=get_module_setting("rawchance","salesman");
	$chance=($test?0:$rawchance);
	return $chance;
}

function salesman_install(){
	module_addeventhook("inn", "require_once(\"modules/salesman.php\"); return salesman_seentest();");
	module_addhook("newday");
}

function salesman_uninstall(){
	return true;
}

function salesman_dohook($hookname,$args){
	switch($hookname){
	case "newday":
		set_module_pref("seentoday",0);
		break;
	}
	return $args;
}

function salesman_runevent($type){

	$innname = getsetting("innname", LOCATION_INN);
	page_header($innname);
	require_once("lib/http.php");
	global $session;
	$session['user']['specialinc'] = "module:salesman";
	$from="inn.php?";
	$cost=get_module_setting("costperlevel")*$session['user']['level'];
	$op=httpget('op');

	if ($session['user']['gold']<$cost){
		$session['user']['specialinc'] = "";
		set_module_pref("seentoday",1);
		output("`n`7While passing through the inn, a shady figure in a trenchcoat and fedora catches your eye and motions you over into his dark corner.");
		output("As you approach, however, he notices a distinct lack of jangle in your gold purse, and slinks off into the shadows.`n`n");
		output("You wonder what that was about, but turn around and continue on your way.");
	}elseif ($op=="pass"){
		$session['user']['specialinc'] = "";
		output("`n`7The salesman seems just too shady for you, and you pass on his offer.`n`n");
		output("He slinks away, muttering, \"`)You don't know what you're missing, pal.`7\"`n`n");
		output("You shake your head, and turn around to head back to the inn.`n");
		$take=e_rand(1,4);
		if ($take==1) {
			$session['user']['gold']-=$cost;
			//Should't happen, but just in case.
			if ($session['user']['gold']<0) $session['user']['gold']=0;
			output("As you walk away, you notice your gold purse feels lighter!`n`n");
			output("You lost `\$%s `7gold!",$cost);
		}
	}elseif ($op=="accept"){
		$session['user']['specialinc'] = "";
		$die=e_rand(1,get_module_pref("hascoin")?48:50);
		output("`n`7Shady though he is, you decide to take up the salesman on his offer.`n`n");
		$session['user']['gold']-=$cost;
		debuglog("spent $cost on the shady salesman.");
		switch($die){
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
		case 6:
			output("`7The salesman reaches into one of his deep pockets and pulls out a gem.");
			output("He hands the gem to you and and congratulates you on your wise purchase, then slinks off into the shadows.`n`n");
			$session['user']['gems']++;
			output("You receive a `%gem`7!");
			debuglog("got a gem from the shady salesman.");
			break;
		case 7:
		case 8:
			output("`7The salesman reaches into one of his deep pockets and pulls out a gold coin in a plastic case.");
			output("The salesman explains to you that this coin is very rare, and very old.");
			output("He then slinks off into the shadows as you examine the coin, which you angrily realize looks rather like every other gold coin you've ever seen.`n`n");
			$session['user']['gold']++;
			output("You receive `^1 gold`7...");
			debuglog("received 1 gold from the shady salesman.");
			break;
		case 9:
		case 10:
		case 11:
		case 12:
		case 13:
		case 14:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial and explains that it is a salve that he got from Crazy Audrey herself, and a wise purchase.");
			output("He then slinks off into the shadows as you apply the salve to your toes.`n`n");
			output("You `@gain`7 a turn!");
			$session['user']['turns']++;
			break;
		case 15:
		case 16:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial and explains that it is a salve that he got from Crazy Audrey herself, and a wise purchase.");
			output("He then slinks off into the shadows as you apply the salve to your toes.`n`n");
			output("Your toes soon smell as bad as Mireraband's!`n");
			if ($session['user']['turns']>0){
				output("You `\$lose`7 a turn!");
				$session['user']['turns']--;
			}
			break;
		case 17:
		case 18:
		case 19:
		case 20:
		case 21:
		case 22:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial and explains that it is a healing potion, and a wise purchase.");
			output("He then slinks off into the shadows as you drink the potion.`n`n");
			output("`&You feel healthy!`7");
			$session['user']['hitpoints']+=$session['user']['maxhitpoints']/4;
			break;
		case 23:
		case 24:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial and explains that it is a healing potion, and a wise purchase.");
			output("He then slinks off into the shadows as you drink the potion.`n`n");
			output("You feel `\$sick`7!");
			$session['user']['hitpoints']-=$session['user']['maxhitpoints']/4;
			if ($session['user']['hitpoints']<1){
				$session['user']['hitpoints']=1;
			}
			break;
		case 25:
		case 26:
		case 27:
		case 28:
		case 29:
		case 30:
			output("`7The salesman searches his deep pockets, then looking slightly embarrassed, he picks up a mug from the table behind him.");
			output("He says to you, \"`)You've purchased one of the best things there are around here, an ale from this very inn!`7\"");
			output("He hands you the mug and slinks off into the shadows as you drink it down.`n`n");
			$drunk=get_module_pref("drunkeness","drinks");
			if ($drunk<get_module_setting("maxdrunk","drinks")){
				output("You enjoy a cold ale!`n");
				$drunk+=33;
				set_module_pref("drunkeness",$drunk,"drinks");
				$add=e_rand(1,3);
				if ($add==1){
					$session['user']['turns']++;
					output("`&You feel vigorous!`7");
				}else{
					$session['user']['hitpoints']+=round($session['user']['maxhitpoints']/10);
					output("`&You feel healthy!`7");
				}
				output("`nYou feel buzzed!");
				apply_buff('buzz', array(
					"name"=>"`#Buzz",
					"rounds"=>10,
					"wearoff"=>"Your buzz fades.",
					"atkmod"=>1.25,
					"roundmsg"=>"You've got a nice buzz going.",
					));
			}else{
				output("`7In your drunkenness, you spill the drink on yourself, completely missing your mouth.");
			}
			break;
		case 31:
		case 32:
			output("`7The salesman searches his deep pockets, then looking slightly embarrassed, he picks up a mug from the table behind him.");
			output("He says to you, \"`)You've purchased one of the best things there are around here, an ale from this very inn!`7\"");
			output("He hands you the mug and slinks off into the shadows as you start to drink it down.`n`n");
			$drunk=get_module_pref("drunkeness","drinks");
			if ($drunk<get_module_setting("maxdrunk","drinks")){
				output("You make an ugly face, as it turned out to be one of Cedrick's beers, instead!`n");
				$drunk+=33;
				set_module_pref("drunkeness",$drunk,"drinks");
				$add=e_rand(1,3);
				if ($add==1){
					$session['user']['turns']--;
					output("You feel `\$sluggish`7!");
				}else{
					$session['user']['hitpoints']-=round($session['user']['maxhitpoints']/20);
					output("You feel `\$sick`7!");
				}
				output("`nYou feel tipsy!");
				apply_buff('buzz', array(
					"name"=>"`#Tipsiness",
					"rounds"=>10,
					"wearoff"=>"Your head clears up.",
					"defmod"=>.95,
					"roundmsg"=>"You are feeling rather tipsy."
					));
			}else{
				output("`7In your drunkenness, you spill the drink on yourself, completely missing your mouth.");
			}
			break;
		case 33:
		case 34:
		case 35:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial, and explains that it is one of Cedrick's magic vitality potions, and a wise purchase.");
			output("He then slinks off into the shadows as you drink the potion.`n`n");
			$hptype = "permanently";
			if (!get_module_setting("carrydk") ||
					(is_module_active("globalhp") &&
					 !get_module_setting("carrydk", "globalhp")))
				$hptype = "temporarily";
			$hptype = translate_inline($hptype);

			if (is_module_active("cedrikspotions")){
				$hp=get_module_setting("vitalgain","cedrikspotions");
			}else{
				$hp=1;
			}
			output("Your hitpoints have been `b%s`b `@increased`7 by %s!",
					$hptype, $hp);

			$session['user']['maxhitpoints']+=$hp;
			$session['user']['hitpoints']+=$hp;
			set_module_pref("extrahps", get_module_pref("extrahps") + $hp);
			break;
		case 36:
			if ($session['user']['maxhitpoints']>$session['user']['level']*10){
				output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
				output("He hands you the vial, and explains that it is one of Cedrick's magic vitality potions, and a wise purchase.");
				output("He then slinks off into the shadows as you drink the potion.`n`n");
				output("You cough and choke as the potion seems to go down all wrong.`n");
				$hptype = "permanently";
				if (!get_module_setting("carrydk") ||
						(is_module_active("globalhp") &&
						 !get_module_setting("carrydk", "globalhp")))
					$hptype = "temporarily";
				$hptype = translate_inline($hptype);

				if (is_module_active("cedrikspotions")){
					$hp=get_module_setting("vitalgain","cedrikspotions");
				}else{
					$hp=1;
				}

				output("Your hitpoints have been `b%s`b `\$decreased`7 by %s!", $hptype, $hp);
				$session['user']['maxhitpoints']-=$hp;
				set_module_pref("extrahps", get_module_pref("extrahps") - $hp);
				if ($session['user']['hitpoints']>$hp+1){
					$session['user']['hitpoints']-=$hp;
				}else{
					$session['user']['hitpoints']=1;
				}
			}else{
				output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
				output("He hands you the vial and explains that it is a healing potion, and a wise purchase.");
				output("He then slinks off into the shadows as you drink the potion.`n`n");
				output("You feel `\$sick`7!");
				$session['user']['hitpoints']-=$session['user']['maxhitpoints']/4;
				if ($session['user']['hitpoints']<1){
					$session['user']['hitpoints']=1;
				}
			}
			break;
		case 37:
		case 38:
		case 39:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial, and explains that it is one of Cedrick's magic charm potions, and a wise purchase.");
			output("He then slinks off into the shadows as you drink the potion.`n`n");
			output("`&You feel charming!`7");
			if (is_module_active("cedrikspotions")){
				$ch=get_module_setting("charmgain","cedrikspotions");
			}else{
				$ch=1;
			}
			$session['user']['charm']+=$ch;
			break;
		case 40:
			output("`7The salesman reaches into one of his deep pockets and pulls out a vial.");
			output("He hands you the vial, and explains that it is one of Cedrick's magic charm potions, and a wise purchase.");
			output("He then slinks off into the shadows as you drink the potion.`n`n");
			output("You cough and choke as the potion seems to go down all wrong.`n");
			if (is_module_active("cedrikspotions")){
				$ch=get_module_setting("charmgain","cedrikspotions");
			}else{
				$ch=1;
			}
			if ($session['user']['charm']>$ch){
				output("You feel less charming!");
				$session['user']['charm']-=$ch;
			}else{
				output("You feel less charming!");
				$session['user']['charm']=0;
			}
			break;
		case 41:
		case 42:
		case 43:
			if ((int)get_module_pref("weaponlevel", "wayofthehero") == 0) {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He hands you the box and explains that it is an upgraded version of your weapon, and a wise purchase.");
				output("He then slinks off into the shadows with your current weapon, as you open the box.`n`n");
				$previously_upgraded =
				strpos($session['user']['weapon']," +1")!==false ? true : false;
				$previously_downgraded =
				strpos($session['user']['weapon']," -1")!==false ? true : false;
				if (!$previously_upgraded && !$previously_downgraded){
					$session['user']['weapon'] = $session['user']['weapon']." +1";
					$session['user']['weapondmg']++;
					$session['user']['attack']++;
					$session['user']['weaponvalue']*=1.33;
					output("You find a `&%s`7 in the box!", $session['user']['weapon']);
				}elseif ($previously_upgraded){
					output("You find another `&%s`7 in the box, just like you had.", $session['user']['weapon']);
				}else{
					$session['user']['weaponvalue']*=1.33;
					$session['user']['weapon'] =
						str_replace(" -1","",$session['user']['weapon']);
					$session['user']['weapondmg']++;
					$session['user']['attack']++;
					output("You find a `&%s`7 in the box!", $session['user']['weapon']);
				}
			} else {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He then slinks off into the shadows, leaving you alone with this box.");
				output("Upon opening it you discover it is empty.`n`n");
			}
			break;
		case 44:
			if ((int)get_module_pref("weaponlevel", "wayofthehero") == 0) {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He hands you the box and explains that it is an upgraded version of your weapon, and a wise purchase.");
				output("He then slinks off into the shadows with your current weapon, as you open the box.`n`n");
				$previously_upgraded =
				strpos($session['user']['weapon']," +1")!==false ? true : false;
				$previously_downgraded =
				strpos($session['user']['weapon']," -1")!==false ? true : false;
				if (!$previously_upgraded && !$previously_downgraded){
					$session['user']['weapon'] = $session['user']['weapon']." -1";
					$session['user']['weapondmg']--;
					$session['user']['attack']--;
					$session['user']['weaponvalue']*=.75;
					output("You find a `&%s`7 in the box!", $session['user']['weapon']);
				}elseif ($previously_downgraded){
					output("You find another `&%s`7 in the box, just like you had.", $session['user']['weapon']);
				}else{
					$session['user']['weaponvalue']*=.75;
					$session['user']['weapon'] =
						str_replace(" +1","",$session['user']['weapon']);
					$session['user']['weapondmg']--;
					$session['user']['attack']--;
					output("You find a `&%s`7 in the box!", $session['user']['weapon']);
				}
			} else {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He then slinks off into the shadows, leaving you alone with this box.");
				output("Upon opening it you discover it is empty.`n`n");
			}
			break;
		case 45:
		case 46:
		case 47:
			if ((int)get_module_pref("armourlevel", "wayofthehero") == 0) {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He hands you the box and explains that it is an upgraded version of your armor, and a wise purchase.");
				output("He then slinks off into the shadows with your current armor, as you open the box.`n`n");
				$previously_upgraded =
				strpos($session['user']['armor']," +1")!==false ? true : false;
				$previously_downgraded =
				strpos($session['user']['armor']," -1")!==false ? true : false;
				if (!$previously_upgraded && !$previously_downgraded){
					$session['user']['armor'] = $session['user']['armor']." +1";
					$session['user']['armordef']++;
					$session['user']['defense']++;
					$session['user']['armorvalue']*=1.33;
					output("You find a `&%s`7 in the box!", $session['user']['armor']);
				}elseif ($previously_upgraded){
					output("You find another `&%s`7 in the box, just like you had.", $session['user']['armor']);
				}else{
					$session['user']['armorvalue']*=1.33;
					$session['user']['armor'] =
						str_replace(" -1","",$session['user']['armor']);
					$session['user']['armordef']++;
					$session['user']['defense']++;
					output("You find a `&%s`7 in the box!", $session['user']['armor']);
				}
			} else {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He then slinks off into the shadows, leaving you alone with this box.");
				output("Upon opening it you discover it is empty.`n`n");
			}
			break;
		case 48:
			if ((int)get_module_pref("armourlevel", "wayofthehero") == 0) {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He hands you the box and explains that it is an upgraded version of your armor, and a wise purchase.");
				output("He then slinks off into the shadows with your current armor, as you open the box.`n`n");
				$previously_upgraded =
				strpos($session['user']['armor']," +1")!==false ? true : false;
				$previously_downgraded =
				strpos($session['user']['armor']," -1")!==false ? true : false;
				if (!$previously_upgraded && !$previously_downgraded){
					$session['user']['armor'] = $session['user']['armor']." -1";
					$session['user']['armordef']--;
					$session['user']['defense']--;
					$session['user']['armorvalue']*=.75;
					output("You find a `&%s`7 in the box!", $session['user']['armor']);
				}elseif ($previously_downgraded){
					output("You find another `&%s`7 in the box, just like you had.", $session['user']['armor']);
				}else{
					$session['user']['armorvalue']*=.75;
					$session['user']['armor'] =
						str_replace(" +1","",$session['user']['armor']);
					$session['user']['armordef']--;
					$session['user']['defense']--;
					output("You find a `&%s`7 in the box!", $session['user']['armor']);
				}
			} else {
				output("`7The salesman reaches into one of his deep pockets and pulls out a very large box.");
				output("He then slinks off into the shadows, leaving you alone with this box.");
				output("Upon opening it you discover it is empty.`n`n");
			}
			break;
		case 49:
		case 50:
			output("`7The salesman reaches into one of his deep pockets and pulls out a gold coin in a plastic case.");
			output("The salesman explains to you that this coin is very rare, and very old.");
			output("He then slinks off into the shadows as you examine the coin, which you look at skeptically, before realizing it really is very rare and very old.`n`n");
			set_module_pref("hascoin",1);
			output("You put the rare gold coin into a hidden pocket, and hope you don't forget it is there.");
			output("`n`nYou turn around and head back to the inn.");
			break;
		}
	}else{
		set_module_pref("seentoday",1);
		output("`n`7While passing through the inn, a shady figure in a black trenchcoat and fedora catches your eye and motions you over into his dark corner.");
		output("You cautiously approach the man, wondering what he could want with you.");
		output("Once you are close by he finally speaks, \"`)Greetings, adventurer.");
		output("You look like you could use a break, and I am just the person to give you such a break.");
		output("For the pittance of only `^%s`) gold, I can offer you something that will be a great asset to you on your travels.", $cost);
		output("Bear in mind, I don't offer my services to just anyone, so you're very lucky.");
		output("Although, you will have to decide quickly, as I am quite busy.`7\"");
		output("He stands there with his hands in his deep pockets, awaiting your decision.`n`n");
		output("You ponder the shady salesman's pitch carefully, wondering how useful this mystery item might actually be, somewhat doubting that the salesman was fully honest.");
		addnav(array("Accept the Salesman's Offer (`^%s`0 gold)", $cost), $from."op=accept");
		addnav("Pass on the Salesman's Offer",$from."op=pass");
	}
}

function salesman_run(){
}
?>

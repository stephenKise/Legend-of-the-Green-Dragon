<?php
//addnews ready
// mail ready
// translator ready
/**ss**********************ss***************
/ LoneStrider's Thief script. .  . LoneStrider's pals strike
/ version 1.55
/ 2.23.04  (7th revision) -scs-

v1.8 (XChrisX)
 - Changed to support multiple enemies. Removed buff, added real thieves. Hidden
   hitpoints for Lonestrider.

v1.7 (Shannon)
 - interface with Oliver's Jewelry to have LS steal items players are wearing

Version '1.6' (jt)
  - Made into a 0.9.8 module with some admin control over features.

Additions for 1.55 (strider)
  - Fixed rewards for elves.
  - Adjusted the chances of events
  - Fixed the navigation so it won't confuse players.

ver 1.5
  -Now adds a line in the news when activated -ss
  -Added some debugging logs
  -Added the Legendgard Race Array (see special note below)
  -Added Race Specific interaction (aka: Lonestrider honors friendly elves.)

/ Version History:
Ver 1.1 by Strider (of Legendgard)
  -Lonestrider does a random backstab even when user kills thieves. -ss
  -lose gold as well as gems when you're beaten and a few spelling
   corrections -ss
Ver .95 by MightyE (of Central)
  -fighting back, running, distracting and stabbing knives buff -me
Ver .8 by JT (of Dragoncat)
  - Modified slightly for bug fixes and clarity (and effect) by JT
  - those bruises hurt
**ss**************************ss************/
// -Originally by: Strider
// -Contributors: MightyE, JT, Shannon Brown
// Feb 2004  - Legendgard Script Release


function thieves_getmoduleinfo(){
	$info = array(
		"name"=>"Lonestrider's Band",
		"version"=>"1.8",
		"author"=>"Shawn Strider",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Lonestrider's Band Settings,title",
			"mingems"=>"How many gems do you need before Lonestrider will actually bother you,range,1,20,1|5",
			"costwin"=>"Percent of gems Lonestrider demands to leave player alone,floatrange,0,100,2.5|5",
			"costlose"=>"Percent of gems Lonestrider takes if player loses,floatrange,0,100,2.5|10",
			"costrun"=>"Percent of gems it requires to try and distract Lonestrider,floatrange,0,100,2.5|2.5",
			"chancedistract"=>"Chance that Lonestrider will be distracted,range,0,100,1|50",
			"chancebackstab"=>"Chance that Lonestrider gets a nasty backstab,range,0,100,1|0",
			"elffriend"=>"Will Lonestrider help out elves,bool|0",
			"losegold"=>"Will Lonestrider steal gold as well,bool|0",
			"usemultis"=>"Use multiple enemies for the fight?,enum,multi,Yes,nomulti,No,setting,According to game settings and DK requirements|setting",
		),
	);
	return $info;
}

function thieves_chance(){
	global $session;
	// This is a continuous function starting at 10% at the minimum gems
	// (just to warn the player Lonestrider is out there even if he doesn't)
	// have the minimum gems, up to 100% at (5 * the minimum)
	// We need the module specification here because when we are called
	// from the chance evaluation string the module is not necessarily loaded
	// yet.
	$mingems = get_module_setting("mingems", "thieves");
	$gems = $session['user']['gems'];
	if ($gems <= $mingems) return 10;
	if ($gems >= 5*$mingems) return 100;
	$scale = round((($gems-$mingems)/(4*$mingems))*90, 0)+10;
	return $scale;
}

function thieves_jewels(){
	global $session;

	$countjewel = get_module_pref("totalheld","jeweler");
	$losechance = 25 - (e_rand(1,5) * $countjewel);
	if ($countjewel == 0) $losechance == -1;
	$odds = e_rand(1,20);
	$selectjewel = "";
	if ($losechance >= $odds) {
		if (e_rand(1,2) == 1 && get_module_pref("ringheld","jeweler") == 1) {
			$selectjewel = "ring";
			set_module_pref("ringheld",0,"jeweler");
			$countjewel --;
			set_module_pref("totalheld",$countjewel,"jeweler");
		} elseif (e_rand(1,4) == 1 && get_module_pref("braceletheld","jeweler")) {
			$selectjewel = "bracelet";
			set_module_pref("braceletheld",0,"jeweler");
			$countjewel --;
			set_module_pref("totalheld",$countjewel,"jeweler");
		} elseif (e_rand(1,7) == 1 && get_module_pref("necklaceheld","jeweler")) {
			$selectjewel = "necklace";
			set_module_pref("necklaceheld",0,"jeweler");
			$countjewel --;
			set_module_pref("totalheld",$countjewel,"jeweler");
		} elseif (e_rand(1,10) == 1 && get_module_pref("amuletheld","jeweler")) {
			$selectjewel = "amulet";
			set_module_pref("amuletheld",0,"jeweler");
			$countjewel --;
			set_module_pref("totalheld",$countjewel,"jeweler");
		} elseif (e_rand(1,15) == 1 && get_module_pref("chokerheld","jeweler")) {
			$selectjewel = "choker";
			set_module_pref("chokerheld",0,"jeweler");
			$countjewel --;
			set_module_pref("totalheld",$countjewel,"jeweler");
		}
	}
	if ($selectjewel != "") output("While you were lying prone, Lonestrider and his men took a fancy to your jeweled %s, and it is gone.`n`n",$selectjewel);
	debuglog("lost a $selectjewel to LoneStrider");
	return true;
}


function thieves_install(){
	module_addeventhook("forest",
			"require_once(\"modules/thieves.php\");
			return thieves_chance();");
	return true;
}

function thieves_uninstall(){
	return true;
}

function thieves_dohook($hookname,$args){
	return $args;
}

function thieves_elf_help($from) {
	global $session;
	$gems = $session['user']['gems'];
	$op = httpget("op");
	$salut = translate_inline($session['user']['sex'] ? "noble Lady":"dear Sire");

	if ($gems == 0) {
		output("`n`n`6An ill wind blows from the West. Before you know it, thieves have surrounded you.");
		output("Under your breath, you curse yourself for not seeing them sooner.");
		output("They begin to demand gems and threaten you.`n`n");
		output("`^You try to explain that you don't have any gems, so they search you.");
		output("Upon finding none, they bring you to their leader `\$Lonestrider `6to figure out what to do with you.`n`n");
		output("`\$Lonestrider `6bows slightly as soon as they push you before him.");
		output("He glares at the bandits and shakes his head.`n");
		output("`\$\"I'm sorry about the mixup, these comrades of mine seem to have confused you for someone else. They tell me you're travelling without a gem to call your own. No elf should be without a single gem.  Please, consider this a gift.\"`n");
		output("`^You gain `%1 gem`0");
		$session['user']['gems']++;
		$session['user']['specialinc']="";
		debuglog("gained 1 gem from Lonestrider for being an elf.");
	} elseif ($op=="search" || $op=="") {
		$session['user']['specialmisc']="";
		output("`n`n`3A strange wind blows from the West.");
		output("Your keen `6elvish`3 eyes glance about.");
		output("A twig snaps and suddenly, several shady elves surround you.");
		output("A tall, well dressed elf with fair skin jumps down from the trees and bows in a display of elfin nobility.");
		output("You curse yourself for not seeing them sooner, for this is none other than the infamous `\$Lonestrider`3 and his pack of thieves.");
		output("Your mind races through a handful of options before `\$Lonestrider `3raises his hand in a show of good faith.`n`n");
		output("`\$\"Greetings and salutations my fine elf. Wonderful day for a walk in the forest to solidify the foundations of our fortunes, isn't it?\"`3`n");
		output("You hear a dozen hidden thieves chime with laughter.`n");
		output("`\$\"Don't look so afraid my %s, I don't have any intention of robbing you. I try to protect other `6Elves`\$, when I can.  Well, I admit we run into an occasional dry spell where anyone will be asked to contribute to our wages, but I honor the elements and the fey blood in my heart.`nI couldn't help but to notice that you were looking a bit lost there a moment ago and I thought we might be able to help a fellow elvish adventurer.\"`n`n", $salut);
		output("`3Still a bit stunned at the speed of which these bandits overtook you, you consider your reply in silence.");
		output("You know that `\$Lonestrider `3is normally worth quite a bit of money, dead or alive. . . if you can catch him.");
		output("Now he stares at you with his blue-green eyes and oddly, you feel at ease, as if you're safe for a few moments in his company.");
		output("Some of his rogues are starting to get impatient as you consider your options and you decide you have to say or do something.`n`n");
		output("`7Your reply is?`n");
		//your options////////////////////////////////
		rawoutput("<a href=\"".$from."op=creatures\">");
		output("\"I'm just looking for creatures.\"");
		rawoutput("</a><br /><a href=\"".$from."op=money\">");
		output("\"I'm a little worried about money.\"");
		rawoutput("</a><br />");
		if (is_module_active("specialtythiefskills") &&
				$session['user']['specialty']=="TS") {
			rawoutput("<a href=\"".$from."op=thiefskill\">");
			output("\"I'm hoping to be a great thief like you someday!\"");
			rawoutput("</a><br />");
		}
		rawoutput("<a href=\"".$from."op=fine\">");
		output("\"Thank you Lonestrider, but I'm fine!\"");
		rawoutput("</a><br/>");
		rawoutput("<a href=\"".$from."op=stand\">");
		output("\"I'm here to collect the bounty on your HEAD!\"");
		rawoutput("</a><br />");
		///hidden so the html tags will work//
		addnav("",$from."op=creatures");
		addnav("",$from."op=money");
		if (is_module_active("specialtythiefskills") &&
				$session['user']['specialty']=="TS") {
			addnav("",$from."op=thiefskill");
		}
		addnav("",$from."op=fine");
		addnav("",$from."op=stand");
		/////////////////////////////////////////
		addnav("Answer Lonestrider?");
		addnav("Creatures",$from."op=creatures");
		addnav("Money",$from."op=money");
		if (is_module_active("specialtythiefskills") &&
				$session['user']['specialty']=="TS") {
			addnav("Thief Skill",$from."op=thiefskill");
		}
		addnav("Nothing",$from."op=fine");
		addnav("Kill Lonestrider!");
		addnav("Attack!",$from."op=stand");
	} elseif ($op =="stand"||$op=="fight"||$op=="run") {
		$costlose = round(($gems*get_module_setting("costlose")/100),0);
		if ($costlose < 0) $costlose = 0;
		thieves_fight($costlose);
	} elseif ($op == "thiefskill") {
		$session['user']['specialinc'] = "";
		// We know the user has a gem since otherwise Lonestrider would
		// give them one (since we're in the elf side)
		require_once("lib/increment_specialty.php");
		output("`n`n`6You anxiously tell `\$Lonestrider`6 a short story of your adventures and mention that you're also a elvish thief, following in his footsteps.");
		output("A puckish smile crosses his lips and he laughs.");
		output("You feel a breeze tickle the back of your neck and you turn around to check it out.");
		output("Next thing you know, `\$Lonestrider`6 is dangling your gem pouch in his hands with a mischievious smirk.");
		output("You laugh just a little, then you ask for your pouch back.");
		output("He tosses it back, but somehow keeps a gem between his fingers.`n`n");
		output("\"`\$Well my %s, I'll just keep this gem and teach you a little trick of the trade in exchange.`6\"`n", $salut);
		output("`6You and the other thieves listen very carefully as he tells a wonderful story about robbing the dwarves of their gems.");
		output("When he's done, you part ways and feel considerably wiser.");
		$session['user']['gems']--;
		increment_specialty("`\$");
		debuglog("paid a gem to Lonestrider for thief skills.");
	} elseif ($op == "money") {
		$session['user']['specialinc'] = "";
		if ($session['user']['gems']>3*get_module_setting("mingems")){
			output("`n`n`6Depressed, you tell `\$Lonestrider`6 that you're trying to earn some extra money.");
			output("He seems to understand and nods his head with a small smile.`n`n");
			output("`\$\"Well my %s, perhaps this will help. I, umm. . . found it in the village.  I guess someone didn't want it. Now you can have it.\"`n", $salut);
			$money = $session['user']['level'] * e_rand(10, 100);
			$session['user']['gold']+=$money;
			output("`6He tosses you a small leather bag.");
			output("You're surprised to find `^%s gold `6 in the little pouch!", $money);
			debuglog("gained $money gold from Lonestrider for being an elf.");
		} else {
			output("`n`n`6Depressed, you tell `\$Lonestrider`6 that you're having no luck finding `%gems`6.");
			output("He seems to understand and nods his head with a small smile.`n`n");
			output("`\$\"Well my %s, perhaps this will help. Don't worry, it's been a good day of looting dwarves for me.\"`n", $salut);
			$gems = e_rand(2, 3);
			$session['user']['gems']+=$gems;
			output("`6He tosses you a small leather bag.");
			output("You're surprised to find `%%s gems `6in the little pouch.", $gems);
			debuglog("gained $gems gem from Lonestrider for being an elf.");
		}
	} elseif ($op == "creatures") {
		$session['user']['specialinc']="";
		output("`n`n`6After a full day of fighting, you tell `\$Lonestrider`6 that you're looking for more creatures to kill.");
		output("He shrugs slightly and doesn't say much, but his puckish smile never leaves his fair features.");
		output("He offers you a flask of water before you resume your fighting.`n`n");
		if ($session['user']['hitpoints'] < $session['user']['maxhitpoints']) {
			output("`^You drink the water and discover that your health has returned!`n");
			$session['user']['hitpoints']=$session['user']['maxhitpoints'];
		} else {
			output("`^You drink the water and feel invigorated!`n");
			output("`&You gain a forest fight.");
			$session['user']['turns']++;
		}
	} else {
		$session['user']['specialinc']="";
		output("`n`n`6You're a little more relaxed about this infamous rogue, but you tell `\$Lonestrider`6 that you're fine and quickly take your leave.");
		output("He doesn't say a word, but his puckish smile never leaves his face as you hear a dozen hidden elves begin to sing songs in the forest behind you.");
		output("`n`n`6Something about this encounter makes you feel a little more rested and at ease with the forest.`n");
		apply_buff('gladesong',
			array(
				"name"=>"`#Gladesinging",
				"rounds"=>4,
				"wearoff"=>"The elftouched sensation fades...",
				"atkmod"=>2,
				"roundmsg"=>"You think you hear a haunting melody.",
				"schema"=>"module-thieves",
			)
		);
	}
	output("`0");
}

function thieves_fight($costlose) {
	$op = httpget("op");
	global $session;

	if ($op=="stand"){
		$dkb = round($session['user']['dragonkills']*.1);
		$usemultis = get_module_setting("usemultis");
		if ($usemultis == "multi") {
			$usemultis = true;
		} elseif ($usemultis == "setting") {
			$dklimit = getsetting("multifightdk", 10);
			if ($session['user']['dragonkills'] >= $dklimit) {
				$usemultis = true;
			} else {
				$usemultis = false;
			}
		} else {
			$usemultis = false;
		}
		if ($usemultis == true) {
			$badguylevel = $session['user']['level']+1;
			$badguyhp = $session['user']['maxhitpoints'] * 0.66;
			$badguyatt = $session['user']['attack'];
			$badguydef = $session['user']['defense'];
			if ($session['user']['level'] > 9) {
				$badguyhp *= 1.05;
			}
			if ($session['user']['level'] < 4) {
				$badguyhp *= .9;
				$badguyatt *= .9;
				$badguydef *= .9;
				$badguylevel--;
			}
			$lonestrider = array(
				"creaturename"=>translate_inline("`6Lonestrider`0"),
				"creaturelevel"=>$badguylevel,
				"creatureweapon"=>translate_inline("Jewel-hilted dagger"),
				"creatureattack"=>$badguyatt,
				"creaturedefense"=>$badguydef,
				"creaturehealth"=>INT_MAX,
				"diddamage"=>0,
				"hidehitpoints"=>true, // Hide the ridiculous amount of health, Lonestrider has
				"cannotbetarget"=>true,
				// Setting the next one to ANYTHING will make this badguy flee, if only he is left in the fight.
				// TRUE would lead to output the standard text.
				// Any string will be interpreted as TRUE AND it will replace the standard text.
				"fleesifalone"=>"{badguy} recognizes that he will be no match for you and flees.",
				"noadjust"=>1, // This is a pre-generated monster
				// This next line looks odd, but it's basically telling the
				// battle code, not to do the determination for surprise.  This
				// means player gets first hit against Lonestrider, he will never
				// go first.
				"didsurprise"=>1,
				"type"=>"lonestrider");
			$thief = array(
				"creaturename"=>translate_inline("`\$Lonestrider's Thief`0"),
				"creaturelevel"=>$badguylevel,
				"creatureweapon"=>translate_inline("Stabbing Knive"),
				"creatureattack"=>$badguyatt,
				"creaturedefense"=>$badguydef,
				"creaturehealth"=>round($badguyhp,0),
				"diddamage"=>0,
				"noadjust"=>1, // This is a pre-generated monster
				"didsurprise"=>1,
				"type"=>"lonestrider");
			$stack = array();
			$stack[] = $lonestrider;
			$maxthieves = ceil(min(15, $session['user']['level'])/3);
			for ($i=0;$i<$maxthieves;$i++) $stack[] = $thief;
		} else {
			$badguylevel = $session['user']['level']+1;
			$badguyhp = $session['user']['maxhitpoints'] * 1.05;
			$badguyatt = $session['user']['attack'];
			$badguydef = $session['user']['defense'];
			if ($session['user']['level'] > 9) {
				$baduyhp *= 1.05;
			}
			if ($session['user']['level'] < 4) {
				$badguyhp *= .9;
				$badguyatt *= .9;
				$badguydef *= .9;
				$badguylevel--;
			}
			$badguy = array(
				"creaturename"=>translate_inline("`\$Lonestrider's Thieves`0"),
				"creaturelevel"=>$badguylevel,
				"creatureweapon"=>translate_inline("Many Stabbing Knives"),
				"creatureattack"=>$badguyatt,
				"creaturedefense"=>$badguydef,
				"creaturehealth"=>round($badguyhp,0),
				"diddamage"=>0,
				"noadjust"=>1, // This is a pre-generated monster
				// This next line looks odd, but it's basically telling the
				// battle code, not to do the determination for surprise.  This
				// means player gets first hit against Lonestrider, he will never
				// go first.
				"didsurprise"=>1);
			apply_buff('thieves', array(
				"startmsg"=>"`n`^You are surrounded by thieves with many tiny daggers!`n`n",
				"name"=>"`%Stabbing Knives",
				"rounds"=>15,
				"wearoff"=>"The thieves have become exhausted.",
				"minioncount"=>min(15, $session['user']['level']),
				"mingoodguydamage"=>0,
				"maxgoodguydamage"=>1+$dkb,
				"effectmsg"=>"`\$A thief `4stabs you for `\${damage}`4 damage.",
				"effectnodmgmsg"=>"`\$A thief `4tries to stab you but `^MISSES`4.",
				"effectfailmsg"=>"`\$A thief `4tries to stab you but `^MISSES`4.",
				"schema"=>"module-thieves",
				));
			$stack[] = $badguy;
		}
		$attackstack = array(
			'enemies'=>$stack,
			'options'=>array('type'=>'lonestrider')
		);
		$session['user']['badguy']=createstring($attackstack);
		$op="fight";
		httpset('op', "fight");
	}
	if ($op=="run"){
		output("There are too many thieves blocking the way now, you have no chance to run!");
		$op="fight";
		httpset('op', "fight");
	}
	if ($op=="fight"){
		$battle=true;
	}
	if ($battle){
		require_once("battle.php");
		if ($victory){
			if (e_rand(1, 100) < get_module_setting("backstabchance")) {
				$costlose2 = $costlose*1.5;
				if ($costlose2 > $session['user']['gems'])
					$costlose2 = $session['user']['gems'];
				$session['user']['gems']-=$costlose2;
				output("`n`\$Lonestrider's`6 thieves lay slain at your feet but the elfin leader has vanished.");
				output("Eagerly you glance about the area, looking for `\$Lonestrider`6 himself.");
				output("The forest is absolutely silent.");
				output("Suddenly, you feel something cold and sharp against your neck.");
				output("The speed of elves is often astonishing, but the silence should have given him away.`n`n");
				output("\"`\$That was well done, but our business isn't concluded as of yet. I'm afraid you'll have to excuse the knife at your throat.  Just a formality, really. Now, before taking my leave, I'll take some of those gems.`6\"`n`n");
				output("You're about to say something exceptionally clever when a crack against the back of your skull sends you spiraling into darkness.");
				output("You wake up with a splitting headache and find `6that `\$Lonestrider`6 has taken `%%s gems`6 from your unconcious body.", $costlose2);
				debuglog("lost $costlose2 gems to Lonestrider backstab");
				if (get_module_setting("losegold")) {
					$goldloss = $session['user']['gold'];
					$session['user']['gold'] = 0;
					output("Additionally, you find `^%s gold`6 gone.", $goldloss);
					debuglog("lost $goldloss gold to Lonestrider backstab");
				}
				require_once("lib/taunt.php");
				$taunt = select_taunt_array();
				addnews("`%%s`2 challenged `4Lonestrider `2and his band of thieves and put up a fierce fight! Unfortunately, `\$Lonestrider `2got the last blow in.`n%s",$session[user][name], $taunt);
				$session['user']['specialmisc']="";
				$session['user']['specialinc']="";
			} else {
				output("`n`6Many of `\$Lonestrider's`6 thieves lay slain at your feet.");
				output("`\$Lonestrider`6 himself has disappeared at some point in the battle, when things were looking sour for his men.`n");
				if (is_module_active("dag")) {
					//one-sixth of max bounty at this level, roughly 67/level
					$bounty = round(get_module_setting("bountymax", "dag")*
							$session['user']['level'] / 6, 0);
					output("Unfortunately this means that you will not have any chance to bring in his head for reward from Dag.");
					output("Some of the dead thieves around you have prices on their heads though, and so you are able to collect `^%s gold `6for their slaying.`n", $bounty);
					$session['user']['gold']+=$bounty;
					debuglog("gained $bounty gold for the head of some of Lonestrider's thieves");
				}
				output("While casually rifling through some of the dead thieves' pockets, you discover `5a healing potion`6, which you quickly imbibe.");
				if ($session['user']['specialmisc']=="triedtorun") {
					output("`n`nYou find none of the gems you tried to use to distract the thieves on any of the bodies.");
					output("`\$Lonestrider`6 must have taken them when he ran.");
				}
				if ($session['user']['hitpoints'] <
						$session['user']['maxhitpoints'])
					$session['user']['hitpoints']=
						$session['user']['maxhitpoints'];
			}
			$session['user']['specialinc']="";
			$session['user']['specialmisc']="";
			strip_buff('thieves');
		}elseif ($defeat){
			strip_buff('thieves');
			require_once("lib/taunt.php");
			$taunt = select_taunt_array();
			$session['user']['gems']-=$costlose;
			addnews("`%%s`6 challenged `4Lonestrider `6and his band of thieves, but was no match for the rogues!`n%s",$session['user']['name'],$taunt);
			debuglog("lost $costlose gems when Lonestrider's thieves knocked him unconscious.");
			output("`n`\$Lonestrider's`6 thieves have laid you unconscious, and help themselves to the `%%s`6 gems that they find in one of your purses.", $costlose);
			if ($session['user']['gems'] > 0) {
				output("Fortunately for you, they do not notice your other purse containing the remainder of your gems.");
			}
			$session['user']['turns']--;
			$iname = getsetting("innname", LOCATION_INN);
			$vname = getsetting("villagename", LOCATION_FIELDS);
			output("`n`nYou lay, moaning on the forest trail, barely clinging to life, as small forest critters and the occasional adventurer pass you by, leaving you to die.");
			output("It is not until a villager from hated Eythgim Village sees you that your aid comes though.");
			output("He raises a healing potion to your lips, and drags you to %s in %s.", $iname, $vname);
			output("There, he purchases a room from %s`6, and leaves coin for your care, departing before you fully gain consciousness, leaving no opportunity to thank him.`n`n", getsetting("barkeep", "`tCedrik"));
			if (is_module_active("jeweler")) thieves_jewels();
			output("`^You lose a forest fight while unconscious.");
			$session['user']['specialinc']="";
			$session['user']['specialmisc']="";
			$session['user']['boughtroomtoday']=1;
			if ($session['user']['hitpoints'] <
					$session['user']['maxhitpoints'])
				$session['user']['hitpoints']=$session['user']['maxhitpoints'];
			$session['user']['location'] = $vname;
			addnav("Wake Up!","inn.php?op=strolldown");
		}else{
			fightnav(true,true);
		}
	}
}

function thieves_runevent($type)
{
	require_once("lib/buffs.php");
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:thieves";

	$gems = $session['user']['gems'];

	if ($session['user']['race'] == "Elf" &&
			get_module_setting("elffriend")) {
		thieves_elf_help($from);
		return;
	}

	// Okay this person isn't an elf.  Do our worst!
	if ($gems<=get_module_setting("mingems")){
		output("`n`n`6An ill wind blows from the West.");
		output("Before you know it, thieves have surrounded you.");
		output("Under your breath, you curse yourself for not seeing them sooner.");
		output("They begin to demand gems and threaten you, kicking at your purse to listen for gems.`n`n");
		output("`^Not hearing enough gem-like sounds to interest them, they rough you up a bit before leaving you in the forest alone.`0");
		if ($session['user']['charm'] > 0) $session['user']['charm'] -=1;
		$session['user']['specialinc']="";
		$session['user']['hitpoints'] -= 5;
		if ($session['user']['hitpoints']<1)
			$session['user']['hitpoints']=1;
		addnews("`3%s`6 got roughed up by `4Lonestrider `6 and his cronies for having little of value.",$session['user']['name']);
		return;
	}

	$costwin = round(($gems*get_module_setting("costwin")/100), 0);
	if ($costwin <= 0) $costwin = 1;
	$costrun = round(($gems*get_module_setting("costrun")/100),0);
	if ($costrun <= 0) $costrun = 1;
	$costlose = round(($gems*get_module_setting("costlose")/100),0);
	if ($costlose < 0) $costlose = 0;

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`n`n`6An ill wind blows from the East.");
		output("Before you know it, thieves have surrounded you.");
		output("Under your breath, you curse yourself for not seeing them sooner.");
		output("They demand `%%s`6 gems and threaten you.", $costwin);
		output("Their elven leader `\$Lonestrider `6shouts above their jeers that you stand or deliver!");
		output("You know that he has you completely outnumbered and you certainly don't feel like dying today.`n`n");
		output("`7You may choose to meet their demands for `%%s`7 gems, stand and fight the thieves, using the skills you learned as a healthy `@%s`7 to try and defend yourself, or try to disappear into the foliage surrounding you (by throwing `%%s`7 %s to distract them).", $costwin, translate_inline($session['user']['race'], "race"), $costrun, translate_inline(($costrun > 1)?"gems" : "gem"));
		output("You know that if you do not give into their demands, and fail, they are likely to take a higher price than they now demand.");
		addnav(array("Give %s gems", $costwin),$from."op=give");
		addnav("Run away!",$from."op=runawaylikealittlesissybaby");
		addnav("Stand and fight!",$from."op=stand");
	}elseif ($op=="give"){
		output("`n`n`6You realize that `\$Lonestrider's`6 forces are more than a match for you, and fearing for your life, you elect to give them the `%%s`6 gems they have demanded.", $costwin);
		$session['user']['gems']-=$costwin;
		debuglog("relinquished $costwin gems to pay off Lonestrider's thieves");
		$session['user']['specialinc']="";
	}elseif ($op=="runawaylikealittlesissybaby"){
		output("`n`n`6You realize that `\$Lonestrider's`6 forces are a greedy and easily distracted bunch, so you throw `%%s`6 %s at them and begin to run.", $costrun, translate_inline(($costrun > 1)?"gems":"gem"));
		$session['user']['gems']-=$costrun;
		debuglog("tossed $costrun gems trying to distract Lonestrider's thieves");
		if (e_rand(1,100) > get_module_setting("chancedistract")) {
			output("`n`nThe thieves are not so easily distracted as you thought though, and they catch up to you, forcing a confrontation.`n`n");
			output("`\$Lonestrider`6 stops to gather up the %s before joining his jeering companions, prepared to fight.", translate_inline(($costrun > 1)? "gems":"gem"));
			$session['user']['specialmisc']="triedtorun";
			addnav("Stand and fight!",$from."op=stand");
		}else{
			$session['user']['specialinc']="";
			output("`n`nThe thieves are easily distracted, and you make your escape!");
		}
	} elseif ($op=="stand" || $op=="fight" || $op=="run") {
		thieves_fight($costlose);
	}
	output("`0");
}

function thieves_run(){
}
?>

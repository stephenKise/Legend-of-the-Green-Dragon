<?php
// translator ready
// addnews ready
// mail ready

require_once("lib/http.php");
require_once("lib/villagenav.php");

function scavenge_getmoduleinfo(){
    $info = array(
        "name"=>"Scavenger",
        "version"=>"1.25",
        "author"=>"Copied and Pasted by Sneakabout",
        "category"=>"Graveyard Specials",
        "download"=>"core_module",
        "settings"=>array(
            "Scavenger - Settings,title",
            "villagechance"=>"Raw Chance of Wraith appearing in the Village?,range,0,100,5|20",
            "gravechance"=>"Raw Chance of Pit Appearing in Graveyard?,range,0,100,5|20",
        ),
        "prefs"=>array(
            "terror_wraith"=>"Wraith curse level?,int|0",
            "seen_wraith"=>"Wraith curse active(good/bad)?,int|0",
        )
    );
    return $info;
}

function scavenge_install(){
	/*$forestchance=get_module_setting("forestchance");
	$villagechance=get_module_setting("villagechance");*/
    module_addeventhook("graveyard",
			"return get_module_setting(\"gravechance\", \"scavenge\");");
    module_addeventhook("village",
			"return get_module_setting(\"villagechance\", \"scavenge\");");
	module_addhook("newday");
    return true;
}

function scavenge_uninstall(){
    return true;
}

function scavenge_dohook($hookname,$args){
	switch($hookname){
    case "newday":
        $terror_wraith=get_module_pref("terror_wraith");
		if ($terror_wraith == 1) {
			output("`7You are still shaking at the memory of dead eyes, staring at you accusingly!.");
			apply_buff('terror', array(
				"name"=>"`\$Terror",
				"rounds"=>15,
				"wearoff"=>"You stop shivering at shadows.",
				"atkmod"=>0.95,
				"defmod"=>0.95,
				"roundmsg"=>"You jump at shadows, and find it harder to fight!",
			));
			set_module_pref("terror_wraith",get_module_pref("terror_wraith")-1);
		} elseif ($terror_wraith == 2){
			output("`7You are still shaking at the memory of dead eyes, staring at you accusingly!.");
			apply_buff('terror', array(
				"name"=>"`\$Unnatural Terror",
				"rounds"=>20,
				"wearoff"=>"You stop shuddering.",
				"atkmod"=>0.90,
				"defmod"=>0.90,
				"roundmsg"=>"You find it hard to stop from running!",
			));
			set_module_pref("terror_wraith",get_module_pref("terror_wraith")-1);
		} elseif ($terror_wraith >= 2){
			output("`7You can barely hold your sword thinking of the vengance of the dead!");
			apply_buff('terror', array(
				"name"=>"`\$Unnatural Horror",
				"rounds"=>25,
				"wearoff"=>"You breathe a little easier.",
				"atkmod"=>0.75,
				"defmod"=>0.75,
				"roundmsg"=>"Your sword shakes in your hand, and you find it harder to fight!",
			));
			set_module_pref("terror_wraith",get_module_pref("terror_wraith")-2);
		}
        break;
	}
    return $args;
}

function scavenge_runevent($type) {
	global $session;
	$death = getsetting("deathoverlord", '`$Ramius');
	$from = "runmodule.php?module=scavenge&";
	if ($type == "graveyard") $from = "graveyard.php?";
	elseif ($type == "village") $from = "village.php?";
	$session['user']['specialinc'] = "module:scavenge";
	$op = httpget('op');
	switch ($type) {
	case "graveyard":
		if ($op=="" || $op=="search") {
			output("`\$Searching for a poor soul to torment as you stride from grave to grave, you catch sight of a pile of bodies stacked awkwardly in a pit. ");
			output("Your scavenging instincts are tingling, and you espy pouches full of the promise of `^gold`\$ or `%gems`\$.`n`n");
			output("However, you know full well that in the `7Graveyard`\$ such things are not always as they appear... fell creatures lurk around every corner.");
			addnav("Pile of Bodies");
			addnav("Search the Bodies", $from . "op=loot");
			addnav("Leave Well Alone", $from . "op=leavescavenge");
		} elseif ($op=="loot") {
			$rand = round(e_rand(1,17), 0);
			output("`7Ignoring the danger, you climb down into the pit and begin the gruesome task of freeing the dead of their belongings.`n`n");
			switch ($rand) {
			case 1:
			case 2:
				output("You look through the bodies and after some effort you find a small pouch of gems.`n`n");
				output("After cleaning off the entrails, you feel that was most worthwhile, and return to the Graveyard.`n`n");
				$fnord = round(e_rand(2,5), 0);
				output("`&You gain `%%s gems`&!",$fnord);
				$session['user']['gems']+=$fnord;
				debuglog("found $fnord gems from scavenging in the Graveyard");
				break;
			case 3:
			case 4:
				$fnord = round(e_rand(3,47), 0);
				output("Whilst you don't find anything except dirt in their pouches, a closer look at the bodies reveals a gold coin under their tongues. ");
				output("You take the time to salvage `^%s gold `7from this macabre source.`n`n",$fnord);
				output("You feel like %s`7 is pleased with this act!`n`n", $death);
				$session['user']['gold']+=$fnord;
				$session['user']['deathpower']+=10;
				debuglog("gained $fnord scavenging");
				break;
			case 5:
				$fnord = round(e_rand($session['user']['level']*10,$session['user']['level']*100), 0);
				output("In various blood-stained pouches you find `^%s gold `7and, tucked away in a boot, a `%gem`7! ",$fnord);
				output("Now to find a way to wash all the blood off....`n`n");
				$session['user']['gold']+=$fnord;
				$session['user']['gems']++;
				$session['user']['charm']--;
				debuglog("found $fnord gold and a gem by scavenging");
				break;
			case 6:
			case 7:
				output("After a while of searching through the dead bodies, you conclude that somebody has already completely looted them. ");
				output("Cursed good-for-nothing looters.`nExhausted, you make your way back to the Graveyard.`n");
				$session['user']['gravefights']--;
				break;
			case 8:
			case 9:
				output("As you hit the bottom of the pit, you get the strangest feeling of not being alone here. ");
				output("This is further confirmed when a zombie sinks his teeth into your arm.`n`n");
				output("After screaming for a bit you manage to shake it off and flee from the cursed pit, clutching your arm in pain.`n`n");
				$fnord = $session['user']['soulpoints'];
				$randfnord = round(e_rand($session['user']['soulpoints']*0.4,5), 0);
				if ($fnord >= $randfnord)
					$session['user']['soulpoints']-=$randfnord;
				elseif ($fnord < $randfnord)
					$session['user']['soulpoints']=1;
				$fnordgold = round(e_rand(2,30), 0);
				if ($fnordgold<=15) {
					output("It left %s `^gold `&teeth `7in your arm!`n",$fnordgold);
					output("You pry them out painfully, knowing you'll be able to melt them down later.");
					$session['user']['gold']+=$fnordgold;
					debuglog("found $fnordgold gold from being bitten");
				} else {
					output("It left %s `&teeth `7marks in your arm! That'll leave an ugly scar....",$fnordgold);
					$session['user']['charm']--;
				}
				break;
			case 10:
				output("As you start looking through the pouches of the dead, you feel a cold hand fasten around your wrist, then another around your throat.`n`n");
				output("Not all the dead here are as asleep as they seem... and they REALLY don't like grave robbers. Not one bit.`n`n");
				output("Talking of bits, you never manage to count just how many of them they tear you into before throwing the remains out of the pit....`n`n");
				output("`&You have been torn apart by vengeful zombies!`nYou may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",
						$session['user']['name']);
				break;
			case 11:
			case 12:
			case 13:
				output("As you jump down into the pit, you hear moans arising from all around you - this is a Zombie pit!`n`n");
				output("You'll have to do something or be torn into teeny-weenie pieces, or maybe worse!");
				addnav("Zombie Pit");
				addnav("Flee the Pit", $from . "op=running");
				addnav("Pretend to be a Zombie", $from . "op=disguise");
				break;
			case 14:
			case 15:
				output("As you jump down into the pit, you can hear a piteous moan coming from beneath the pile of bodies. ");
				output("Looking closer, you see that there is a lost soul underneath the bodies, but trapped by the press. ");
				output("You could free it, but %s`0 would not be pleased if he heard that you had helped a soul escape his realm.", $death);
				addnav("Lost Soul");
				addnav("Leave it to its Fate", $from . "op=leave");
				addnav("Free the soul", $from . "op=free");
				break;
			case 16:
			case 17:
				output("Roughly going through the possessions of the dead, you fail to notice the dust swirling around your feet. ");
				output("Suddenly you feel a great wind behind you, and you turn to see a wraith, wordless cursing you and reaching towards you. ");
				output("Gibbering in terror, you crawl backwards away from the frightful vision before you!`n`n");
				output("`&You got away with some `^gold`&!`n`n`7You can still see the dead's eyes upon you!");
				set_module_pref("terror_wraith",get_module_pref("terror_wraith")+1);
				set_module_pref("seen_wraith","1");
				$fnordgold = round(e_rand(30,120), 0);
				$session['user']['gold']+=$fnordgold;
				debuglog("found $fnordgold gold from the dead");
				break;
			}
		} elseif ($op=="leave") {
			output("Turning your back on the cries coming from the ground, you leave this repugnant place and stride off to attempt to struggle back to life. ");
			output("You can feel accusing eyes staring into your back!`n`n");
			output("`\$Your evil deed does not go unnoticed!`n`n");
			output("Your corruption spreads to your features!");
			$session['user']['charm']--;
			set_module_pref("terror_wraith",get_module_pref("terror_wraith")+2);
			set_module_pref("seen_wraith","1");
			$session['user']['soulpoints']+=5;
			$session['user']['specialinc'] = "";
			addnews("%s left an innocent soul to suffer in the Graveyard - this evil act will not go unpunished.",$session['user']['name']);
		} elseif ($op=="free") {
			$session['user']['specialinc'] = "";
			output("You climb carefully down into the pit and pull aside the rotting corpses to reveal a lost wraith, shivering in the unearthly cold. ");
			output("With a wan smile, it nods to you then drifts up and away from this cursed realm.`n`n");
			output("`&This good act is heard of through the lands!`n`n");
			output("%s `&is angry at your temerity!`n`n", $death);
			$session['user']['deathpower'] = round($session['user']['deathpower']*0.5);
			$terror_wraith=get_module_pref("terror_wraith");
			set_module_pref("seen_wraith","-1");
			if ($terror_wraith>0) {
				set_module_pref("terror_wraith",get_module_pref("terror_wraith")-1);
				output("`&You feel the memory of accusing eyes fade!");
			}
			addnews("`^%s`7 saved an innocent soul in the Graveyard from the clutches of %s`7.",$session['user']['name'], $death);
		} elseif ($op=="running") {
			$session['user']['specialinc'] = "";
			$rand_run=round(e_rand(1,8), 0);
			switch($rand_run) {
			case 1:
				output("As you start to flee the pit, you trip over a reaching arm and fall into the waiting embrace of a zombie. ");
				output("And by waiting embrace, we mean tearing claws and teeth.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 2:
				output("Stumbling backwards in terror from the zombie horde, you hit the side of the pit. ");
				output("Looking up, you see that it is impossible to climb up from here. ");
				output("Your struggles against the zombies are short, and punctuated by tearing sounds.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 3:
				output("As you crawl backwards, away from the vengeful dead, you find an old rusty sword embedded in one of the corpses beneath your hand. ");
				output("Seizing the weapon with joy, you set about the task of carving your way to freedom. ");
				output("At the first blow to a zombie, the blade shatters, leaving you within arms reach of a very angry zombie indeed. ");
				output("Your screams echo through the graveyard.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 4:
				output("Trusting in your nimble feet and sharp reflexes, you dive under the first zombie and roll to avoid the tearing claws. ");
				output("Dodging the attacks of the zombies deftly, you reach the side of the pit, and start to climb up the side, grabbing at the dead tree roots which protude from the wall of dirt. ");
				output("The dead, ROTTEN tree roots. ");
				output("As they disintegrate in your desperate hands, you fall backwards, into the waiting claws of the horde.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 5:
				output("Leaping up athletically, you crush the head of the first zombie beneath your booted foot and leap towards the side of the pit, jumping from zombie to zombie. ");
				output("As you reach the side however, your foot twists awkwardly on the uneven ground, and you feel the bones in your ankle shatter. ");
				output("Fortunately, you black out and are spared the experience of the teeth of the zombies eating you alive.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 6:
				output("Summoning all the authority you possess, you command the zombies to return to their rest and trouble you no more. ");
				output("They fall back for a moment but, realising your lack of authority over them, they leap forward once more. ");
				output("You don't even have a chance to start running....`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 7:
				output("Spying a gap in the rising dead, you flee through the walking corpses and quickly scramble up the side of the pit. ");
				output("Pausing for a moment to catch your breath, your foot is suddenly caught by a grey hand thrust from the earth. ");
				output("Try as you might, you cannot release its deathly grip and, slowly but surely, the others reach you. ");
				output("In their fury, they leave no part of you intact.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			case 8:
				output("Turning to flee, you feel the sharp claws tearing at your back and feet as you run. ");
				output("Knowing that you cannot escape by speed alone, you utter a quick prayer to some higher source to save you in this dread place. ");
				output("As you fall to the dirt and they start to eat your flesh, you find no response, and you are quickly finished.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				break;
			}
		} elseif ($op=="disguise") {
			$rand_soulpoints = round(e_rand(4,8), 0)/10;
			$level = $session['user']['level'];
			if ($session['user']['soulpoints']<=(($level * 5 + 50)*$rand_soulpoints)) {
				output("Quickly assuming a slumped posture and lurching walk, you shamble around the pit, murmuring \"Braiiiinssssss\" under your breath in an attempt to fit in. ");
				output("You look so beaten up, the zombies fall for it! ");
				output("Sidling out of the pit, you leg it once you get to a safe distance and rest for a moment, shocked but unharmed.");
				$session['user']['specialinc'] = "";
			} else {
				output("You quickly assume a slumped posture and start saying \"Braiiiinsssss\", but your healthy looks quickly betray you. ");
				output("Well, not healthy for long after they start tearing off your skin.`n`n");
				output("`&You have been torn apart by vengeful zombies!`n`n");
				output("You may not torment any more souls today!`n`n");
				$session['user']['soulpoints'] = 0;
				$session['user']['gravefights'] = 0;
				debuglog("was torn apart by zombies in the graveyard.");
				addnews("%s was torn apart by zombies in the graveyard.",$session['user']['name']);
				$session['user']['specialinc'] = "";
			}
		} elseif ($op=="leavescavenge") {
			output("`7Thinking that the dead are better left in peace, you hurry away to seek favour with %s`7 elsewhere.`0", $death);
			$session['user']['specialinc'] = "";
		}
		break;
	case "village":
		$seen_wraith=get_module_pref("seen_wraith");
		if (($seen_wraith!=0)&&($op=="")) {
			output("`\$Drifting out of a side alleyway, you spot a hooded figure approaching you slowly, almost drifting on the wind. ");
			output("You can see no face or features beneath the ragged cloth, and a sudden apprehension strikes you. ");
			output("Will you wait to see what the stranger wants?");
			addnav("Ghostly Figure");
			addnav("Approach the Ghostly Figure",$from."op=approach");
			addnav("Hastily Move Away",$from."op=goaway");
		} elseif ($op=="") {
			output("For a moment out of the corner of your eye you think you can see a ghostly figure, searching for someone, but a farmer passes in front of you, and in the next instant the figure has gone. ");
			output("A shiver runs down your spine as you wonder who it was looking for and hasten on.");
			$session['user']['specialinc'] = "";
		}
		if ($op=="goaway") {
			output("`&Far too busy with your business to deal with a tramp, you hasten on you way through the village. ");
			output("Not running at all, no. Just going fast to...... somewhere else.....");
			set_module_pref("seen_wraith","0");
			$session['user']['specialinc'] = "";
		} elseif ($op=="approach") {
			output("`7As the ghostly figure moves closer, a chill wind blows past you...");
			set_module_pref("seen_wraith","0");
			$terror_wraith=get_module_pref("terror_wraith");
			if ($seen_wraith==-1) {
				output("`^As the hood lifts you see the face of the shade you freed, but as a hale and hearty %s, alive and well. ",$session['user']['race']);
				output("He tells you of his fight for freedom and escape from the dread realm, bids you farewell and turns away back down the alleyway. ");
				output("You turn to say something more to him, but he has vanished.`n`n");
				output("`&You feel blessed!`n`n");
				output("This uplifting experience has shown you more about the world!");
				set_module_pref("terror_wraith","0");
				set_module_pref("seen_wraith","0");
				$session['user']['experience']*=1.2;
				apply_buff('blessing', array(
					"name"=>"`@Blessing",
					"rounds"=>15,
					"wearoff"=>"You feel forgotten.",
					"defmod"=>1.2,
					"roundmsg"=>"You feel watched over!",
					"survivenewday"=>1,
					"newdaymessage"=>"`n`7You feel watched over!.`n`n"
				));
				$session['user']['specialinc'] = "";
			} elseif ($terror_wraith>=2) {
				output("`n`n`7The wind blows back the hood of the figure to reveal a horrific visage - the face of the wraith you betrayed!");
				$rand_skills=round(e_rand(3,12), 0);
				if (is_module_active("specialtydarkarts") &&
						get_module_pref("skill", "specialtydarkarts") >= $rand_skills) {
					output("At the sight of the fell being your training instantly kicks in - you quickly chant an incantation to banish the creature back to the fell depths from whence it came.`n`n");
					output("`&You feel more experienced in the Dark Arts!");
					require_once("lib/increment_specialty.php");
					increment_specialty("`$");
					$session['user']['specialinc'] = "";
				} else {
					output("`\$Gibbering in terror you fall to your knees and beg for forgiveness, but the dead eyes close in on you until you can see nothing else. ");
					output("Later you are found by the guards, dead without a mark on your body.`n`n");
					output("`&You have died from fear!");
					set_module_pref("terror_wraith","0");
					$session['user']['hitpoints']=0;
					$session['user']['specialinc'] = "";
					addnews("%s was found curled up at the mouthway of an alley with no marks on him, dead.",$session['user']['name']);
					addnav("Daily News", "news.php");
				}
			} elseif ($seen_wraith==1) {
				$fnord_rand=round(e_rand(1,10), 0);
				switch($fnord_rand) {
				case 1:
				case 2:
					output("As the hood is blown back, you see a scarred, ancient face leering at you. ");
					output("In surprise, you fail to get out of the way as he stumbles past, knocking you out of his way as he grumbles under his breath. ");
					output("As you sort yourself out, you notice your `^gold`7 pouch is missing! ");
					output("You walk on, grumbling under your breath.");
					$session['user']['gold']=0;
					set_module_pref("seen_wraith","0");
					$session['user']['specialinc'] = "";
					break;
				case 3:
				case 4:
				case 5:
				case 6:
				case 7:
					output("The hood of the figure is blown back, revealing the rotting face of the dead! ");
					output("You fall away and scrabble backwards, barely managing to flee in time.`n`n");
					output("`&By the time you stop running, you are exhausted!");
					set_module_pref("seen_wraith","0");
					$session['user']['turns']--;
					$session['user']['specialinc'] = "";
					break;
				case 8:
				case 9:
					output("As the hood is blown back, you are relieved to find that underneath is merely an ancient warrior, scarred from old battles long past. ");
					output("However, your relief suddenly fades as his hand clamps onto your shoulder with a vice-like grip and he begins to relate the first of many tales of battles he was in... ");
					output("by the time you manage to get away, the sun is much lower in the sky.");
					$rand_fnord=round(e_rand(2,4), 0);
					$session['user']['turns']-=$rand_fnord;
					if ($session['user']['turns'] < 0)
						$session['user']['turns'] = 0;
					set_module_pref("seen_wraith","0");
					$session['user']['specialinc'] = "";
					break;
				case 10:
					output("The wind rustles the figure's robe and, looking down, you see no feet! ");
					output("The wraith reaches out a transparent hand towards you and suddenly flies through your body. ");
					output("You pass out from the shock, and when you awaken, you feel drained, physically and mentally.");
					$session['user']['experience']*=.95;
					set_module_pref("seen_wraith","0");
					$session['user']['specialinc'] = "";
					break;
				}
			}
		}
	}
}

function scavenge_run(){
}
?>

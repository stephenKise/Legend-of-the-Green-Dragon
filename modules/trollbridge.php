<?php

function trollbridge_getmoduleinfo(){
	$info = array(
		"name"=>"Troll Bridge",
		"version"=>"1.0",
		"author"=>"`%Sneakabout`0",
		"category"=>"Travel Specials",
		"download"=>"core_module",
		"settings"=>array(
			"liketrolls"=>"Does the Troll like other trolls?,bool|1",
			"hateelves"=>"Does the Troll attack elves on sight?,bool|1",
		),
		"requires"=>array(
			"racetroll"=>"1.0|by Eric Stevens",
		),
	);
	return $info;
}

function trollbridge_install(){
	module_addeventhook("travel", "return 40;");
	return true;
}

function trollbridge_uninstall(){
	return true;
}

function trollbridge_dohook($hookname,$args){
	return $args;
}

function trollbridge_runevent($type,$link){
	global $session;
	$from = $link;
	$city = httpget("city");
	$session['user']['specialinc'] = "module:trollbridge";

	$op = httpget('op');
	$fight = httpget('fight');
	switch($op){
	case "cross":
		if ($session['user']['location'] ==
				get_module_setting("villagename","racetroll") ||
				$city==get_module_setting("villagename","racetroll")) {
			output("`2When you are about halfway across the bridge, a gigantic fearsome troll jumps out from underneath the bridge, blocking your path!`n`n");
			if ($session['user']['race']=="Troll" &&
					get_module_setting("liketrolls")) {
				output("It looks a little sheepish when it notices that you're a troll too and you have a short, awkward conversation about good bridges, swamps and killing goats before you run out of small talk.");
				output("After a short silence it wishes you well in your travels and you go on your way.");
				$session['user']['specialinc'] = "";
			} elseif ($session['user']['race']=="Elf" &&
					get_module_setting("hateelves")) {
				output("Seeing that you are a tasty, succulent elven delicacy the beast dispenses with any chatter and leaps to the attack, salivating as it tries to bite your head off!");
				addnav("Fight the Troll", $from."fight=trollfight");
			} else {
				output("The troll licks its lips as it advances on you, and says, \"");
				output("`6Huh huh huh.... I'm gonna eat you right up, tasty snack. Anythin' ta say before I chomp you right up?`2\"");
				output("What will you say?");
				addnav("Don't Eat Me...");
				addnav("...I'm Too Scrawny!", $from."op=thin");
				addnav("...I'm Too Beautiful!", $from."op=charm");
				addnav("...I Taste Bad!", $from."op=taste");
				addnav("Look, a Goat!", $from."op=goat");
				addnav("Other");
				addnav("Jump Off the Bridge", $from."op=jump");
				addnav("Fight the Troll", $from."fight=trollfight");
			}
		} else {
			output("`2The bridge creaks dangerously as you make your way across, but it stays firm, and you are able to make your way onwards.");
			$session['user']['specialinc'] = "";
		}
		break;
	case "thin":
		$hp=($session['user']['maxhitpoints']-($session['user']['level']*10));
		if ($hp<e_rand(10,100)) {
			output("`2The troll laughs a little, then starts looking worried when it looks at your more closely.");
			output("\"`6Well... you're right! Yer naught but a sack of bones! You'd be crunchy, but I'm in the mind fer meat. Besides, the bones would stick in my teeth. Again.`2\"");
			output("He waves you over the bridge, slightly disappointed, and shouts after you to eat some more before you cross his bridge again.");
			$session['user']['specialinc'] = "";
		} else {
			output("`2The troll laughs a little before readying what look disturbingly like bone cutlery.");
			output("\"`6You've got plenty o' meat on those bones... you'll be a tasty snack indeed, something to get my teeth into!`2\"");
			output("He leaps towards you, knife and fork drawn - you must attack!");
			addnav("Fight the Troll", $from."fight=trollfight");
		}
		break;
	case "charm":
		output("`2The troll looks at you closely, sniffing and looking around suspiciously.");
		if ($session['user']['charm']<e_rand(1,2)) {
			output("\"`6You're right! A fine creature such as you must be preserved at all cost, my most sincere apologies for trying to.. err.. eat you. Go on your way with my compliments.`2\"");
			output("`n`nUnsure what just happened, you make your way onwards with a smiling troll behind you, pretty sure that it wasn't a compliment.");
			$session['user']['specialinc'] = "";
		} else {
			output("\"`6Ugh! I mean, how could you even imagine you're worth saving? You have no slime at ALL on you, you smell disgustingly fresh and don't even get me started on your nose.... no, you nearly put me off my meal. Nearly.\"");
			output("He leaps towards you, knife and fork drawn - you must attack!");
			addnav("Fight the Troll", $from."fight=trollfight");
		}
		break;
	case "taste":
		output("`2The troll looks at you closely, sniffing you up and down a few times.");
		$taste=e_rand("1,5");
		if ($session['user']['race']=="Elf") $taste++;
		if ($session['user']['race']=="Dwarf" ||
				$session['user']['race']=="Troll") $taste--;
		if ($taste>3) {
			output("\"`6You've got plenty of flavour locked inside, no question about that... now be quiet while I enjoy the taste sensation that is you!`2\"");
			output("He leaps towards you, knife and fork drawn - you must attack!");
			addnav("Fight the Troll", $from."fight=trollfight");
		} else {
			output("\"`6You're right! You'd be bitter and disgusting, I'd never get the taste out of my mouth. Go on, get away with you. Darn inedible things.....`2\"");
			output("You hurry on your way, thankful he wasn't hungry enough to fancy you.");
			$session['user']['specialinc'] = "";
		}
		break;
	case "goat":
		if (e_rand(0,2) == 0) {
			output("`2The troll leaps to the side in an extraordinary acrobatic feat and clings to a tree while it shouts angrily, eyes darting around for the beast!");
			output("You take this chance to get across the bridge, sure you'll be long gone before he realises what happened.");
			$session['user']['specialinc'] = "";
		} else {
			output("`2He glances around in fear once, but seeing the absence of a goat turns back to you, a dangerous gleam in his eyes.");
			output("\"`6You know... I really HATE people who try that one..... I'm gonna eat you, FEET FIRST!\"`2");
			output("He leaps towards you, knife and fork drawn - you must attack!");
			addnav("Fight the Troll", $from."fight=trollfight");
		}
		break;
	case "jump":
		output("`2Taking your chances with the fall rather than a hungry troll, you leap off the bridge into the gorge!");
		output("As you bounce off the sides, bones breaking with each impact, you realise you should have chosen a wiser path.");
		if (e_rand(1,7)>=6) {
			output("All of your gold falls out of your pockets on the way down.");
			output("Your broken body floats downstream.");
			output("`n`n`%You have died!");
			output("Your soul drifts to the shades.");
			$session['user']['specialinc'] = "";
			$session['user']['experience']*=0.9;
			debuglog("lost " . $session['user']['gold'] . " jumping off the trollbridge to their death.");
			$session['user']['gold']=0;
			$session['user']['hitpoints'] = 0;
			$session['user']['alive'] = false;
			addnews("%s`2's body was found broken by a river!`0",$session['user']['name']);
			addnav("Return to the News","news.php");
		} else {
			output("As your broken body floats downstream, some fairies use you as a raft - the dust heals some of your wounds!");
			output("By the time they get bored of playing, you are strong enough to swim to the bank and find a new path to your destination.");
			$session['user']['hitpoints']*=0.5;
			$session['user']['specialinc'] = "";
		}
		break;
	case "return":
		output("`2Worried about a bridge, you decide to go back. After all, that moss looks dangerous!");
		$session['user']['specialinc'] = "";
		require_once("lib/villagenav.php");
		villagenav();
		break;
	default:
		if (!$fight) {
			output("`2Walking along a particularly overgrown portion of the trail, you emerge onto one side of a small gorge blocking your path.");
			output("A mossy wooden bridge crosses the gap.");
			output("What will you do?");
			addnav("Cross the Bridge", $from."op=cross");
			addnav(array("Return to %s",$session['user']['location']), $from."op=return");
		}
		break;
	}
	switch($fight){
	case "trollfight":
		$badguy = array(
			"creaturename"=>translate_inline("Gigantic Troll"),
			"creaturelevel"=>$session['user']['level']+2,
			"creatureweapon"=>translate_inline("Knife and Fork"),
			"creatureattack"=>round($session['user']['attack']*1.2, 0),
			"creaturedefense"=>round($session['user']['defense']*0.5, 0),
			"creaturehealth"=>round($session['user']['maxhitpoints']*4, 0),
			"diddamage"=>0,
			"type"=>"travel"
		);
		$session['user']['badguy']=createstring($badguy);
		$battle=true;
		// Drop through
	case "trollfighting":
		require_once("lib/fightnav.php");
		include("battle.php");
		if ($victory ||
				$badguy['creaturehealth'] <
				($session['user']['maxhitpoints']*2)) {
			output("`2With a great push, the injured troll falls back off the edge of the bridge!");
			output("You can hear him cursing as he hits the bottom, and you leave the scene quickly, knowing he will get back up soon enough.");
			$expgain=round($session['user']['experience']*(e_rand(1,3)*0.01));
			$session['user']['experience']+=$expgain;
			output("`n`n`&You gain %s experience from this fight!",$expgain);
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`&Looking around quickly, you find some grass and moss to stop the flow of blood from your wounds, saving yourself from bleeding to death!.");
				$session['user']['hitpoints'] = 1;
			}
			$session['user']['specialinc'] = "";
		} elseif ($defeat) {
			output("`2Your vision blacks out as a huge fork is plunged into your body.");
			output("Your dying thought is one of thanks that you won't be conscious for the main meal.`n`n");
			output("`%You have died! You lose 10% of your experience, and your gold is stolen by the troll!");
			output("Your soul drifts to the shades.");
			debuglog("was killed by a troll and lost ".$session['user']['gold']." gold.");
			$session['user']['gold']=0;
			$session['user']['experience']*=0.9;
			$session['user']['alive'] = false;
			addnews("`2Some chewed-on bones, all that remains of %s`2, were found in a river!",$session['user']['name']);
			$session['user']['specialinc'] = "";
			addnav("Return to the News","news.php");
		} else {
			fightnav(true,true,$from."fight=trollfighting");
		}
		break;
	}
}

function trollbridge_run(){
}
?>

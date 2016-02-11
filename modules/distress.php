<?php
//addnews ready
// mail ready
// translator ready
function distress_getmoduleinfo(){
	$info = array(
		"name"=>"Damsel In Distress",
		"version"=>"1.1",
		"author"=>"Joe Naylor and Matt Clift",
		"category"=>"Forest Specials",
		"download"=>"core_module",
	);
	return $info;
}

function distress_install(){
	module_addeventhook("forest", "return 100;");
	return true;
}

function distress_uninstall(){
	return true;
}

function distress_dohook($hookname,$args){
	return $args;
}

function distress_runevent($type)
{
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:distress";

	$op = httpget('op');
	if ($op == "" || $op == "search") {
		output("`n`3While searching through the forest, you find a man lying face down in the dirt.");
		output("The arrow in his back, the pool of blood, and the lack of movement are good indications this man is dead.`n`n");
		output("`3While searching his body, you find a crumpled piece of paper, tightly clenched within his fist.");
		output("You carefully free the paper and realize it is a note, hastily written by the look of it.");
		output("The note reads:`n`n");
		output("`3\"`7Help! I have been imprisoned by my horrid old Uncle. He plans to force me to marry. Please save me! I am being held at ...`3\"`n`n");
		output("`3The rest of the note is either too bloody or too badly damaged to read.`n`n");
		if ($session['user']['sex']) {
			output("`3Outraged you cry, \"`7I must save him!`3\"");
		} else {
			output("`3Outraged you cry, \"`7I must save her!`3\"");
		}
		output("But where will you go?`n`n");

		addnav("Go to");
		addnav("Wyvern Keep", $from . "op=1");
		addnav("Castle Slaag", $from . "op=2");
		addnav("Draco's Dungeon", $from . "op=3");
		addnav("Ignore it", $from . "op=no");
	} elseif ($op == 'no') {
		output("`3You crumple the note up and toss it into the trees.");
		if ($session['user']['sex']) {
			output("You're not afraid, he's just not worth your time.");
		} else {
			output("You're not afraid, she's just not worth your time.");
		}
		output("Nope, not afraid at all, no way.");
		output("You turn your back on the poor distressed %s's pitiful cry for help, and set off through the trees to find something a little less danger- ...er, a little more challenging.`n`n", translate_inline($session['user']['sex']?"man":"maiden"));
		$session['user']['specialinc']="";
	} else {
		switch ($op) {
		case 1: $loc = "Wyvern Keep"; break;
		case 2: $loc = "Castle Slaag"; break;
		case 3: $loc = "Draco's Dungeon"; break;
		}
		$loc = translate_inline($loc);
		output("`n`3You storm through the doors of `#%s`3 slaying the guards and anybody else who gets in your way.", $loc);

		switch (e_rand(1, 10)) {
		case 1:
		case 2:
		case 3:
		case 4:
			output("`3Finally you open what looks like a likely door and spy a well furnished chamber.`n`n");
			output("The chamber holds a young, %s, and grateful occupant.`n`n", translate_inline($session['user']['sex']?"handsome":"beautiful"));
			if ($session['user']['sex']) {
				output("\"`#Oh, you came!`3\" he says.");
			} else {
				output("\"`#Oh, you came!`3\" she beams.");
			}
			output("\"`#%s, how can I ever thank you?`3\"`n`n",
					translate_inline($session['user']['sex']?"Heroine":"Hero"));
			output("After a few hours in each other's arms, you leave the %s side and go on your way.", translate_inline($session['user']['sex']?"prince's":"princess'"));
			if ($session['user']['sex']) {
				output("He insists that you take a small token of his appreciation.");
			} else {
				output("She insists that you take a small token of her appreciation.");
			}
			output_notl("`n`n");
			switch (e_rand(1, 5)) {
			case 1:
				output("You are given a small leather bag.`n`n");
				$reward = e_rand(1, 2);
				output("`&You gain `%%s %s`&!", $reward, translate_inline($reward == 1?"gem":"gems"));
				$session['user']['gems'] += $reward;
				debuglog("gained $reward gems rescuing a damsel in distress");
				break;
			case 2:
				output("You are given a small leather bag.`n`n");
				$reward = e_rand(1, $session['user']['level']*30);
				output("`&You gain `^%s gold`&!", $reward);
				$session['user']['gold'] += $reward;
				debuglog("gained $reward gold rescuing a damsel in distress");
				break;
			case 3:
				if ($session['user']['sex']) {
					output("He shows you things you never even dreamed of.`n`n");
				} else {
					output("She shows you things you never even dreamed of.`n`n");
				}
				$newexp = $session['user']['experience'] * 1.1;
				$gain = round($newexp - $session['user']['experience'], 0);
				output("`^You gain %s experience!", $gain);
				$session['user']['experience'] += $gain;
				break;
			case 4:
				if ($session['user']['sex']) {
					output("He taught you how to be a real woman.`n`n");
				} else {
					output("She taught you how to be a real man.`n`n");
				}
				output("`^You gain two charm points!");
				$session['user']['charm'] += 2;
				break;
			case 5:
				output("You are given a carriage ride back to the forest, alone...`n`n");
				output("`^You gain a forest fight, and are completely healed!");
				$session['user']['turns'] ++;
				if ($session['user']['hitpoints'] <
						$session['user']['maxhitpoints'])
					$session['user']['hitpoints'] =
						$session['user']['maxhitpoints'];
				break;
			}
			break;
		case 5:
			output("`3Finally you open what looks like a likely door and spy a well furnished chamber.`n`n");
			output("The chamber holds a large chest, muffled cries for help come from inside.");
			output("You throw the chest open and strike a heroic pose, then you see the chest's occupant.");
			output("Out from the chest leaps a monstrous, and lonely, %s!!", translate_inline($session['user']['sex']?"troll":"trolless"));
			if($session['user']['sex']) {
				output("After a few hours of... excitement, he lets you go on your way.`n`n");
			} else {
				output("After a few hours of... excitement, she lets you go on your way.`n`n");
			}
			if ($session['user']['race'] == "Troll") {
				output("You'd almost forgotten how potent your race was!`n");
				output("`%You gain 1 forest fights!`n");
				output("`%You gain a charm point!`n");
				$session['user']['turns']+=1;
				$session['user']['charm']++;
			} else {
				output("You feel more than a little dirty.`n`n");
				output("`%You lose a forest fight!`n");
				output("`%You lose a charm point!`n");
				if ($session['user']['turns'] > 0)
					$session['user']['turns']--;
				if ($session['user']['charm'] > 0)
					$session['user']['charm']--;
			}
			break;
		case 6:
			output("`3Finally you open what looks like a likely door and spy a well furnished chamber.`n`n");
			output("The chamber holds a wrinkled old %s!", translate_inline($session['user']['sex']?"man":"hag"));
			output("You gasp in horror at the hideous thing before you, and run screaming from the room.");
			output("Somehow, you think something rubbed off on you.`n`n");
			output("`%You lose a charm point!`n");
			if ($session['user']['charm'] > 0) $session['user']['charm']--;
			break;
		case 7:
			output("`3Finally you open what looks like a likely door and spy a well furnished chamber.`n`n");
			output("You dash into the room, and sitting at the window is a ridiculous-looking effeminate fop.");
			output("\"`5Oh, you came!`3\" he cries, jumping to his feet.");
			output("As he starts toward you, he trips over his bedpan and gets tangled in his clothes.");
			output("You take this opportunity to slip away as quickly and quietly as possible.");
			output("Luckily, nothing was injured but your pride.`n`n");
			break;
		case 8:
			output("`3A fierce fight ensues, and you put forth a valiant effort!");
			output("Unfortunately you are hopelessly outnumbered, you try to run, but soon fall beneath the blades of your enemies. `n`n");
			output("`%You have died!`n`n");
			output("`3The life lesson learned here balances any experience you would have lost.`n");
			output("You may continue playing again tomorrow.");
			$session['user']['alive']=false;
			$session['user']['hitpoints']=0;
			addnav("Daily News","news.php");
			addnews("`%%s`3 was slain attempting to rescue a %s from `#%s`3.", $session['user']['name'], translate_inline($session['user']['sex']?"prince":"princess"), $loc);
			break;
		case 9:
			output("`3A fierce fight ensues, and you put forth a valiant effort!");
			output("Unfortunately you are hopelessly outnumbered, finally you see your chance and break free.");
			output("The last thing the denizens of `#%s`3 see is your backside, fleeing into the forest.`n`n", $loc);
			output("`%You lose a forest fight!`n");
			output("`%You lose most of your hitpoints!`n");
			if ($session['user']['turns']>0)
				$session['user']['turns']--;
			if ($session['user']['hitpoints'] >
					($session['user']['hitpoints']*.1))
				$session['user']['hitpoints'] =
					round($session['user']['hitpoints']*.1,0);
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints']=1;
			break;
		case 10:
			output("`3Finally you open what looks like a likely door and spy a well furnished chamber.`n`n");
			output("You dash inside, and find a surprised nobleman and his wife, just sitting down to dinner.`n`n");
			output("\"`^What is the meaning of this?`3\" he demands.");
			output("You try to explain how you ended up in the wrong place, but he doesn't seem to pay any attention.");
			output("The authorities are called, and you are fined for damages.`n`n");
			output("`%You lose all the gold you were carrying!`n");
			debuglog("lost {$session['user']['gold']} gold while rescuing a damsel in distress");
			$session['user']['gold']=0;
			break;
		}
		$session['user']['specialinc']="";
	}
}

function distress_run(){
}
?>

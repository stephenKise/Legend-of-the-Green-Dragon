<?php
//addnews ready
// mail ready
// translator ready
function glowingstream_getmoduleinfo(){
	$info = array(
		"name"=>"Glowing Stream",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
	);
	return $info;
}
debug("glowingstream");
function glowingstream_install(){
	module_addeventhook("forest", "return 100;");
	module_addeventhook("travel", "return 100;");
	return true;
}

function glowingstream_uninstall(){
	return true;
}

function glowingstream_dohook($hookname,$args){
	return $args;
}

function glowingstream_runevent($type,$link)
{
	global $session;
	// We only care about the forest here currently.
	$from = $link;
	$session['user']['specialinc']="module:glowingstream";

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`#You discover a small stream of faintly glowing water that babbles over round pure white stones.");
		output("You can sense a magical power in the water.");
		output("Drinking this water may yield untold powers, or it may result in crippling disability.");
		output("Do you wish to take a drink?");
		addnav("Drink", $from . "op=drink");
		addnav("Don't Drink", $from . "op=nodrink");
	}elseif ($op=="drink"){
		$session['user']['specialinc']="";
		$rand = e_rand(1,10);
		output("`#Knowing that the water could yield deadly results, you decide to take your chances.");
		output("Kneeling down at the edge of the stream, you take a long hard draught from the cold stream.");
		output("You feel a warmth growing out from your chest...`n");
		switch ($rand){
		case 1:
			output("`iIt is followed by a dreadful clammy cold`i.");
			output("You stagger and claw at your breast as you feel what you imagine to be the hand of the reaper placing its unbreakable grip on your heart.`n`n");
			output("You collapse by the edge of the stream, only just now noticing that the stones you observed before were actually the bleached skulls of other adventurers as unfortunate as you.`n`n");
			output("Darkness creeps in around the edges of your vision as you lay staring up through the trees.");
			output("Your breath comes shallower and less and less frequently as warm sunshine splashes on your face, in sharp contrast to the void taking residence in your heart.`n`n");
			output("`^You have died due to the foul power of the stream.`n");
			output("As the woodland creatures know the danger of this place, none are here to scavenge from your corpse, thus you may keep your gold.`n");
			output("The life lesson learned here balances any experience you would have lost.`n");
			output("You may continue playing again tomorrow.");
			$session['user']['alive']=false;
			$session['user']['hitpoints']=0;
			addnav("Daily News","news.php");
			addnews("%s encountered strange powers in the forest, and was not seen again.",$session['user']['name']);
			break;
		case 2:
			output("`iIt is followed by a dreadful clammy cold`i.");
			output("You stagger and claw at your breast as you feel what you imagine to be the hand of the reaper placing its unbreakable grip on your heart.`n`n");
			output("You collapse by the edge of the stream, only just now noticing that the stones you observed before were actually the bleached skulls of other adventurers as unfortunate as you.`n`n");
			output("Darkness creeps in around the edges of your vision as you lay staring up through the trees.");
			output("Your breath comes shallower and less and less frequently as warm sunshine splashes on your face, in sharp contrast to the void taking residence in your heart.`n`n");
			output("As you exhale your last breath, you distantly hear a tiny giggle.");
			output("You find the strength to open your eyes, and find yourself staring at a tiny fairy who, flying just above your face is inadvertently sprinkling its fairy dust all over you, granting you the power to crawl once again to your feet.");
			output("The lurch to your feet startles the tiny creature, and before you have a chance to thank it, it flits off.`n`n");
			output("`^You narrowly avoid death, you lose a forest fight, and most of your hitpoints.");
			if ($session['user']['turns']>0) $session['user']['turns']--;
			if ($session['user']['hitpoints'] >
					($session['user']['maxhitpoints']*.1))
				$session['user']['hitpoints'] =
					round($session['user']['maxhitpoints']*.1,0);
			if ($session['user']['hitpoints'] < 1)
				$session['user']['hitpoints'] = 1;
			break;
		case 3:
			output("You feel INVIGORATED!`n`n");
			output("`^Your hitpoints have been restored to full, and you feel the energy for another turn in the forest.");
			if ($session['user']['hitpoints'] <
					$session['user']['maxhitpoints'])
				$session['user']['hitpoints'] =
					$session['user']['maxhitpoints'];
			$session['user']['turns']++;
			break;
		case 4:
			output("You feel PERCEPTIVE!`n`n");
			output("You notice something glittering under one of the pebbles that line the stream.`n`n");
			output("`^You find a `%GEM`^!");
			$session['user']['gems']++;
			debuglog("found 1 gem by the stream");
			break;
		case 5:
		case 6:
		case 7:
			output("You feel ENERGETIC!`n`n");
			output("`^You receive an extra forest fight!");
			$session['user']['turns']++;
			break;
		default:
			output("You feel HEALTHY!`n`n");
			output("`^Your hitpoints have been restored to full.");
			if ($session['user']['hitpoints'] <
					$session['user']['maxhitpoints'])
				$session['user']['hitpoints'] =
					$session['user']['maxhitpoints'];
		}
		output("`0");
	}else{
		$session['user']['specialinc']="";
		output("`#Fearing the dreadful power in the water, you decide to let it be, and return to the forest.`0");
	}
}

function glowingstream_run(){
}
?>

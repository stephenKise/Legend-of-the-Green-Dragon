<?php
//addnews ready
// mail ready
// translator ready
function stonehenge_getmoduleinfo(){
	$info = array(
		"name"=>"Stonehenge",
		"version"=>"1.1",
		"author"=>"Colin Harvie<br>Updates by JT Traub",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Stonehenge Forest Event Settings,title",
			"maxhp"=>"How many hitpoints can Stonehenge give?,range,1,5,1|3",
			"carrydk"=>"Do max hitpoints gained carry across DKs?,bool|1",
		),
		"prefs"=>array(
			"Fairy Forest Event User Preferences,title",
			"extrahps"=>"How many extra hitpoints has the user gained?,int",
		),
	);
	return $info;
}

function stonehenge_install(){
	module_addeventhook("forest", "return 100;");
	module_addhook("hprecalc");
	return true;
}

function stonehenge_uninstall(){
	return true;
}

function stonehenge_dohook($hookname,$args){
	switch($hookname){
	case "hprecalc":
		$args['total'] -= get_module_pref("extrahps");
		if (!get_module_setting("carrydk")) {
			$args['extra'] -= get_module_pref("extrahps");
			set_module_pref("extrahps", 0);
		}
		break;
	}
	return $args;
}

function stonehenge_runevent($type)
{
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:stonehenge";

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`#You wander through the forest aimlessly, looking for something to battle.");
		output("Suddenly the forest opens up into a small clearing.");
		output("In the center, you can see a circle of stones.");
		output("You have found the legendary Stonehenge!");
		output("You've heard the town-folk talk about this mythical place, but you never believed that it actually existed.");
		output("They say that the circle has great powers, though they're unpredictable.");
		output("What will you do?");
		addnav("Go into Stonehenge","forest.php?op=stonehenge");
		addnav("Leave it alone","forest.php?op=leavestonehenge");
	}elseif ($op=="stonehenge"){
		$session['user']['specialinc']="";
		$rand = e_rand(1,22);
		output("`#Knowing that the powers of the stones are unreliable, you decide to take your chances.");
		output("You walk into the center of the everlasting stones, ready to witness the awesome powers of Stonehenge.");
		output("As you step into the center, the sky turns to a black, starry night, and you notice the ground on which you stand is glowing with a faint purple light, almost as if the ground itself were turning to mist.");
		output("You start to feel a tingling which envelops your whole body.");
		output("Suddenly a bright, intense light envelops the circle, and you with it.");
		switch ($rand){
		case 1:
		case 2:
			output("When the light clears, you are no longer in Stonehenge.`n`n");
			output("Everywhere around you are the souls of those who have fallen in battle, in old age, and in grievous accidents.");
			output("Each bears telltale signs of the means by which they met their end.");
			output("You realize with increasing despair that the circle of stones has transported you to the land of the dead!`n`n");
			output("`^You have been sent to the underworld because of your foolhardy choice.`n");
			output("Since you have been physically transported to the underworld, you still have your gold.`n");
			output("You lose 5% of your experience.`n");
			output("You may continue playing again tomorrow.");
			$session['user']['alive']=false;
			$session['user']['hitpoints']=0;
			$session['user']['experience']*=0.95;
			addnav("Daily News","news.php");
			addnews("%s has been gone for a while, and those who have looked for " . ($session['user']['sex'] ? "her" : "him") . " do not come back.",$session['user']['name']);
			break;
		case 3:
			output("When the light clears, there is the body of a foolhardy traveller that decided to chance the powers of Stonehenge.`n`n");
			output("`^You spirit has been ripped from your body!`n");
			output("Since your body lies in Stonehenge, you lose all your gold.`n");
			output("You lose 10% of your experience.`n");
			output("You may continue playing again tomorrow.");
			$session['user']['alive']=false;
			$session['user']['hitpoints']=0;
			$session['user']['experience']*=0.9;
			$gold = $session['user']['gold'];
			$session['user']['gold'] = 0;
			addnav("Daily News","news.php");
			addnews("The body of %s was found lying in an empty clearing.",$session['user']['name']);
			debuglog("lost $gold dying at Stonehenge");
			break;
		case 4:
		case 5:
		case 6:
			output("When the light clears, you feel a searing energy ripping through your body.");
			output("Your brain is on fire with the experiences of a thousand individuals.");
			output("When the awful pain stops, you notice that you have retained some of the experience of those whose lives you witnessed.`n`n");
			$reward = round($session['user']['experience'] * 0.1, 0);
			output("`^You have gained `7%s`^ experience!", $reward);
			$session['user']['experience'] += $reward;
			break;
		case 7:
		case 8:
		case 9:
		case 10:
			$reward = round(e_rand(1, 4), 0);
			if ($reward == 4) $rewardn = "FOUR gems";
			else if ($reward == 3) $rewardn = "THREE gems";
			else if ($reward == 2) $rewardn = "TWO gems";
			else if ($reward == 1) $rewardn = "ONE gem";
			output("When the light clears, you notice that there %s `%%s`# lying at your feet!`n`n",
					translate_inline($reward==1?"is":"are"),
					translate_inline($rewardn));
			$session['user']['gems']+=$reward;
			debuglog("found $reward gems from Stonehenge");
			break;
		case 11:
		case 12:
		case 13:
			output("When the light clears, you feel much more confident about yourself.`n`n");
			output("`^You gain four charm!");
			$session['user']['charm'] += 4;
			break;
		case 14:
		case 15:
		case 16:
		case 17:
		case 18:
			// was displaying a message about restoring HP when they were already full
			if ($session['user']['hitpoints'] < $session['user']['maxhitpoints'] ) {
				output("When the light clears, you find that you feel extremely healthy.`n`n");
				output("`^Your hitpoints have been restored to full.");
				if ($session['user']['hitpoints']<$session['user']['maxhitpoints'])
					$session['user']['hitpoints']=$session['user']['maxhitpoints'];
			} else {
				// their hitpoints were already maxed
				output("When the light clears, an icy wind brushes your face, and tendrils of fear snake into your skin.`n`n");
				output("After a moment you realize you are unharmed, and you thank your good fortune at being spared today.`n`n");
			}
			break;
		case 19:
		case 20:
			output("When the light clears, you feel your stamina skyrocket!`n`n");
			$reward = e_rand(1, get_module_setting("maxhp"));

			$hptype = "permanently";
			if (!get_module_setting("carrydk") ||
					(is_module_active("globalhp") &&
					 !get_module_setting("carrydk", "globalhp")))
				$hptype = "temporarily";
			$hptype = translate_inline($hptype);

			output("Your maximum hitpoints have been `b%s`b increased by %s!",
					$hptype, $reward);

			$session['user']['maxhitpoints'] += $reward;
			$session['user']['hitpoints'] += $reward;
			set_module_pref("extrahps", get_module_pref("extrahps")+$reward);
			break;
		case 21:
		case 22:
			$prev = $session['user']['turns'];
			if ($prev >= 5) $session['user']['turns']-=5;
			else if ($prev < 5) $session['user']['turns']=0;
			$current = $session['user']['turns'];
			$lost = $prev - $current;

			output("When the light clears, the day has passed by.");
			output("It seems that Stonehenge has frozen you in time for most of the day.`n`n");
			output("`^As a result, you lose `\$%s`^ forest fights!", $lost);
			break;
		}
		output("`0");
	}else{
		$session['user']['specialinc']="";
		output("`#Fearing the awesome power of Stonehenge, you decide to let it be, and return to the forest.`0");
	}
}

function stonehenge_run(){
}
?>

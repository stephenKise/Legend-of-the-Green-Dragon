<?php
/*Five Sixes tavern game
  version 1.7
  - Added periods at the end of News announcements
  version 1.6
  - Fixed newday reset
  version 1.5
  - Added win function for rolling 3 sixes
  - Included winner list for 4 and 3 sixes on the entry page
 */
function game_fivesix_getmoduleinfo(){
	$info = array(
		"name"=>"Five Sixes Dice Game",
		"author"=>"`4Talisman",
		"version"=>"1.7",
		"category"=>"Darkhorse Game",
		"download"=>"core_module",
		"settings"=>array(
			"Five Sixes Dice Game,title",
			"cost"=>"Cost to play,int|5",
			"dailyuses"=>"Plays per  day (0=unlimited),int|10",
			"jackpot"=>"Gold in the pot,int|100",
			"maxjackpot"=>"Maximum Payout,int|5000",
			"lastwin5"=>"Last jackpot winner|Nobody...yet",
			"lastpot5"=>"Last Jackpot Won,int|0",
			"lastwin4"=>"Last winner of 4 sixes|Nobody...yet",
			"lastpot4"=>"Last Jackpot Won,int|0",
			"lastwin3"=>"Last winner of 3 sixes|Nobody...yet",
			"lastpot3"=>"Last Jackpot Won,int|0",
		),
		"prefs"=>array(
			"Five Sixes Dice, title",
			"playstoday"=>"Times played today,int|0",
		)
	);
	return $info;
}

function game_fivesix_install(){
	global $session;
	module_addhook("darkhorsegame");
	module_addhook("newday");
	return true;
}

function game_fivesix_uninstall(){
	return true;
}

function game_fivesix_dohook($hookname, $args){

	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("playstoday",0);
		break;

	case "darkhorsegame":
		$ret = urlencode($args['return']);
		addnav("Play Sixes Dice Game",
				"runmodule.php?module=game_fivesix&ret=$ret&what=play");
	}
	return $args;
}

function game_fivesix_run(){
	global $session;
	$ret = urlencode(httpget("ret"));
	page_header("A Game of Dice");

	$prize=get_module_setting("jackpot");
	$cost=get_module_setting("cost");

	$what = httpget('what');
	if ($what=="play"){
		output("`n`@So you'd like to try your hand to rolling five sixes, would you?`n");
		output("The game is quite simple, really - you roll five dice.");
		output("If all five display sixes, you win the jackpot.`n`n");
		output("`^It only costs you %s gold to play, and the current jackpot is %s gold pieces.`n`n", $cost, $prize);

		$lastpot5=get_module_setting("lastpot5");
		if ($lastpot5>=1){
			$lastwin5=get_module_setting("lastwin5");
			output("`@The last jackpot, worth %s gold was won by %s.`n`n",$lastpot5,$lastwin5);
		}else{
			output("`@The jackpot has never been won - you could be the first!`n`n");
		}
		$lastpot4=get_module_setting("lastpot4");
		if ($lastpot4>=1) {
			output("%s`@, in a peerless display, won `^%s `@gold for rolling 4 sixes.`n`n", get_module_setting("lastwin4"), get_module_setting("lastpot4"));
		}
		$lastpot3=get_module_setting("lastpot3");
		if ($lastpot3>=1) {
			output("If you need a loan, you might talk to %s`@, who won `^%s`@ gold for getting just three sixes.`n`n", get_module_setting("lastwin3"), get_module_setting("lastpot3"));
		}
		addnav("Play the Games");
		addnav("D?Roll the Dice","runmodule.php?module=game_fivesix&what=roll&ret=$ret");
	} elseif ($what=="roll"){
		$visits = get_module_pref("playstoday");
		$max = get_module_setting("dailyuses");

		if (($visits < $max) || ($max==0)){
			$cost=get_module_setting("cost");
			if ($session['user']['gold'] < $cost){
				output("`3The old man watches as nothing but moths and dust emerge from your coin purse.`n");
				output("He shakes his head at you then turns back to his ale as though you weren't there.");
				addnav("Return to the Main Room",appendlink(urldecode($ret), "op=tavern"));
			}else{
				debuglog("spent $cost gold on five-sixes game");
				$session['user']['gold']-=$cost;
				$visits++;
				set_module_pref("playstoday",$visits);
				$prize+=$cost;

				$maxpot=get_module_setting("maxjackpot");
				if ($prize > $maxpot) {
					output("`n`@The old man slips your donation into his own change purse, muttering something about the pot being big enough already.`n`n");
					$prize = $maxpot;
				}

				set_module_setting("jackpot",$prize);

				$almost=0;
				$one=e_rand(1,6);
				if ($one==6) $almost++;
				$two=e_rand(1,6);
				if ($two==6) $almost++;
				$three=e_rand(1,6);
				if ($three==6) $almost++;
				$four=e_rand(1,6);
				if ($four==6) $almost++;
				$five=e_rand(1,6);
				if ($five==6) $almost++;

				output("`n`@You gather the dice in an old leather cup, shake them, and hold your breath as you spill them onto the table.`n");
				output("Upon inspecting them, you see their values are `^%s %s %s %s `@and `^%s`@.`n`n",$one,$two,$three,$four,$five);
				if ($almost==5){
					output("\"Congratulations!\", the old man exclaims.");
					output("\"Tis rare for anybody to win this prize\"`n");
					output("`^The old man hands you your winnings of %s gold",$prize);
					$session['user']['gold']+=$prize;
					debuglog("won $prize gold at sixes game.");
					addnews("%s won %s gold after rolling 5 sixes in the %s.",$session['user']['name'],$prize, get_module_setting("tavernname","darkhorse"));
					set_module_setting("jackpot",100);
					set_module_setting("lastpot5",$prize);
					$lastwin5=$session['user']['name'];
					set_module_setting("lastwin5",$lastwin5);
				}elseif ($almost==4){
					$win=round($prize * .1);
					$prize=$prize - $win;
					set_module_setting("jackpot", $prize);
					$session['user']['gold']+=$win;
					debuglog("won $win gold at sixes game.");
					set_module_setting("lastpot4",$win);
					$lastwin4=$session['user']['name'];
					set_module_setting("lastwin4",$lastwin4);

					addnews("%s won %s gold after rolling 4 sixes in the %s.",$session['user']['name'],$win, get_module_setting("tavernname", "darkhorse"));
					output("`@The old man leans over the table to peer at your dice and looks thoughtful for a moment.`n");
					output("\"Well now...four out of five sixes is darned close, my friend. I think your effort deserves %s gold in reward.\"`n`n",$win);
					output("`^The old man hands you %s gold.", $win);
				}elseif ($almost==3){
					$win=round($prize * .05);
					$prize=$prize - $win;
					set_module_setting("jackpot", $prize);
					$session['user']['gold']+=$win;
					set_module_setting("lastpot3",$win);
					$lastwin3=$session['user']['name'];
					set_module_setting("lastwin3",$lastwin3);

					debuglog("won $win gold at sixes game.");
					addnews("%s won %s gold after rolling 3 sixes in the %s.",$session['user']['name'],$win,get_module_setting("tavernname", "darkhorse"));
					output("`@The old man leans over the table to peer at your dice and cackles as he notes your results.`n");
					output("\"Well now...three out of five sixes isn't a bad effort, my friend. I'll give ye %s gold for that try.\"`n`n",$win);
					output("`^The old man hands you %s gold.", $win);
				}else{
					output("The old man cackles.  You rolled %s %s...but that's not enough!`n`n", $almost, translate_inline($almost == 1? "six": "sixes"));
					output("Disappointed, you walk away.");
				}
			}
		}else{
			output("`@The old man looks up at you and shakes his head slowly.`n");
			output("`%\"I think you've had enough for today.  Why don't you come back tomorrow?\"");
		}
		addnav("Play the Games");
		addnav("Play again?","runmodule.php?module=game_fivesix&ret=$ret&what=play");
	}
	addnav("Other Games",appendlink(urldecode($ret), "op=oldman"));
	addnav("Return to the Main Room",appendlink(urldecode($ret), "op=tavern"));

	page_footer();
}
?>

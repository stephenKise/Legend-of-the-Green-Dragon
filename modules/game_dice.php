<?php
// addnews ready
// mail ready
// translator ready
function game_dice_getmoduleinfo(){
	$info = array(
		"name"=>"Dice Game for DarkHorse",
		"author"=>"Eric Stevens",
		"version"=>"1.1",
		"category"=>"Darkhorse Game",
		"download"=>"core_module",
	);
	return $info;
}

function game_dice_install(){
	global $session;
	debug("Adding Hooks");
	module_addhook("darkhorsegame");
	return true;
}

function game_dice_uninstall(){
	output("Uninstalling this module.`n");
	return true;
}

function game_dice_dohook($hookname, $args){
	if ($hookname=="darkhorsegame"){
		$ret = urlencode($args['return']);
		addnav("D?Play Dice Game",
				"runmodule.php?module=game_dice&ret=$ret");
	}
	return $args;
}

function game_dice_run(){
	global $session;
	$ret = urlencode(httpget("ret"));
	page_header("A Game of Dice");

	if ($session['user']['gold']>0){
		$bet = abs((int)httpget('bet') + (int)httppost('bet'));
		if ($bet<=0){
			addnav("Never mind", appendlink(urldecode($ret), "op=oldman"));
			output("`3\"`!You get to roll a die, and choose to keep or pass on the roll.  If you pass, you get up to two more chances to roll, for a total of three rolls.  Once you keep your roll (or on the third roll), I will do the same.  In the end, if my die is higher than yours, I win, if yours is higher, you win, and if they are a tie, neither of us wins, and we each keep our bet.`3\"`n`n");
			output("`3\"`!How much would you bet young %s?`3\"", translate_inline($session['user']['sex']?"lady":"man"));
			rawoutput("<form action='runmodule.php?module=game_dice&ret=$ret' method='POST'>");
			rawoutput("<input name='bet' id='bet'>");
			$b = translate_inline("Bet");
			rawoutput("<input type='submit' class='button' value='$b'>");
			rawoutput("</form>");
			rawoutput("<script language='JavaScript'>document.getElementById('bet').focus();</script>");
			addnav("","runmodule.php?module=game_dice&ret=$ret");
		}else if($bet>$session['user']['gold']){
			output("`3The old man reaches out with his stick and pokes your coin purse.");
			output("\"`!I don't believe you have `^%s`! gold!`3\" he declares.`n`n", $bet);
			output("Desperate to really show him good, you open up your purse and spill out its contents: `^%s`3 gold.`n`n", $session['user']['gold']);
			output("Embarrassed, you think you'll head back to the tavern.");
			addnav("Return to the Main Room",appendlink(urldecode($ret), "op=tavern"));
		} else {
			$what = httpget('what');
			if ($what!="keep"){
				$session['user']['specialmisc']=e_rand(1,6);
				$try=(int)httpget('try');
				$try++;
				switch ($try) {
				case 1: $die = "first";  break;
				case 2: $die = "second";  break;
				default: $die = "third";  break;
				}
				$die = translate_inline($die);
				output("You roll your %s die, and it comes up as `b%s`b`n`n", $die, $session['user']['specialmisc']);
				output("`3You have bet `^%s`3.", $bet);
				output("What do you do?");
				addnav("Keep","runmodule.php?module=game_dice&what=keep&bet=$bet&ret=$ret");
				if ($try<3)
					addnav("Pass","runmodule.php?module=game_dice&what=pass&try=$try&bet=$bet&ret=$ret");
			}else{
				output("Your final roll was `b%s`b, the old man will now try to beat it:`n`n", $session['user']['specialmisc']);
				$r = e_rand(1,6);
				output("The old man rolls a %s...`n", $r);
				if ($r>$session['user']['specialmisc'] || $r==6){
					output("\"`7I think I'll stick with that roll!`0\" he says.`n");
				}else{
					$r = e_rand(1,6);
					output("The old man rolls again and gets a %s...`n", $r);
					if ($r>=$session['user']['specialmisc']){
						output("\"`7I think I'll stick with that roll!`0\" he says.`n");
					}else{
						$r = e_rand(1,6);
						output("The old man rolls his final roll and gets a %s...`n", $r);
					}
				}
				if ($r>$session['user']['specialmisc']){
					output("`n\"`7Yeehaw, I knew the likes of you would never stand up to the likes of me!`0\" exclaims the old man as you hand him your `^%s`0 gold.", $bet);
					$session['user']['gold']-=$bet;
					debuglog("lost $bet gold at dice");
				}elseif ($r==$session['user']['specialmisc']){
					output("`n\"`7Yah... well, looks as though we tied.`0\" he says.");
				}else{
					output("`n\"`7Aaarrgh!!!  How could the likes of you beat me?!?!?`0\" shouts the old man as he gives you the gold he owes.");
					$session['user']['gold']+=$bet;
					debuglog("won $bet gold at dice");
				}
				addnav("Play again?","runmodule.php?module=game_dice&ret=$ret");
				addnav("Other Games",appendlink(urldecode($ret), "op=oldman"));
				addnav("Return to the Main Room", appendlink(urldecode($ret), "op=tavern"));
			}
		}
	}else{
		output("`3The old man reaches out with his stick and pokes your coin purse.  \"`!Empty?!?!  How can you bet with no money??`3\" he shouts.");
		output("With that, he turns back to his dice, apparently having already forgotten his anger.");
		addnav("Return to the Main Room", appendlink(urldecode($ret), "op=tavern"));
	}
	page_footer();
}
?>

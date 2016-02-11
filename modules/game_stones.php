<?php
// addnews ready
// mail ready
// translator ready
function game_stones_getmoduleinfo(){
	$info = array(
		"name"=>"Stones Game for DarkHorse",
		"author"=>"Eric Stevens",
		"version"=>"1.1",
		"category"=>"Darkhorse Game",
		"download"=>"core_module",
	);
	return $info;
}

function game_stones_install(){
	global $session;
	debug("Adding Hooks");
	module_addhook("darkhorsegame");
	return true;
}

function game_stones_uninstall(){
	output("Uninstalling this module.`n");
	return true;
}

function game_stones_dohook($hookname, $args){
	if ($hookname=="darkhorsegame"){
		$ret = urlencode($args['return']);
		addnav("S?Play Stones Game",
				"runmodule.php?module=game_stones&ret=$ret");
	}
	return $args;
}

function game_stones_run(){
	global $session;
	$ret = urlencode(httpget("ret"));
	page_header("A Game of Stones");
	$stones = unserialize($session['user']['specialmisc']);
	if (!is_array($stones)) $stones = array();
	$side = httpget('side');
	if ($side=="likepair") $stones['side']="likepair";
	if ($side=="unlikepair") $stones['side']="unlikepair";
	$bet = httppost('bet');
	if ($bet != "")
		$stones['bet'] = min($session['user']['gold'], abs((int)$bet));
	if (!isset($stones['side']) || $stones['side']==""){
		output("`3The old man explains his game, \"`7I have a bag with 6 red stones, and 10 blue stones in it.  You can choose between 'like pair' or 'unlike pair.'  I will then draw out pairs of stones two at a time.  If they are the same color as each other, they go to which ever of us is 'like pair,' and otherwise they go to which ever of us is 'unlike pair.'  Whoever has the most stones at the end will win.  If we have the same number, then it is a draw, and no one wins.`3\"");
		addnav("Never Mind", appendlink(urldecode($ret), "op=oldman"));
		addnav("Like Pair",
				"runmodule.php?module=game_stones&side=likepair&ret=$ret");
		addnav("Unlike Pair",
				"runmodule.php?module=game_stones&side=unlikepair&ret=$ret");
		$stones['red']=6;
		$stones['blue']=10;
		$stones['player']=0;
		$stones['oldman']=0;
	}elseif (!isset($stones['bet']) || $stones['bet']==0){
		$s1 = translate_inline($stones['side']=="likepair"?"Like":"Unlike");
		$s2 = translate_inline($stones['side']=="likepair"?"unlike":"like");
		output("`3\"`7%s pair for you, and %s pair for me it is then!  How much do you bet?`3\"", $s1, $s2);
		rawoutput("<form action='runmodule.php?module=game_stones&ret=$ret' method='POST'>");
		rawoutput("<input name='bet' id='bet'>");
		$b = translate_inline("Bet");
		rawoutput("<input type='submit' class='button' value='$b'>");
		rawoutput("</form>");
		rawoutput("<script language='JavaScript'>document.getElementById('bet').focus();</script>");
		addnav("","runmodule.php?module=game_stones&ret=$ret");
		addnav("Never Mind", appendlink(urldecode($ret), "op=oldman"));
	}elseif ($stones['red']+$stones['blue'] > 0 &&
			$stones['oldman']<=8 && $stones['player']<=8){
		$s1="";
		$s2="";
		$rstone = translate_inline("`\$red`3");
		$bstone = translate_inline("`!blue`3");
		while ($s1=="" || $s2==""){
			$s1 = e_rand(1,($stones['red']+$stones['blue']));
			if ($s1<=$stones['red']) {
				$s1=$rstone;
				$stones['red']--;
			}else{
				$s1=$bstone;
				$stones['blue']--;
			}
			if ($s2=="") {
				$s2=$s1;
				$s1="";
			}
		}
		output("`3The old man reaches into his bag and withdraws two stones.");
		output("They are %s and %s.  Your bet is `^%s`3.`n`n", $s1, $s2, $stones['bet']);

		if ($stones['side']=="likepair" && $s1==$s2) {
			$winner="your";
			$stones['player']+=2;
		} elseif ($stones['side']!="likepair" && $s1!=$s2) {
			$winner="your";
			$stones['player']+=2;
		} else {
			$stones['oldman']+=2;
			$winner = "his";
		}
		$winner = translate_inline($winner);

		output("Since you are %s pairs, the old man places the stones in %s pile.`n`n", translate_inline($stones['side']=="likepair"?"like":"unlike"), $winner);

		output("You currently have `^%s`3 stones in your pile, and the old man has `^%s`3 stones in his.`n`n", $stones['player'], $stones['oldman']);
		output("There are %s %s stones and %s %s stones in the bag yet.", $stones['red'], $rstone, $stones['blue'], $bstone);
		addnav("Continue","runmodule.php?module=game_stones&ret=$ret");
	}else{
		if ($stones['player']>$stones['oldman']){
			output("`3Having defeated the old man at his game, you claim your `^%s`3 gold.", $stones['bet']);
			$session['user']['gold']+=$stones['bet'];
			debuglog("won {$stones['bet']} gold in the stones game");
		}elseif ($stones['player']<$stones['oldman']){
			output("`3Having defeated you at his game, the old man claims your `^%s`3 gold.", $stones['bet']);
			$session['user']['gold']-=$stones['bet'];
			debuglog("lost {$stones['bet']} gold in the stones game");
		}else{
			output("`3Having tied the old man, you call it a draw.");
		}
		$stones=array();
		addnav("Play again?","runmodule.php?module=game_stones&ret=$ret");
		addnav("Other Games",appendlink(urldecode($ret), "op=oldman"));
		addnav("Return to Main Room", appendlink(urldecode($ret), "op=tavern"));
	}
	$session['user']['specialmisc']=serialize($stones);
	page_footer();
}
?>

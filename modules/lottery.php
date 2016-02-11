<?php
//addnews ready
// mail ready
// translator ready

function lottery_getmoduleinfo(){
	$info = array(
		"name"=>"Cedrik's Lottery",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Inn",
		"download"=>"core_module",
		"settings"=>array(
			"Village Lottery Settings,title",
			"basepot"=>"How much gold is the base pot?,int|0",
			"ticketcost"=>"How much gold does a ticket cost?,int|100",
			"percentbleed"=>"Percentage to keep for injured forest creatures,range,1,100,1|50",
			"currentjackpot"=>"Current Jackpot,int|0",
			"roundnum"=>"Lottery Round Number,viewonly|0",
			"Past Round Data,title",
			"todaysnumbers"=>"Last numbers,viewonly|0000",
			"prize"=>"Last Jackpot,int|0",
			"howmany"=>"How many people was this prize split among?,int|0",
		),
		"prefs"=>array(
			"Village Lottery User Preferences,title",
			"pick"=>"Numbers chosen,|",
			"roundnum"=>"Round the numbers were chosen in,int|0",
		)
	);
	return $info;
}

function lottery_install(){
	module_addhook("newday");
	module_addhook("newday-runonce");
	module_addhook("inn");
	return true;
}

function lottery_uninstall(){
	return true;
}

function lottery_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		$numbers = get_module_setting("todaysnumbers");
		$n = $numbers;
		output("`n`@Today's lottery numbers are `^%s-%s-%s-%s`0`n", $n[0], $n[1], $n[2], $n[3]);
		$roundnum = get_module_setting("roundnum");
		$pround = get_module_pref("roundnum");
		if ($roundnum > $pround){
			if (get_module_pref("pick")===$numbers){
				$prize = get_module_setting("prize");
				if ($prize>"" && $pround < $roundnum){
					rawoutput("<font size='+1'>");
					output("`\$You won today's lottery!`0`n");
					rawoutput("</font>");
					output("`\$The jackpot is `^^%s`\$ gold, money that has been deposited in to your bank account!`n", $prize);
					$session['user']['goldinbank']+=$prize;
					debuglog("won $prize gold on lottery");
					addnews("`@%s`@ won `^%s`@ gold in the lottery!`0",$session['user']['name'],$prize);
				}
			}
			set_module_pref("pick","");
		}
		break;
	case "newday-runonce":
		$numbers = array();
		for ($i=0; $i<4; $i++){
			$numbers[$i] = e_rand(0,9);
		}
		sort($numbers);
		set_module_setting("todaysnumbers",join("",$numbers));
		$sql = "SELECT count(*) AS c FROM " . db_prefix("module_userprefs") . " WHERE modulename='lottery' AND setting='pick' AND value='".join("",$numbers)."'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if ($row['c']>0){
			//split the jackpot among winners.
			$prize = round(get_module_setting("currentjackpot") / $row['c'],0);
			set_module_setting("prize",$prize);
			set_module_setting("currentjackpot",get_module_setting("basepot"));
			set_module_setting("howmany",$row['c']);
		}else{
			//the jackpot rolls over.
			set_module_setting("prize",0);
			set_module_setting("howmany",0);
		}
		set_module_setting("roundnum",get_module_setting("roundnum")+1);
		break;
	case "inn":
		addnav("Things to do");
		addnav(array("%s`0's Lottery", getsetting('barkeep', '`tCedrik')),"runmodule.php?module=lottery&op=store");
		break;
	}
	return $args;
}

function lottery_run(){
	global $session;
	$op = httpget("op");
	$cost = get_module_setting("ticketcost");
	$numbers = get_module_setting("todaysnumbers");
	$n = $numbers;
	$prize = get_module_setting("prize");
	$prizecount = get_module_setting("howmany");
	$jackpot = (int)get_module_setting("currentjackpot");
	$bleed = (int)get_module_setting("percentbleed");
	$roundnum = (int)get_module_setting("roundnum");
	$msg="";
	if ($op=="buy"){
		$op="store";
		httpset("op", $op);
		if ($session['user']['gold']>=$cost){
			$lotto = httppost("lotto");
			if ($lotto) {
				sort($lotto);
				set_module_pref("pick",join("",$lotto));
				set_module_pref("roundnum",$roundnum);
				$session['user']['gold']-=$cost;
				debuglog("spent $cost on a lottery ticket");
				$jackpot += round($cost * (100-$bleed) / 100,0);
				set_module_setting("currentjackpot",$jackpot);
			} else {
				$msg = translate_inline("`\$You seem to have mumbled when you requested the lottery tickets.  Please restate your numbers.`0`n`n");
			}
		}else{
			$msg = translate_inline("`\$You do not have enough to buy a lottery ticket!`0`n`n");
		}
	}
	if ($op=="store"){
		require_once("lib/villagenav.php");
		page_header("%s's Lottery", getsetting("barkeep", "`tCedrik"));
		output("Today's lottery numbers are `^%s-%s-%s-%s`7.", $n[0], $n[1], $n[2], $n[3]);
		output("The jackpot is now up to `^%s`0 gold!`n`n", $jackpot);
		if ($prize>0) {
			if ($prizecount == 1) {
				output("The winner of the last jackpot got `^%s`0 gold!`n`n", $prize);
			}  else {
				output("The %s winners of the last jackpot each got `^%s`0 gold!`n`n", $prizecount, $prize);
			}
		} else {
			output("There were no recent jackpot winners!`n`n");
		}
		$pick = get_module_pref("pick");
		if ($pick>""){
			$n = $pick;
			output("You bought a lottery ticket for tomorrow's drawing; the numbers you chose are: `^%s-%s-%s-%s`7`n`n", $n[0], $n[1], $n[2], $n[3]);
		}else{
			output("%s", $msg);
			output("Lottery tickets cost `^%s`0 gold.", $cost);
			output("If you would like to buy one for tomorrow's drawing, please choose your numbers below and click \"Buy\".");
			rawoutput("<form action='runmodule.php?module=lottery&op=buy' method='POST'>",true);
			addnav("","runmodule.php?module=lottery&op=buy");
			for ($i = 0; $i < 4; $i++) {
				$j = $i+1;
				if ($j == 4) $k = "buy";
				else $k = $j+1;
				rawoutput("<select id='lotto$j' name='lotto[$i]' onChange='document.getElementById(\"lotto$k\").focus()'>");
				for($x = 0; $x < 10; $x++) {
					rawoutput("<option value='$x'>$x</option>");
				}
				rawoutput("</select>");
			}
			$b = translate_inline("Buy");
			rawoutput("<input type='submit' class='button' value='$b' id='lottobuy'>");
			rawoutput("</form>");
			rawoutput("<script language='JavaScript'>document.getElementById('lotto1').focus();</script>");
		}
		output("A drawing is held at the start of each game day.");
		output("If the numbers you chose match, in any order, you win the jackpot for the day!");
		output("If no one matches the jackpot on a particular day, the sum will roll over for the following game day.");
		output("A portion of the proceeds of the lottery go to help injured forest creatures.");
		addnav("Return");
		addnav("I?Return to the Inn","inn.php");
		villagenav();
		page_footer();
	}
}
?>

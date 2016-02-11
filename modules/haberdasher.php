<?php
// translator ready
// addnews ready
// mail ready
/*
Deimos'  Haberdashery
Author:  Red Yates aka Deimos
Date:    8/10/2004
Version: 2.6 (3/14/2005)

A hat store for users to spend excess gold on hats.
The hats serve no game purpose and are completely a luxury.

Version 1.5:
First Public Release

Version 1.6:
Removed Hatsize userpref, instead always calculating size on the fly.

Version 2.0:
Added Customer Listing (Hall of Fame style).
And an option for depreciating of hats on DK (defaults to off).

Version 2.1:
Some fixes and translator and addnews ready, courtesy of Kendaer.

Version 2.2:
Added option for chosing which villages for the Haberdashery
to show in, with much assist from Saucy and Kendaer
This requires .9.8-prerelease.4 or up!

Version 2.25: Minor typo/color fixes, and improved translation friendliness.
Version 2.3:
Fixed some loopholes on hats going under the starting size.
From now on such hats disappear, especially from the hat list.
Changed village nav to D instead of H (to not conflict with Healer)

Version 2.31:
Fixed the last fix.

Version 2.4:
Changed improvemin setting into a range, changed increments
in improvemin and losepersent to 1.
Suggest that improvemin be higher than losepersent, by 10%.
Tried to make use of a new hook to dynamically change hatsize
when hatgold is edited in user editor.
Kendaer fixed this, using an existing hook. Yay Kendaer.

Version 2.41:
Changed hattypes into an array/array_rand.

Version 2.5:
At some point I started using a hatsize pref again, now it's fully
utilitzed, and it is only recalculated when hatgold could have changed.

Version 2.6:
Removed option to have the Haberdashery in any city, because Deimos loves the bustle of the capital.

*/

require_once("lib/http.php");
require_once("lib/villagenav.php");

function haberdasher_getmoduleinfo(){
	$info = array(
		"name"=>"Deimos' Haberdashery",
		"author"=>"`\$Red Yates",
		"version"=>"2.6",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Haberdasher Settings,title",
			"improvemin"=>"Minimum percent to improve hat,range,0,100,1|10",
			"lowesthat"=>"Price for size 1 hat,int|1000",
			"sizebase"=>"Hatsize multiplier: each level is the previous level times this,float|1.1",
			"spendall"=>"Users can spend all their gold on their hat,bool|0",
			"losepercent"=>"Percent of hat worth lost on DK (0 for off),range,0,100,1|0",
			"perpage"=>"Players to show per page in Deimos' Customer List, int|50",
			),
		"prefs"=>array(
			"Haberdasher User Preferences,title",
			"hatgold"=>"Player's hat is worth,int|0",
			"hatsize"=>"Player's hat is size,int|0",
			"Editing the size directly won't work as it's immediately recalculated based on worth.,note"
			)
		);
	return $info;
}

function haberdasher_install(){
	module_addhook("village");
	module_addhook("biostat");
	module_addhook("dragonkilltext");
	module_addhook("validateprefs");
	return true;
}

function haberdasher_uninstall(){
	return true;
}

function haberdasher_dohook($hookname, $args){
	global $session;
	switch($hookname){
	case "village":
		if ($session['user']['location'] == getsetting("villagename", LOCATION_FIELDS)){
			tlschema($args['schemas']['marketnav']);
			addnav($args["marketnav"]);
			tlschema();
			addnav("D?Deimos' Haberdashery","runmodule.php?module=haberdasher");
		}
		break;
	case "biostat":
		if (get_module_pref("hatsize",false,$args['acctid'])){
			output("`^Hat Size: `@%s`n", get_module_pref("hatsize",false,$args['acctid']));
		}
		break;
	case "dragonkilltext":
		$losepercent=get_module_setting("losepercent");
		$hatgold=get_module_pref("hatgold");
		if (($losepercent > 0) && ($hatgold > 0)){
			$losepercent/=100;
			$loseamount=floor($hatgold*$losepercent);
			$hatgold-=$loseamount;
			if ($hatgold <= get_module_setting("lowesthat")) {
				output("`n`nYou find a hat nearby, but it's too badly burnt to be worth wearing.");
				$hatgold = 0;
			}else{
				output("`n`nYou find a hat nearby, somewhat burnt, but still wearable.");
			}
			set_module_pref("hatgold",$hatgold);
			$hatsize=haberdasher_sizecalc($session['user']['acctid']);
		}
		break;
	case "validateprefs":
		if (isset($args['hatsize'])){
			if (is_numeric($args['hatsize'])){
				if (isset($args['hatgold'])) {
					$hatsize = haberdasher_rawsizecalc($args['hatgold']);
					if ($hatsize == 0) {
						$args['hatgold'] = 0;
					}
					$args['hatsize'] = $hatsize;
				}
			}
		}
		break;
	}
	return $args;
}

function haberdasher_rawsizecalc($gold){
	$base=get_module_setting("sizebase");
	$start=get_module_setting("lowesthat");
	if ($gold < $start) return 0;
	return floor((log($gold)-log($start))/log($base))+1;
}

function haberdasher_sizecalc($id){
	$total=get_module_pref("hatgold","haberdasher",$id);
	$hatsize = haberdasher_rawsizecalc($total);
	if ($hatsize == 0) {
		set_module_pref("hatgold",0,"haberdasher",$id);
	}
	set_module_pref("hatsize",$hatsize,"haberdasher",$id);
	return $hatsize;
}

function haberdasher_run(){
	global $session;
	$uid=$session['user']['acctid'];
	$spendall=get_module_setting("spendall");
	$op = httpget('op');
	$page = httpget('page');
	$perpage = get_module_setting("perpage");
	page_header("Deimos' Haberdashery");
	output("`c`b`\$Deimos' Haberdashery`b`c");
	$starting=get_module_setting("lowesthat");
	if ($op=="buy") {
		output("`7\"`3I think I'd like to buy a hat,`7\" you say.`n`n");
		output("`\$Deimos`7 says, \"`\$Good plan. Everyone should have a hat, especially you.`7\"`n`n");
		output("You ask him, \"`3What would a hat do?`7\"");
		output("You expect it'd look rather charming, or maybe help you defend yourself.`n`n");
		output("`\$Deimos`7 gives you a look, then answers, \"`\$Why, it sits on your head, of course.");
		output("What else would it do?");
		output("My hats are a luxury, you see.");
		output("Well, I suppose you could compare your hat to others, and see whose is the grandest.");
		output("Hats start at `^%s `\$gold, for the basic model.`7\"`n`n", $starting);

		output("`7How much gold do you want to spend on your hat?`n");
		$buy = translate_inline("Buy Hat");
		rawoutput("<form action='runmodule.php?module=haberdasher&op=bought' method='POST'>");
		rawoutput("<input name='amount' id='amount' width='8'>");
		rawoutput("<input type='submit' class='button' value='$buy'>");
		rawoutput("</form>");
		addnav("","runmodule.php?module=haberdasher&op=bought");
		if ($spendall) {
			output("`i`7Enter 0 or nothing to spend all your gold on your hat.`i");
		}
		addnav("H?Return to Haberdashery","runmodule.php?module=haberdasher");
		addnav("L?View Deimos' Customer List","runmodule.php?module=haberdasher&op=listing");
		villagenav();
	}elseif($op=="bought"){
		$amount = abs((int)httppost('amount'));
		if ($spendall) {
			if ($amount==0){
				$amount=$session['user']['gold'];
			}
		}
		if ($session['user']['gold']==0){
			output("`\$Deimos`7 stares at you blankly, as you don't have any gold, then walks off to do more important things.");
		}elseif ($amount>$session['user']['gold']){
			output("`\$Deimos`7 stares at you blankly, as you don't have that much gold, then walks off to do more important things.");
		}elseif($amount<$starting){
			output("`\$Deimos`7 looks at you pointedly and says, \"`\$I thought we went over this,`7\" then walks off to do more important things.`n`n");
			output("`7You need to spend at least `^%s`7 gold on a hat.", $starting);
		}else{
			$session['user']['gold']-=$amount;
			debuglog("spent $amount gold buying a hat.");
			set_module_pref("hatgold",$amount);
			$hatsize=haberdasher_sizecalc($uid);
			output("`\$Deimos`7 quickly and skillfully fashions you a hat as you try and follow what he is doing.");
			output("When he is done he hands the hat to you and watches you don it.");
			output("\"`\$Wonderful hat, if I do say so myself. Which I do.");
			output("You'll surely have some bragging rights with a hat like that,`7\" he says, mostly to himself.`n`n");
			output("You pay `\$Deimos`7 his `^%s `7gold and are now the proud owner of a size `@%s `7hat.",$amount, $hatsize);

			addnav("H?Return to Haberdashery","runmodule.php?module=haberdasher");
			addnav("L?View Deimos' Customer List","runmodule.php?module=haberdasher&op=listing");
		}
		villagenav();
	}elseif($op=="upgrade"){
		output("`7You inform `\$Deimos`7 that you want a bigger hat.`n`n");
		output("`\$Deimos`7 looks at you and your hat and says, \"`\$I suppose you're right.");
		output("Your hat, grand as it is, could stand some improving.");
		output("I'll tell you what, I'll do just that, just let me know how much to improve it.");
		output("Bear in mind, I won't keep doing this bit by bit, as I'm quite busy.");
		$impmin = get_module_setting("improvemin");
		output("You'll have to spend at least %s percent of what you've already spent on your hat to make it worth my doing.`7\"`n`n", $impmin);
		$worth = get_module_pref("hatgold");
		$hatsize=haberdasher_sizecalc($uid);
		output("`7Your size `@%s `7hat is worth `^%s `7gold so far.`n`n", $hatsize, $worth);
		output("`7How much gold do you want to spend on your hat?`n");
		$upgrade = translate_inline("Upgrade Hat");
		rawoutput("<form action='runmodule.php?module=haberdasher&op=upgraded' method='POST'>");
		rawoutput("<input name='amount' id='amount' width='8'>");
		rawoutput("<input type='submit' class='button' value='$upgrade'>");
		rawoutput("</form>");
		addnav("","runmodule.php?module=haberdasher&op=upgraded");
		if ($spendall) {
			output("`i`7Enter 0 or nothing to spend all your gold on your hat.`i");
		}
		addnav("H?Return to Haberdashery","runmodule.php?module=haberdasher");
		addnav("L?View Deimos' Customer List","runmodule.php?module=haberdasher&op=listing");
		villagenav();
	}elseif($op=="upgraded"){
		$amount = abs((int)httppost('amount'));
		$hatgold=get_module_pref("hatgold");
		$impmin=get_module_setting("improvemin");
		$impmin=$impmin/100;
		$needimp=$hatgold*$impmin;
		if ($spendall) {
			if ($amount==0){
				$amount=$session['user']['gold'];
			}
		}
		if ($session['user']['gold']==0){
			output("`\$Deimos`7 stares at you blankly, as you don't have any gold, then walks off to do more important things.");
		}elseif ($amount>$session['user']['gold']){
			output("`\$Deimos`7 stares at you blankly, as you don't have that much gold, then walks off to do more important things.");
		}elseif ($amount<$needimp){
			output("`\$Deimos`7 looks at you pointedly and says, \"`\$I thought we went over this,`7\" then walks off to do more important things.`n`n");
			$impmin = get_module_setting("improvemin");
			output("`7You need to improve your hat by at least %s percent.",$impmin);
		}else{
			$session['user']['gold']-=$amount;
			debuglog("spent $amount gold upgrading their hat.");
			set_module_pref("hatgold", $amount+get_module_pref("hatgold"));
			$hatsize=haberdasher_sizecalc($uid);
			output("`\$Deimos`7 takes your hat and sets to work quickly and skillfully improving it.");
			output("In what seems to be a lot less time than it should take, your hat is ready to be donned again.");
			output("`\$Deimos`7 hands your hat back to you, better than ever.");
			output("\"`\$I didn't think I could improve on my own work, but here I am, doing it again,`7\" he comments, mostly to himself.`n`n");
			$worth = get_module_pref("hatgold");
			output("You pay `\$Deimos`7 his `^%s `7gold. You now have a size `@%s `7hat, worth `^%s`7 gold!", $amount, $hatsize, $worth);

			addnav("H?Return to Haberdashery","runmodule.php?module=haberdasher");
			addnav("L?View Deimos' Customer List","runmodule.php?module=haberdasher&op=listing");
		}
		villagenav();
	}elseif($op=="listing"){
		$page = (int)$page;
		if (!$page) $page = 1;
		$pageoffset = $page;
		if ($pageoffset>0) $pageoffset--;
		$pageoffset*=$perpage;
		$from = $pageoffset+1;

		$limit=" LIMIT $pageoffset,$perpage ";

		output("`7You take a look at `\$Deimos`7' customer listing, which is sitting on the counter.");
		output("Apparently `\$Deimos`7 is rather proud of his biggest customers.`n`n");

		output("`c`b`\$Deimos' Customer Listing`b`c`n");
		$sql= "SELECT COUNT(*) AS c FROM " . db_prefix("module_userprefs") . " WHERE modulename='haberdasher' and value>0 and setting='hatsize'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$listtotal = $row['c'];
		$sql = "SELECT prefs.userid, (prefs.value+0) AS hatsize, users.name, users.sex, users.race FROM ".db_prefix("module_userprefs")." AS prefs, ".db_prefix("accounts")." AS users WHERE prefs.setting='hatsize' AND prefs.value>0 AND prefs.modulename='haberdasher' AND prefs.userid=users.acctid ORDER BY (prefs.value+0) DESC, prefs.userid ASC $limit";
		$result = db_query($sql);
		$count = db_num_rows($result);

		$rank = translate_inline("Rank");
		$size = translate_inline("Size");
		$cust = translate_inline("Customer");
		$sex = translate_inline("Sex");
		$race = translate_inline("Race");
		rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#999999'>");
		rawoutput("<tr class='trhead'><td>$rank</td><td>$size</td><td>$cust</td><td>$sex</td><td>$race</td>");

		if ($from+$perpage < $listtotal){
			$cond=$pageoffset+$perpage;
		}else{
			$cond=$listtotal;
		}
		for($i = $pageoffset; $i < $cond && $count; $i++) {
			$row = db_fetch_assoc($result);
			if ($row['name']==$session['user']['name']){
				rawoutput("<tr class='trhilight'><td>");
			} else {
				rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
			}
			$j=$i+1;
			output_notl("$j.");
			rawoutput("</td><td align=\"center\">");
			output_notl("`@%s`0", $row['hatsize']);
			rawoutput("</td><td>");
			output_notl("`&%s`0", $row['name']);
			rawoutput("</td><td>");
			output_notl("%s`0",
					translate_inline($row['sex']?"`%Female":"`!Male"));
			rawoutput("</td><td>");
			output_notl("`#%s`0", translate_inline($row['race'], "race"));
			rawoutput("</td></tr>");
		}

		if ($count == 0) {
			rawoutput("<tr><td colspan='5'>");
			output("`i`7Nobody has yet bought one of `\$Deimos'`7 fine hats. Why not be the first?`i");
			rawoutput("</td></tr>");
		}

		rawoutput("</table>");
		addnav("H?Return to Haberdashery","runmodule.php?module=haberdasher");
		villagenav();
		if ($listtotal>$perpage){
			// only show multipage navs if multiple pages are used.
			addnav("Pages");
			for ($p=0;$p<$listtotal;$p+=$perpage){
				$pnum = $p/$perpage+1;
				if ($pnum == $page) {
					addnav(array("`b`#Page %s`0 (%s-%s)`b", ($p/$perpage+1), ($p+1), min($p+$perpage,$listtotal)), "runmodule.php?module=haberdasher&op=listing&page=$pnum");
				} else {
					addnav(array("Page %s (%s-%s)", $pnum, ($p+1), min($p+$perpage,$listtotal)), "runmodule.php?module=haberdasher&op=listing&page=$pnum");
				}
			}
		}
	}else{
		checkday();
		output("`7You step into a respectable building, and see `\$Deimos`7 hard at work on a hat, as usual.");
		$hattypes=array("rakish","stylish","dashing","snazzy","jaunty","dapper");
		$n=array_rand($hattypes);
		$hattype=$hattypes[$n];
		output("`7He himself wears a `\$%s red hat`7, which you take to be a good sign of his skill.`n`n", $hattype);
		output("`\$Deimos`7 notices your entrance and stands and doffs his hat to you and says, \"`\$Hi, I assume you're here about a hat.");
		output("Most people who come in here are, this being a haberdashery and all.");
		output("So, what can I do for you?`7\"`n`n");
		if (get_module_pref("hatsize")<1){
			output("`7You currently do not have a hat.");
			addnav("Buy a Hat","runmodule.php?module=haberdasher&op=buy");
		}else{
			output("`7You have a size `@%s `7hat.", get_module_pref("hatsize"));
			addnav("Upgrade Your Hat","runmodule.php?module=haberdasher&op=upgrade");
		}
		addnav("L?View Deimos' Customer List","runmodule.php?module=haberdasher&op=listing");
		villagenav();
	}
	page_footer();
}
?>

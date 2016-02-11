<?php
// addnews ready
// mail ready
// translator ready

/* Holiday Town Marquee */
/* generic, for any holiday town */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 21st Sept 2004 */

// Thanks to Eric Stevens and Randy Yates for the SQL stuff
// ver 1.1 customisable meals and outfits 10 Nov 2004


require_once("lib/villagenav.php");
require_once("lib/http.php");

function marquee_getmoduleinfo(){
    $info = array(
        "name"=>"Holiday Marquee",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village",
        "download"=>"core_module",
        "settings"=>array(
            "Holiday Marquee - Settings,title",
			"price"=>"How much for a meal?,int|5",
			"marqueeloc"=>"Where does the marquee appear,location|".getsetting("villagename", LOCATION_FIELDS),
			"Holiday Marquee - Meals,title",
			"meal1"=>"Name of meal,|Stew",
			"meal2"=>"Name of meal,|Ghoulash",
			"meal3"=>"Name of meal,|Potatoes",
			"meal4"=>"Name of meal,|Eyeballs",
			"meal5"=>"Name of meal,|",
			"meal6"=>"Name of meal,|",
			"meal7"=>"Name of meal,|",
			"Holiday Marquee - Costumes,title",
			"suit1"=>"Name of suit,|`)ghost`7",
			"suit2"=>"Name of suit,|`4ghoul`7",
			"suit3"=>"Name of suit,|`\$pirate`7",
			"suit4"=>"Name of suit,|`%cheerleader`7",
			"suit5"=>"Name of suit,|huge blow-up `Qpumpkin`7",
			"suit6"=>"Name of suit,|`&skeleton`7",
			"suit7"=>"Name of suit,|`#clown`7",
        ),
        "prefs"=>array(
            "Holiday Marquee - User Preferences,title",
			"eatentoday"=>"Has the user eaten today?,bool|0",
			"voucher"=>"Does the user have a free pizza voucher?,bool|0",
        )
    );
    return $info;
}

function marquee_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function marquee_uninstall(){
    return true;
}

function marquee_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("marqueeloc")) {
				set_module_setting("marqueeloc", $args['new']);
			}
		}
		break;
   	case "newday":
		set_module_pref("eatentoday",0);
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("marqueeloc")) {
            tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
            tlschema();
			addnav("M?The Marquee","runmodule.php?module=marquee");
		}
		break;
	}
    return $args;
}

function marquee_run() {
    global $session;
	$op=httpget('op');
	$city = get_module_setting("villagename","ghosttown");
	$price = get_module_setting("price");
	$eatentoday = get_module_pref("eatentoday");
	$food = array(
		"plates of `2salad",
		"slices of `@cake",
		"bowls of `4soup",
		"mugs of `%fruit punch",
		"jugs of `6lemonade",
	);
	$food = translate_inline($food);
	$talk="";
	if (is_module_active("stafflist")){
		$sql = "SELECT a.name FROM " . db_prefix("accounts") . " AS a, " . db_prefix("module_userprefs") . " AS m WHERE m.modulename = 'stafflist' AND m.setting = 'rank' AND m.value > 0 AND m.userid = a.acctid ORDER by rand(".e_rand().") LIMIT 1";
		$result=db_query($sql);
		$row = db_fetch_assoc($result);
		$talk = $row['name'];
	}
	// Talk will be empty if we don't have a stafflist or if noone is set
	// as staff
	if ($talk==""){
		// These don't get translated since they are proper names.
		$talkers = array(getsetting("barkeep", "`tCedrik"), "MightyE");
		$talk = $talkers[e_rand(0, count($talkers)-1)];
	}

	$tucker = $food[e_rand(0, count($food)-1)];

	page_header("The Marquee");
	output("`&`c`bThe Marquee`b`c`7");
	$sit = translate_inline("You place your order, then take a seat and listen to the hubbub around you.");
	$chance=e_rand(1,3);

	$meals = array();
	for ($i = 1; $i < 8; $i++) {
		$meal = get_module_setting("meal$i");
		if ($meal) array_push($meals, $meal);
	}
	$meals = translate_inline($meals);

	$suits = array();
	for ($i = 1; $i < 8; $i++) {
		$suit = get_module_setting("suit$i");
		if ($suit) array_push($suits, $suit);
	}
	$suits = translate_inline($suits);
	$suit = $suits[e_rand(0, count($suits)-1)];
	$dk = $session['user']['dragonkills'];
	$voucher = get_module_pref("voucher");
	if ($chance==2 || $op=="pizza")
		$result = translate_inline("You laugh hysterically in good humor.");
	elseif ($chance==1)
		$result=translate_inline("You scream in panic and jump in fright.");
	else
		$result = translate_inline("You smile at the effective costume and congratulate him.");

	if($op==""){
		output("`7You step into the doorway of a huge tent, where many tourists are seated at long trestle tables.");
		output("A band is playing lively music, and several patrons are dancing.`n`n");
		output("Waitresses move among the diners, offering drinks in tall glasses.");
		output("%s`7 is standing at the servery, handing out %s`7.`n`n", $talk ,$tucker);
		output("On a board near the door is a list of the dishes available, for `^%s gold`7 each.",$price);
		if($eatentoday==1){
			output("`n`nYou really aren't hungry!");
			output("Maybe you'll come back tomorrow.");
		}elseif($session['user']['gold']<$price && $voucher){
			output("`n`nYou realize that most of the dishes here cost `^%s gold`7, but you have a voucher for free pizza.",$price);
			addnav("R?Redeem Pizza Voucher","runmodule.php?module=marquee&op=pizza");
		}elseif($session['user']['gold']<$price){
			output("`n`nYou then realize that dishes here cost `^%s gold`7.",$price);
			output("Maybe you'll come back another time.");
		}else{
			output("A petite lady in a %s suit asks if you would like to order.", $suit);
			addnav("Order");
			foreach($meals as $meal) {
				addnav(array("%s", $meal), "runmodule.php?module=marquee&op=".$meal);
			}
			if ($voucher)
				addnav("R?Redeem Pizza Voucher","runmodule.php?module=marquee&op=pizza");
		}
	}elseif ($op == "pizza"){
		output_notl("%s", $sit);
		output("The lady takes your voucher and moves away.");
		output("After a few minutes, a waiter in a %s suit approaches, and serves your %s before yelling, `&\"HAPPY HOLIDAYS!!!!\"`7`n`n",$suit,$op);
		output_notl("%s`n`n", $result);
		output("The box is marked with four stars.`n`n");
		output("You dig in with gusto!");
		output("You feel `@vigorous!");
		$session['user']['turns']+=2;
		set_module_pref("eatentoday",1);
		set_module_pref("voucher",0);
	}elseif($op!=""){
		output_notl("%s", $sit);
		output("After a few minutes, a waiter in a %s suit approaches, and serves your %s before merrily yelling, `&\"HAPPY HOLIDAYS!!!\"`7`n`n",$suit,$op);
		output_notl("%s`n`n", $result);
		set_module_pref("eatentoday",1);
		$session['user']['gold']-=$price;
		if ($chance==1){
			output("You `4lose `7some hitpoints!");
			$session['user']['hitpoints']=($session['user']['hitpoints']*0.85);
			if ($session['user']['hitpoints']<1)
				$session['user']['hitpoints']=1;
		}elseif($chance==2){
			output("You feel `@vigorous!");
			$session['user']['turns']++;
			// be nice to farmies
			if ($dk<=5) $session['user']['turns']++;
		}else{
			output("You feel `@healthy!");
			$session['user']['hitpoints'] =
				($session['user']['hitpoints']*1.05);
		}
	}
	villagenav();
	page_footer();
}
?>
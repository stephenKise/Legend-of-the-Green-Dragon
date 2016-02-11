<?php
// translator ready
// addnews ready
// mail ready

/* Calle Trader */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 17th September 2004 */

/*
 * This is the ticket seller for the Caravan Module and also the calle
 * shell trader, for access to the highcity module.
 * You do not need both of those modules installed.
 */

/*
 * The sell rate will automatically be set to at least one more than the
 * buy rate if you do not set them that way.
 */

require_once("lib/http.php");
require_once("lib/villagenav.php");

function calletrader_getmoduleinfo(){
    $info = array(
        "name"=>"Calle Trader",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
            "Calle Trader - Settings,title",
			"buyrate"=>"Amount of gems offered to player for five calle shells,range,2,50,1|11",
			"sellrate"=>"Amount of gems player needs to buy five calle shells,range,3,51,1|14",
			"allowall"=>"Have trader appear in all villages?,bool|0",
			"traderloc"=>"Where does the trader appear,location|".getsetting("villagename", LOCATION_FIELDS),
		),
        "prefs"=>array(
            "Calle Trader User Preferences,title",
            "callecount"=>"How many shells does the player have?,int|0",
        )
    );
    return $info;
}

function calletrader_install(){
	module_addhook("changesetting");
	module_addhook("village");
    return true;
}

function calletrader_uninstall(){
    return true;
}

function calletrader_dohook($hookname,$args){
    global $session;
	switch($hookname){
	case "village":
		$allowall=get_module_setting("allowall");
		if ($session['user']['location'] == get_module_setting("traderloc") ||
				$allowall) {
            tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
            tlschema();
			addnav("V?Vernon's Trade Stall","runmodule.php?module=calletrader");
		}
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("traderloc")) {
				set_module_setting("traderloc", $args['new']);
			}
		}
		break;
	}
	return $args;
}

function calletrader_run() {
    global $session;
    $op=httpget("op");
    $sellrate=get_module_setting("sellrate");
    $buyrate=get_module_setting("buyrate");
    $callecount=get_module_pref("callecount");

	if ($sellrate<=$buyrate) {
		$sellrate = $buyrate+1;
		set_module_setting("sellrate",$sellrate);
	}

	page_header("Vernon's Trade Stall");
	if ($op == ""){
		output("`&`c`bVernon, the Trader`b`c");
        output("`7You walk up to Vernon's stall, to see what he has to offer today.");
		output("He smiles and asks, `&\"And how can I be of assistance?\"");

		$sellsomething = 0;

		if (is_module_active("highcity")) {
			output("`7Gems and calle shells are arranged neatly under a glass cover.`n`n");
			// these are from Vernon's perspective... he is selling
			output("`&\"I'm buying five calle at the rate of %s gems, and selling for %s gems.\"",$buyrate,$sellrate);
			// these are from the player's perspective. They are buying
			addnav(array("B?Buy 5 Calle Shells (`5%s gems`0)",$sellrate),"runmodule.php?module=calletrader&op=buy");
			addnav(array("S?Sell 5 Calle Shells (`5%s gems`0)",$buyrate),"runmodule.php?module=calletrader&op=sell");
			$sellsomething = 1;
		}
		if (is_module_active("caravan") && is_module_active("ghosttown") &&
				get_module_setting("canvisit", "caravan")) {
			$vname = get_module_setting("villagename", "ghosttown");
			$ticketcost=get_module_setting("ticketcost", "caravan");
			output("`7Travel tickets to %s are in a small pile to his left.`n`n", $vname);
			addnav(array("T?Buy Ticket to %s (`^%s gold`0)", $vname,
						$ticketcost),
					"runmodule.php?module=calletrader&op=ticket");
			$sellsomething = 1;
		}
		if (is_module_active("icecaravan") && is_module_active("icetown") &&
				get_module_setting("canvisit", "icecaravan")) {
			$vname = get_module_setting("villagename", "icetown");
			$ticketcost=get_module_setting("ticketcost", "icecaravan");
			output("`7Travel tickets to %s are in a small pile to his right.`n`n", $vname);
			addnav(array("T?Buy Ticket to %s (`^%s gold`0)", $vname,
						$ticketcost),
					"runmodule.php?module=calletrader&op=iceticket");
			$sellsomething = 1;
		}

		if (!$sellsomething) {
			output("`n`n`7Vernon shrugs, `&\"I don't seem to have anything you need today!\"");
		}
	}elseif ($op == "sell" && $callecount>=5){
        output("`7You place five calle shells onto the glass, and Vernon inspects them carefully.");
        output("`7Satisfied, he hands you %s gems.",$buyrate);
		$callecount-=5;
		set_module_pref("callecount",$callecount);
		$session['user']['gems']+=$buyrate;
		debuglog("gained $buyrate gems selling 5 calle shells");
	}elseif ($op == "buy" && $session['user']['gems']>=$sellrate){
        output("`7You hand over your %s gems, and Vernon places five calle shells into your palm.",$sellrate);
		$callecount+=5;
		set_module_pref("callecount",$callecount);
		$session['user']['gems']-=$sellrate;
		debuglog("spent $sellrate gems on 5 calle shells");
	} elseif ($op == "iceticket" && is_module_active("icecaravan")) {
		$ticketcost=get_module_setting("ticketcost", "icecaravan");
		$hasticket=get_module_pref("hasticket","icecaravan");
		output("`7You announce that you'd like to buy one travel ticket, and you open your purse.");
		output("\"`&Certainly! One ticket. That will be %s gold, thank you.\"`n`n",$ticketcost);
		if ($session['user']['gold']<$ticketcost){
			// you don't have enough money.
			output("`7Vernon smiles.");
			output("\"Perhaps you'll return when you have enough gold to buy the ticket?\"");
			output("`7 You survey your purse sadly, and resolve to come back when you have more gold.");
		}elseif ($hasticket>0){
			// you already have a ticket.
			output("`7Vernon stops, then looks into your purse carefully.");
			output("\"`&Isn't that a ticket you have there already?");
			output("Ye don't be needing two!\"`n`n");
			output("`7You realize he is right!");
		}else{
			output("`7You hand over the gold and take the ticket eagerly.");
			$session['user']['gold']-=$ticketcost;
			set_module_pref("hasticket",1,"icecaravan");
			debuglog("spent $ticketcost gold on a travel ticket");
		}
	}elseif ($op == "ticket" && is_module_active("caravan")) {
		$ticketcost=get_module_setting("ticketcost", "caravan");
		$hasticket=get_module_pref("hasticket","caravan");
		output("`7You announce that you'd like to buy one travel ticket, and you open your purse.");
		output("\"`&Certainly! One ticket. That will be %s gold, thank you.\"`n`n",$ticketcost);
		if ($session['user']['gold']<$ticketcost){
			// you don't have enough money.
			output("`7Vernon smiles.");
			output("\"Perhaps you'll return when you have enough gold to buy the ticket?\"");
			output("`7 You survey your purse sadly, and resolve to come back when you have more gold.");
		}elseif ($hasticket>0){
			// you already have a ticket.
			output("`7Vernon stops, then looks into your purse carefully.");
			output("\"`&Isn't that a ticket you have there already?");
			output("Ye don't be needing two!\"`n`n");
			output("`7You realize he is right!");
		}else{
			output("`7You hand over the gold and take the ticket eagerly.");
			$session['user']['gold']-=$ticketcost;
			set_module_pref("hasticket",1,"caravan");
			debuglog("spent $ticketcost gold on a travel ticket");
		}
	}elseif ($op == "sell" || $op == "buy"){
        // you don't have enough
		output("`7You triumphantly dump your purse contents onto the glass and announce that his prices sound great.`n`n");
		output("Vernon eyes you carefully.");
        output("`&\"There seems to be a problem with that there arithmetic!\" `7he exclaims.");
		output("`7You decide to check his prices and count the contents of your purse again.");
		if ($op == "sell") addnav(array("B?Buy 5 Calle Shells (`5%s gems`0)",$sellrate),"runmodule.php?module=calletrader&op=buy");
		if ($op == "buy") 	addnav(array("S?Sell 5 Calle Shells (`5%s gems`0)",$buyrate),"runmodule.php?module=calletrader&op=sell");
	}
	villagenav();
	page_footer();
}

?>
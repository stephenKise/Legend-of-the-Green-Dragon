<?php

// Custom Weapon and Armor
// 23 Jan 2005
// ver 1.0 by Booger - bigredx (a) sci -dot- fi


require_once("lib/http.php");

function customeq_getmoduleinfo(){
	$info = array(
		"name"=>"Custom Equipment",
		"author"=>"Booger",
		"version"=>"1.0",
		"category"=>"Lodge",
		"download"=>"core_module",
		"settings"=>array(
			"Custom Equipment Module Settings,title",
			"keep"=>"When will the player lose the equipment?,enum,0,Never,1,After killing the dragon,2,After upgrading",
			"showlvl"=>"Does the equipment level appear in Equipment Info?,bool|1",
			"weaponcost"=>"How many points will the first custom weapon cost?,int|100",
			"armorcost"=>"How many points will the first custom armor cost?,int|100",
			"The costs for subsequent buys will only be used if the equipment carries over upgrades and DKs!,note",
			"extraweapon"=>"How many points will subsequent weapon changes cost?,int|0",
			"extraarmor"=>"How many points will subsequent armor changes cost?,int|0",
		),
		"prefs"=>array(
			"Custom Equipment Preferences,title",
			"weaponname"=>"Players custom weapon,|",
			"armorname"=>"Players custom armor,|",
			"keepeq"=>"Player can keep equipment over upgrades and DKs regardless of game settings,bool|0",
		),
	);
	return $info;
}

function customeq_install(){
	module_addhook("lodge");
	module_addhook("pointsdesc");
	module_addhook("footer-weapons");
	module_addhook("footer-armor");
	module_addhook("dragonkilltext");
	module_addhook("charstats");
	return true;
}

function customeq_uninstall(){
	return true;
}

function customeq_dohook($hookname,$args){
	global $session;
	$keep = get_module_setting("keep");
	$weapon = get_module_pref("weaponname");
	$armor = get_module_pref("armorname");
	$keepeq = get_module_pref("keepeq");
	switch($hookname){
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$wcost = get_module_setting("weaponcost");
		$acost = get_module_setting("armorcost");
		$xwcost = get_module_setting("extraweapon");
		$xacost = get_module_setting("extraarmor");
		if ($keep == 0){
			$basestr = translate("A custom %s costs %s points");
			$extrastr = translate(" for the first change and %s points for subsequent changes.");
		}elseif ($keep == 1){
			$basestr = translate("Renaming your %s until you kill the dragon costs %s points.");
		}else {
			$basestr = translate("Renaming your %s until you get a new one costs %s points.");
		}
		if ($xwcost && $keep == 0){
			$wstr = sprintf($basestr.$extrastr, translate_inline("weapon"), $wcost, $xwcost);
			}else{
			$wstr = sprintf($basestr.".", translate_inline("weapon"), $wcost);
		}
		if ($xwcost && $keep == 0){
			$astr = sprintf($basestr.$extrastr, translate_inline("armor"), $acost, $xacost);
		}else{
			$astr = sprintf($basestr.".", translate_inline("armor"), $acost);
		}
		output($format, $wstr, true);
		output($format, $astr, true);
		break;
	case "lodge":
		$wcost = get_module_setting("extraweapon");
		if ($wcost < 1 || !$weapon || $keep)
			$wcost = get_module_setting("weaponcost");
		$acost = get_module_setting("extraarmor");
		if ($acost < 1 || !$armor || $keep)
			$acost = get_module_setting("armorcost");
		addnav(array("Custom Weapon (%s points)", $wcost),
				"runmodule.php?module=customeq&op=buy&subop=weapon");
		addnav(array("Custom Armor (%s points)", $acost),
				"runmodule.php?module=customeq&op=buy&subop=armor");
		break;
	case "footer-weapons":
		$op = httpget("op");
		if (($keep != 2 || $keepeq) && $weapon && $op == "buy"){
			customeq_wchange($weapon);
			output("`n`n`7As you step out the door you realize that your new weapon has magically turned back into `5%s`7!`0`n", $session['user']['weapon']);
		}elseif ($weapon && $op == "buy"){
			if (is_module_active("costumeshop") &&
					get_module_pref("weapon","costumeshop"))
				customeq_wchange($weapon);
			else
				set_module_pref("weaponname","");
		}
		break;
	case "footer-armor":
		$op = httpget("op");
		if (($keep != 2 || $keepeq) && $armor && $op == "buy"){
			customeq_achange($armor);
			output("`n`n`5As you put on your new armor, it magically changes back into `&%s`5!`0`n", $session['user']['armor']);
		}elseif ($armor && $op == "buy"){
			if (is_module_active("costumeshop") &&
					get_module_pref("armor","costumeshop"))
				customeq_achange($armor);
			else
				set_module_pref("armorname","");
		}
		break;
	case "dragonkilltext":
		if ($keep == 0 || $keepeq){
			if ($weapon) customeq_wchange($weapon);
			if ($armor) customeq_achange($armor);
		}else{
			if ($weapon) set_module_pref("weaponname","");
			if ($armor) set_module_pref("armorname","");
		}
		break;
	case "charstats":
		if (get_module_setting("showlvl")){
			$wmsg = getcharstat("Equipment Info", "Weapon");
			if (!strpos($wmsg," (".$session['user']['weapondmg'].")")){
				$wmsg .= " (".$session['user']['weapondmg'].")";
				setcharstat("Equipment Info", "Weapon", $wmsg);
			}
			$amsg = getcharstat("Equipment Info", "Armor");
			if (!strpos($amsg," (".$session['user']['armordef'].")")){
				$amsg .= " (".$session['user']['armordef'].")";
				setcharstat("Equipment Info", "Armor", $amsg);
			}
		}
		break;
	}
	return $args;
}

function customeq_wchange($newweapon){
	global $session;
	set_module_pref("weaponname",$newweapon);
	$oldweapon = $session['user']['weapon'];
	$upgraded = strpos($oldweapon," +1")!==false ? true : false;
	$downgraded = strpos($oldweapon," -1")!==false ? true : false;
	if (is_module_active("costumeshop") &&
			get_module_pref("weapon","costumeshop")){
		set_module_pref("oldweapon",$newweapon,"costumeshop");
		$newweapon = get_module_pref("weapon","costumeshop");
	}
	if ($upgraded){
		$session['user']['weapon'] = $newweapon." +1";
	}elseif ($downgraded){
		$session['user']['weapon'] = $newweapon." -1";
	}else{
		$session['user']['weapon'] = $newweapon;
	}
}

function customeq_achange($newarmor){
	global $session;
	set_module_pref("armorname",$newarmor);
	$oldarmor = $session['user']['armor'];
	$upgraded = strpos($oldarmor," +1")!==false ? true : false;
	$downgraded = strpos($oldarmor," -1")!==false ? true : false;
	if (is_module_active("costumeshop") &&
			get_module_pref("armor","costumeshop")){
		set_module_pref("oldarmor",$newarmor,"costumeshop");
		$newarmor = get_module_pref("armor","costumeshop");
	}
	if ($upgraded){
		$session['user']['armor'] = $newarmor." +1";
	}elseif ($downgraded){
		$session['user']['armor'] = $newarmor." -1";
	}else{
		$session['user']['armor'] = $newarmor;
	}
}

function customeq_form($subop){
	$eq = translate_inline($subop);
	output("What would you like to name your %s?`0`n", $eq);
	$prev = translate_inline("Preview");
	rawoutput("<form action='runmodule.php?module=customeq&op=preview&subop=".$subop."' method='POST'><input name='newname' value=\"\"> <input type='submit' class='button' value='$prev'></form>");
	addnav("","runmodule.php?module=customeq&op=preview&subop=".$subop);
}

function customeq_run(){
	global $session;
	$op = httpget("op");
	$subop = httpget("subop");
	$keep = get_module_setting("keep");
	$weapon = get_module_pref("weaponname");
	$armor = get_module_pref("armorname");
	$wcost = get_module_setting("extraweapon");
	if ($wcost < 1 || !$weapon || $keep)
		$wcost = get_module_setting("weaponcost");
	$acost = get_module_setting("extraarmor");
	if ($acost < 1 || !$armor || $keep)
		$acost = get_module_setting("armorcost");
	$pointsavailable =
		$session['user']['donation'] - $session['user']['donationspent'];
	page_header("Hunter's Lodge");
	if ($op == "buy"){
		addnav("L?Return to the Lodge","lodge.php");
		output("`7J. C. Petersen smiles at you, \"`&So, you're interested in purchasing custom equipment.`7\"`n");
		if (($subop == "weapon" && $pointsavailable < $wcost) ||
				($subop == "armor" && $pointsavailable < $acost)){
			output("`nHe consults his book silently for a moment and then turns to you. \"`&I'm terribly sorry, but you only have %s points available.`7\"`n", $pointsavailable);
			if ($subop == "weapon"){
				output("`n\"`&A custom weapon costs %s points.`7\"`n`n",
						$wcost);
			}else{
				output("`n\"`&A custom armor costs %s points.`7\"`n`n",
						$acost);
			}
		}else{
			if ($subop == "weapon"){
				output("`n\"`&A custom weapon costs %s points.`7\"`n`n",
						$wcost);
			}else{
				output("`n\"`&A custom armor costs %s points.`7\"`n`n",
						$acost);
			}
			output("\"`&Unfortunately you may not use colors in the name.`7\"`0`n`n");
			customeq_form($subop);
		}
	}elseif ($op == "preview"){
		addnav("L?Return to the Lodge","lodge.php");
		$newname = rawurldecode(httppost("newname"));
		$newname = stripslashes($newname);
		$newname = str_replace("`0", "", $newname);
		$newname = preg_replace("/[+-][0-9]+/", "", $newname);
		$newname = trim($newname);
		$newname = sanitize($newname);
		$eq = translate_inline($subop);
		if ($newname){
			output("`7You have chosen to name your %s %s.`n", $eq, $newname);
			output(" Is this the name you want?`0`n");
			addnav("C?Confirm","runmodule.php?module=customeq&op=confirm&subop=".$subop."&newname=".rawurlencode($newname));
		}else{
			output("`7You did not choose a valid name for your %s!`0`n", $eq);
		}
		addnav("a?Choose another name","runmodule.php?module=customeq&op=buy&subop=".$subop."");
	}elseif ($op == "confirm"){
		addnav("L?Return to the Lodge","lodge.php");
		$newname = rawurldecode(httpget("newname"));
		$newname = stripslashes($newname);
		$eq = translate_inline($subop);
		output("`7Your %s has been changed.`0`n", $eq);
		if ($subop == "weapon"){
			customeq_wchange($newname);
			$session['user']['donationspent'] += $wcost;
			debuglog ("spent $wcost lodge points changing weapon to $newname.");
		}else{
			customeq_achange($newname);
			$session['user']['donationspent'] += $acost;
			debuglog ("spent $wcost lodge points changing armor to $newname.");
		}
		if (is_module_active("costumeshop")) {
			output("`n`\$Please note, that if you have rented a costume, you might not see your custom %s until you return the costume!`0`n", $eq);
		}
	}
	page_footer();
}
?>

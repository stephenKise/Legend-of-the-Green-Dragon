<?php

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 28 May 2005 */

/* ver 1.1 by Catscradler */
/* reduced the number of database hits, now easier to add more items */
/* 18 June 2005 */

// v1.11 (dying) main change
// =========================
// - added output of jewelry inventory to character biography

require_once("lib/http.php");
require_once("lib/villagenav.php");

function jeweler_getmoduleinfo(){
	$info = array(
		"name"=>"Jeweler",
		"version"=>"1.11",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"choker"=>"Price of the jeweled choker,range,300,500,10|300",
			"amulet"=>"Price of the jeweled amulet,range,200,300,10|200",
			"necklace"=>"Price of the jeweled necklace,range,100,200,10|100",
			"bracelet"=>"Price of the jeweled bracelet,range,50,100,5|50",
			"ring"=>"Price of the jeweled ring,range,10,50,5|10",
			"sellpercent"=>"Sellback value percentage,range,50,100,1|50",
			"jewelerloc"=>"Where does the jeweler appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
			"chokerheld"=>"Does the player own a jeweled choker,bool|0",
			"amuletheld"=>"Does the player own a jeweled amulet,bool|0",
			"necklaceheld"=>"Does the player own a jeweled necklace,bool|0",
			"braceletheld"=>"Does the player own a jeweled bracelet,bool|0",
			"ringheld"=>"Does the player own a jeweled ring,bool|0",
			"totalheld"=>"Items held in total,int|0",
		)
	);
	return $info;
}

function jeweler_install(){
	module_addhook("changesetting");
	module_addhook("village");
	module_addhook("biostat");

	return true;
}

function jeweler_uninstall(){
	return true;
}

function jeweler_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("jewelerloc")) {
				set_module_setting("jewelerloc", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("jewelerloc")) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("Oliver's Jewelry","runmodule.php?module=jeweler");
		}
		break;
	case "biostat":
		$choker = get_module_pref("chokerheld", false, $args['acctid']);
		$amulet = get_module_pref("amuletheld", false, $args['acctid']);
		$necklace = get_module_pref("necklaceheld", false, $args['acctid']);
		$bracelet = get_module_pref("braceletheld", false, $args['acctid']);
		$ring = get_module_pref("ringheld", false, $args['acctid']);

		if ($choker||$amulet||$necklace||$bracelet||$ring) {
			output("`^Jewelry: ");
			$displayed = 0;
			$displaystring = "";

			$chokertext = translate_inline("Choker");
			$amulettext = translate_inline("Amulet");
			$necklacetext = translate_inline("Necklace");
			$bracelettext = translate_inline("Bracelet");
			$ringtext = translate_inline("Ring");

			if ($choker) {
				$displaystring = $chokertext;
				$displayed++;
			}

			if ($amulet)
				$displaystring = sprintf("%s%s%s", $displaystring, $displayed++ ? ", " : "", $amulettext);
			if ($necklace)
				$displaystring = sprintf("%s%s%s", $displaystring, $displayed++ ? ", " : "", $necklacetext);
			if ($bracelet)
				$displaystring = sprintf("%s%s%s", $displaystring, $displayed++ ? ", " : "", $bracelettext);
			if ($ring)
				$displaystring = sprintf("%s%s%s", $displaystring, $displayed++ ? ", " : "", $ringtext);

			output_notl("`@%s`n", $displaystring);
		}
		break;
	}
	return $args;
}

function jeweler_run(){
	global $session;

	$totalheld = get_module_pref("totalheld");
	$sellpercent = get_module_setting("sellpercent");
	$op = httpget("op");
	$type = httpget("type");

	page_header("Oliver, the Jeweler");
	output("`&`c`bOliver's Jewelry`b`c`n");

	if ($op==""){
		$itemarray=array( "choker"=>"C",
					"amulet"=>"A",
					"necklace"=>"N",
					"bracelet"=>"B",
					"ring"=>"R");
		$prefsarray=get_all_module_prefs();

		addnav("Examine");
		while (list($key, $val) = each($itemarray)) {
			if (!$prefsarray[$key."held"]){
				$navstring="runmodule.php?module=jeweler&op=examine&type=$key";
				$key=str_replace("_" , " " , $key);
				$key=ucwords($key);
				$displaystring="Examine $key";
				if ($val!==false) $displaystring=$val . "?" . $displaystring;
				addnav($displaystring,$navstring);
			}
		}
		reset($itemarray);

		addnav("Sell");
		while (list($key, $val) = each($itemarray)) {
			if ($prefsarray[$key."held"]){
				$navstring="runmodule.php?module=jeweler&op=sell&type=$key";
				$key=str_replace("_" , " " , $key);
				$key=ucwords($key);
				$displaystring=" ?Sell Back $key";
				addnav($displaystring,$navstring);
			}
		}

		output("`7You step into Oliver's Jewelry Store and are greeted by a dwarf in spectacles.");
		output("`3Oliver`7 folds his hands on a small bench and his eyes twinkle.`n`n");
		output("\"`&Good day, warrior, come to see the fine wares, have ye?`7\"`n`n");
		output("`7You see another bench showcasing some of his fine work, and wonder whether to take a closer look.");
	} elseif ($op=="examine") {
		switch($type){
		case "choker":
			output("`3Oliver `7gets up from his stool and moves towards the magnificent choker sitting on a mannequin's torso.");
			output("Glittering jewels are held together by gold chains, meshed in a design larger than your hand.`n`n");
			output("\"`&Ye have a fine eye for quality there!`7\" he says.`n`n");
			output("\"`&More than a hundred pieces of crystal and fifty more precious stones went into this.");
			output("It isn't every day I put my heart into a piece like this!");
			break;
		case "amulet":
			output("`3Oliver `7rises and moves towards the amulet on the stand.");
			output("Polished silver sports gems in all colors of the spectrum.`n`n");
			output("\"`&Ah, the amulet in silver!`7\" he says.`n`n");
			output("\"`&So many hours of work, so many precious stones!");
			output("This is no cheap item, no, ye've spotted a quality work, here.");
			break;
		case "necklace":
			output("`3Oliver `7gets up from his stool and moves towards the beautiful necklace draped around a mannequin neck.");
			output("Silky strands of silver fall in an elegant V, with pearls and diamonds in a cluster at the bottom.`n`n");
			output("\"`&I see ye looking at the necklace with the diamonds and pearls!`7\" he says.`n`n");
			output("\"`&It's so simple, and yet so elegant, nay?");
			output("It's a true show piece, this one.");
			break;
		case "bracelet":
			output("`3Oliver `7moves from behind his workbench and approaches the copper bracelet studded with crystals.");
			output("As you look closer, you see the colors emerge.`n`n");
			output("\"`&Ye see the twinkles, I can see!`7\" he says.`n`n");
			output("\"`&Not just white, but green, yellow, blue in this one.");
			output("I keep these for special customers!`7\" he says with a wink.`n`n\"`&");
			break;
		case "ring":
			output("`3Oliver `7sees you eyeing the ring, and he moves over to show you more.`n`n");
			output("\"`&What ye have there's the finest gold in these lands!");
			output("The emerald, oh, such an emerald that even the king wouldst think fine!`7\" he says.`n`n");
			output("\"`&My gems are specially sourced, you know.");
			output("Other jewelers can't all claim such a special thing!");
			break;
		}

		$buycost=get_module_setting($type);
		addnav("Purchase");
		output("It can be yours, for `%%s`& gems.`7\"`n`n",$buycost);
		if ($session['user']['gems'] >= $buycost) {
			addnav("Buy This Item",
					"runmodule.php?module=jeweler&op=buy&type=".$type);
		}
		addnav("Shop");
		addnav("Continue Looking", "runmodule.php?module=jeweler");
	} elseif ($op=="buy") {
		set_module_pref($type."held", 1);
		$buycost=get_module_setting($type);
		$session['user']['gems'] -= $buycost;
		debuglog("spent " . $buycost . " gems on a $type");
		$totalheld ++ ;
		set_module_pref("totalheld",$totalheld);
		output("`7You hand `3Oliver `7the gems and take the %s from him with a smile, putting it on and admiring yourself in the mirror in front of you.",translate_inline($type));
		addnav("Shop");
		addnav("Continue Looking", "runmodule.php?module=jeweler");
	} elseif ($op=="sell") {
		output("`3Oliver `7picks up the %s and eyes you carefully.",translate_inline($type));
		output("\"`&Are ye sure ye wish to sell it back to me?");
		output("I have a few of them now, and can't offer ye the same price you paid.`7\"`n`n");
		output("`7He examines the %s, then counts out some gems into a pile.",translate_inline($type));

		$sellcost = round(get_module_setting($type) * $sellpercent * .01);
		output("`7You consider his offer of %s gems.",$sellcost);
		addnav("Sell");
		addnav("Confirm Sale",
				"runmodule.php?module=jeweler&op=confirm&type=".$type);
		addnav("Shop");
		addnav("Continue Looking", "runmodule.php?module=jeweler");
	} elseif ($op == "confirm") {
		$sellcost = round(get_module_setting($type) * $sellpercent * .01);
		$session['user']['gems'] += $sellcost;
		set_module_pref($type."held", 0);
		debuglog("received " . $sellcost . " gems refund for a $type");
		$totalheld-- ;
		set_module_pref("totalheld",$totalheld);
		output("`7You hand `3Oliver `7your %s and take the tidy pile of gems from him with a smile, placing them in your purse.",translate_inline($type));
		addnav("Shop");
		addnav("Continue Looking", "runmodule.php?module=jeweler");
	}
	villagenav();
	page_footer();
}

?>

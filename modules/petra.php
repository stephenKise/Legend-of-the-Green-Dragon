<?php
// addnews ready
// translator ready
// mail ready

/* Petra the Inker */
/* ver 1.0 */
/* originally by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 90% of it actually fixed up by JT Traub */

/* 23 Aug 2004 */

// This module checks for a feather given to the player by Lauri
// in the Kitchen of DOOM module, however, you do not need to have
// the kitchen module installed for Petra to function.

// 3rd Sept ver 1.1 Also interfaces with Matthias, the Astute

// v1.3 (dying) main changes
// =========================
// - added hooks "petraavail", "petraadded", "petradescr", and "petracolor",
//   to allow modules to easily make other tattoos available
// - added instaheal! option for testing purposes
// - set new interpretation convention for tatname pref variable
// - added option to output tattoo description
//   - credit goes to atrus for the idea
// - added tatpower pref variable and automagic recalculation code
// - allow tatnumber to be float, as long as it is not negative

// v1.31 (dying) main change
// =========================
// - compressed output of tattoo inventory in character biography to one line
//   and changed the hook used for output from "bioinfo" to "biostat"

// interpretation convention for tatname pref variable as of v1.3
// ==============================================================
// - !isset($tatname['t1']) : player does not have tattoo t1
// - $tatname['t1'] == x
//   - where is_int(x)
//        or is_float(x)    : player has tattoo t1, worth x under tatnumber
//   - where x == "hidden"  : player has tattoo t1, but it is now part of a set,
//                            so it won't show up in the character biography,
//                            and is not counted towards tatnumber
//   - where x == "unavail" : player does not have tattoo t1,
//                            and won't be able to obtain it either
// - tattoo t1 cannot be obtained if isset($tatname['t1'])
// - examples:
//   - if tattoo t1 is worth 0, tattoo t2 is worth 3, tattoo t3 is only
//     available when tattoo t1 and tattoo t2 are obtained, and tattoos
//     t1, t2, and t3 together form the tattoo set t4, which is worth 5,
//     then if the player has tattoos t1 and t2, but neither t3 nor t4, then:
//     - $tatname['t1'] == 0
//     - $tatname['t2'] == 3
//     - $tatname['t3'] is undefined
//     - $tatname['t4'] is undefined
//     - character biography only shows tattoos t1 and t2
//     - total contribution to tatnumber is 3.
//   - if the tattoos t1, t2, and t3 are no longer available once the player
//     gets the set t4, then if the player then gets tattoo t3, then the
//     description for tattoo t3 may be outputted during the tattoo process,
//     and:
//     - $tatname['t1'] == "hidden"
//     - $tatname['t2'] == "hidden"
//     - $tatname['t3'] == "hidden"
//     - $tatname['t4'] == 5
//     - character biography only shows tattoo set t4
//     - total contribution to tatnumber is 5.

// how to add tattoos via a module
// ===============================
// the only hook that is really needed is hook "petraavail".
// all the other hooks are just additional options.
// the module additionaltattoos.php is a decent demo.
//
// hook "petraavail":
// this activates when the player is looking at the list of tattoos available.
// add the appropriate nav if the tattoo in question is available to the player.
// remember to set $args['canbuy'] to 1 if the tattoo is available, where $args
// is the array of arguments that the hook passes to the module.  functions
// petra_addnavifavail() and petra_addnav() defined below may be useful.
//
// hook "petraadded":
// this hook is defined for modules that wish to mess around with tatname.
// it is activated when the player gets a new tattoo.  $args['tattoos'] is
// passed to the module, where $args['tattoos']['t1'] should be set or unset
// to affect $tatname['t1'].  note that both tatnumber and tatpower are
// automagically recalculated after this hook.
//
// hook "petradescr":
// this hook is also activated when the player gets a new tattoo.  have the
// module set $args['tattoodescr'] to a description if $args['tname'] is the
// name of a tattoo the module has made available.
//
// hook "petracolor":
// this hook is activated when someone views the character biography of
// a player with tattoos.  have the module set $args['colortat'] to a
// appropriate colored string if $args['tname'] is the name of a tattoo the
// module has made available.  note that, technically, the string doesn't
// have to be a colored version of the tattoo name.

require_once("lib/villagenav.php");
require_once("lib/http.php");

function petra_getmoduleinfo(){
	//added 2 lines to settings for custom tats....
	//plus code in 2 other places (marked below)
	$info = array(
		"name"=>"Petra the Tattoo Artist",
		"version"=>"1.31",
		"author"=>"Shannon Brown and dying<br>Custom Tats added by `#Lonny Luberts",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"Petra the Tattoo Artist - Settings,title",
			"healnumber"=>"Number of days required to heal a tattoo,range,4,50,1|20",
			"cost"=>"Cost in gems per tattoo,range,2,30,1|10",
			"petraloc"=>"Where does the Petra appear,location|".getsetting("villagename", LOCATION_FIELDS),
			"customtat1"=>"Custom Tattoo 1,text|hummingbird",
			"customtat1color"=>"Custom Tattoo 1 Color Version,text|`!hummingbird",
			"customtat1descr"=>"Custom Tattoo 1 Description,text|",
		),
		"prefs"=>array(
			"Petra the Tattoo Artist User Preferences,title",
			"tatnumber"=>"Effective number of tattoos,viewonly|0",
			"tatpower"=>"Power of tattoos,viewonly|0",
			"lasttat"=>"Days remaining for a player to heal,int|0",
			"tatname"=>"List of tattoos,viewonly|none",
		)
	);
	return $info;
}

function petra_install(){
	module_addhook("newday");
	module_addhook("biostat");
	module_addhook("village");
	module_addhook("changesetting");
	return true;
}

function petra_uninstall(){
	return true;
}

function petra_colortat($tname) {
	$custom1 = get_module_setting('customtat1');
	switch ($tname) {
	case 'heart': return "`4heart"; break;
	case 'daisy': return "`!daisy"; break;
	case 'rose': return "`\$rose"; break;
	case 'skull': return "`)skull"; break;
	case 'symbol': return "`%symbol"; break;
	case 'star': return "`^star"; break;
	case 'swan': return "`&swan"; break;
	case 'snake': return "`@snake"; break;
	case 'tiger': return "`7t`Qi`7g`Qe`7r"; break;
	//custom tat code
	case $custom1: return get_module_setting('customtat1color'); break;
	default:
		$retdargs = modulehook("petracolor", array('tname' => $tname));
		if (isset($retdargs['colortat'])) return $retdargs['colortat'];
		return "`)$tname";
	}
}

function petra_tattoodescr($tname) {
	$custom1 = get_module_setting('customtat1');
	$alreadytranslated = 0;
	$str = 0;
	switch ($tname) {
	case 'heart':
		/* [add heart description] */;
		break;
	case 'daisy':
		/* [add daisy description] */;
		break;
	case 'rose':
		/* [add rose description] */;
		break;
	case 'skull':
		/* [add skull description] */;
		break;
	case 'symbol':
		/* [add symbol description] */;
		break;
	case 'star':
		/* [add star description] */;
		break;
	case 'swan':
		/* [add swan description] */;
		break;
	case 'snake':
		/* [add snake description] */;
		break;
	case 'tiger':
		/* [add tiger description] */;
		break;
	case $custom1:
		$str = get_module_setting('customtat1descr');
		break;
	default:
		$retdargs = modulehook("petradescr", array('tname' => $tname));
		$str = (isset($retdargs['tattoodescr'])?$retdargs['tattoodescr']:"");
		$alreadytranslated = 1;
		break;
	}
	if (!$alreadytranslated && $str)
		$str = translate_inline($str);
	return $str;
}

function petra_dohook($hookname,$args){
	global $session;

	switch($hookname){
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("petraloc")) {
				set_module_setting("petraloc", $args['new']);
			}
		}
		break;
	case "newday":
		$lasttat=get_module_pref("lasttat");
		if ($lasttat>1) {
			$maxtime = get_module_setting("healnumber");
			// See if they are still hurting.
			// Gradually hurt less over time.
			$mult = 0;
			if ($lasttat > $maxtime*.95) {
				$mult = 0.7;
			} elseif ($lasttat > $maxtime*.8) {
				$mult = 0.8;
			} elseif ($lasttat > $maxtime*.7) {
				$mult = 0.9;
			} elseif ($lasttat > $maxtime*.66) {
				$mult = 0.95;
			}

			output("`n`6Your tattoo is gradually healing.`n");
			// adjust their hitpoints
			if ($mult) {
				output("`&You `\$lose`& some hitpoints due to the pain.`n");
				$session['user']['hitpoints'] *= $mult;
				if ($session['user']['hitpoints'] <= 1) {
					$session['user']['hitpoints'] = 1;
				}
			}

			// decrement the healing days remaining (time since the tattoo
			// was bought).
			$lasttat--;
			set_module_pref("lasttat",$lasttat);
		} elseif (get_module_pref("lasttat")==1) {
			// tattoo is healed
			set_module_pref("lasttat",0);
			output("`n`6Your tattoo has completely healed.`n");
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("petraloc")){
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("Petra's Tattoo Parlor","runmodule.php?module=petra");
		}
		break;
	case "biostat":
		$tname = get_module_pref("tatname", "petra", $args['acctid']);
		if (!$tname) break;
		$biotatname = @unserialize($tname);
		if (!is_array($biotatname)) {
			if ($tname == "none") $biotatname = array();
			else $biotatname = array($tname=>1);
			set_module_pref("tatname", serialize($biotatname), "petra",
					$args['acctid']);
		}
		if (count($biotatname)) {
			$displaystring = "";
			foreach($biotatname as $name=>$val) {
				if (($val !== "hidden") && ($val !== "unavail")) {
					$translatedtattoo = translate_inline(petra_colortat($name));
					$displaystring = sprintf("%s`@%s%s", $displaystring, $displaystring == "" ? "" : ", ", $translatedtattoo);
				}
			}
			if ($displaystring != "") {
				output("`^Tattoos: ");
				output_notl($displaystring . "`n");
			}
		}
		break;
	}
	return $args;
}

function petra_run() {
	global $session;
	$op = httpget("op");
	$cost=get_module_setting("cost");
	$costfeather=$cost-1;
	$lasttat=get_module_pref("lasttat");
	$feather=get_module_pref("feather","kitchen");

	$tname = get_module_pref("tatname");
	if ($tname == "none") $curtats = array();
	else $curtats = unserialize($tname);
	if (!is_array($curtats)) {
		if ($tname == "none") $curtats = array();
		else $curtats = array($tname=>1);
		set_module_pref("tatname", serialize($curtats));
	}

	page_header("Tattoo Parlor");
	output("`&`c`bPetra, the Ink Artist`b`c");

	if ($op == "" && $lasttat==0) {
		output("`7A tiny elf looks up from a sketch pad and smiles at you as you enter the shop.");
		output("On every spare surface are intricate designs of animals, symbols, and words, in every color of the rainbow.`n`n");
		output("Noting your interest, he calls for Petra, who emerges from behind a screen.`n`n");
		output("`7Petra's blue hair surrounds her like an aura. \"`&Good day, dear %s`&! So lovely to see you. What beautiful work of art can I grace you with today?`7\"`n`n", $session['user']['name']);
		output("`7The elf gets back to his drawing as you gaze about and try to decide.");
		// different tattoos go here
		$canbuy = 0;

		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"heart", translate_inline("Heart"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"daisy", translate_inline("Daisy"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"rose", translate_inline("Rose"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"skull", translate_inline("Skull"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"symbol", translate_inline("Symbol"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"star", translate_inline("Star"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"swan", translate_inline("Swan"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"snake", translate_inline("Snake"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				"tiger", translate_inline("Tiger"));
		$canbuy = petra_addnavifavail($curtats, $canbuy,
				get_module_setting('customtat1'),
				translate_inline(ucfirst(get_module_setting('customtat1'))));

		$retdargs = modulehook("petraavail",
				array('tattoos' => $curtats, 'canbuy' => $canbuy));
		$canbuy = $retdargs['canbuy'];

		if ($canbuy) {
			addnav("Don't get a tattoo today",
					"runmodule.php?module=petra&op=nope");
		} else {
			output("`7You browse through the designs, but see that you already have one of each of the designs adorning your body.");
			output("`7Perhaps Petra will come up with some new designs soon, but for now, you will have to be content with the ink you already wear.`n`n");
			output("`7Sadly, you return to town.");
			villagenav();
		}
	} elseif ($op == "instaheal") {
		output("The rawness of your tattoo, and its pain, fades, as if by magic!");
		set_module_pref("lasttat",0);
		addnav("Return to Parlor", "runmodule.php?module=petra");
	} elseif ($op == "") {
		output("`7You step in to admire the lovely designs on the walls, but then you think about the pain of the last inking you had done.`n`n");
		output("Perhaps one day, you can think about getting another.");
		if ($session['user']['superuser'] & SU_DEVELOPER) {
			addnav("InstaHeal!", "runmodule.php?module=petra&op=instaheal");
		}
		villagenav();
	} elseif ($op == "yes") {
		$tatname = httpget("des");
		output("`7Petra nods with a smile. \"`&Of course! It's a lovely design, isn't it? I quite like it myself, in fact, I may just get my assistant to ink it on my...");
		if ($feather==1 && $session['user']['gems']>=$costfeather) {
			output("Oh! That feather in your hair, it's lovely! I'll do the %s for 1 gem less, if you'll give me that feather!\"`n`n", translate_inline($tatname));
			addnav(array("Pay %s gems", $cost),"runmodule.php?module=petra&op=full&des=$tatname");
			addnav(array("Give %s gems and the feather", $costfeather),"runmodule.php?module=petra&op=give&des=$tatname");
			addnav("Don't get a tattoo today","runmodule.php?module=petra&op=nope");
		} elseif ($session['user']['gems']>=$cost) {
			output("Oh! Never mind! Now, let's get this %s organized. The price is %s gems.\"`n`n", translate_inline($tatname), $cost);
			addnav(array("Pay %s gems", $cost),"runmodule.php?module=petra&op=full&des=$tatname");
			addnav("Don't get a tattoo today","runmodule.php?module=petra&op=nope");
		} else {
			output("Oh! Never mind! Now, let's get this %s organized. The price is %s gems.`n`n", translate_inline($tatname), $cost);
			output("`7Petra stops as she sees your face fall.`n`n");
			output("\"`&You don't have %s gems, do you? Oh dear, I am sorry. Perhaps you'll come back another time?\"`n`n",$cost);
			output("`7You realize you don't have much choice in the matter.");
			villagenav();
		}
	} elseif ($op == "nope") {
		output("`7You're more than a little afraid of getting a tattoo, and you just want to get out of there.`n`n");
		output("`7Petra thanks you for visiting.");
		villagenav();
	} else {
		$tatname = httpget("des");
		// Cannot do this above or else they will be marked as tattooed
		// even if they couldn't pay.
		$curtats[$tatname] = 1;

		$retdargs = modulehook("petraadded", array('tattoos' => $curtats));
		set_module_pref("tatname",serialize($retdargs['tattoos']));
		petra_calculate();
		if ($op == "give") {
			set_module_pref("feather",0,"kitchen");
			// need to check if module exists on this server
			if (is_module_active("matthias")){
				$astute=get_module_pref("astuteness","matthias");
				$astute--;
				set_module_pref("astuteness",$astute,"matthias");
			}
			$session['user']['gems']-=$costfeather;
			debuglog("spent $costfeather gems and a feather on a tattoo of a $tatname");
		} else {
			$session['user']['gems']-=$cost;
			debuglog("spent $cost gems on a tattoo of a $tatname");
		}

		$session['user']['hitpoints']*=0.2;
		if ($session['user']['hitpoints']<=1) {
			$session['user']['hitpoints']=1;
		}

		// add a bit of randomness to the healing.
		$healnumber= get_module_setting("healnumber");
		$lasttat=$healnumber + e_rand(-3, +3);
		if ($lasttat < 3) $lasttat = 3;

		output("`7Petra motions you to a chair, and you desperately try to relax as she leans over you.");
		output("You can't bear to look as she begins to work.");
		output("You're suffering for the art!`n`n");
		output("`7The work feels like it takes years, as you feel her every movement.");
		output("Finally, she announces that the %s is finished.", translate_inline($tatname));
		output("You survey your reddened skin, and although it is very painful, you're quite pleased with the result.`n`n");
		$tattoodescr = petra_tattoodescr($tatname);
		if ($tattoodescr != "") {
			output($tattoodescr);
			output_notl("`n`n");
		}
		output("`7You're far too sore to move very fast until it heals properly.`n`n");
		output("You `\$lose`7 a lot of your hitpoints!`n`n");
		set_module_pref("lasttat",$lasttat);
		if ($session['user']['superuser'] & SU_DEVELOPER) {
			addnav("InstaHeal!", "runmodule.php?module=petra&op=instaheal");
		}
		villagenav();
	}
	page_footer();
}

function petra_addnavifavail($curtats, $canbuy, $tatname, $tattranslated)
{
	if (!isset($curtats[$tatname])) {
		petra_addnav($tatname, $tattranslated);
		return 1;
	}

	return $canbuy;
}

function petra_addnav($tatname, $tattranslated)
{
	addnav(array("Get %s", $tattranslated),
			"runmodule.php?module=petra&op=yes&des=" . $tatname);
}

function petra_calculate()
{
	$tatlist = @unserialize(get_module_pref("tatname", "petra"));
	$tatnum = 0;
	if (is_array($tatlist)) {
		foreach ($tatlist as $name => $val) {
			if (is_int($val)||is_float($val)) $tatnum += $val;
		}
		if ($tatnum < 0) $tatnum = 0;
	}

	set_module_pref("tatnumber", $tatnum, "petra");
	set_module_pref("tatpower", petra_tatpower($tatnum), "petra");
}

function petra_tatpower($tatnum)
{
	// if $tatnum <= 1, then $tatnum == (2*$tatpower).
	// any part of $tatnum above 1 will contribute to $tatpower
	//    according to the integral of 2/(1+sqrt(x-1)).
	// this function was chosen because 2/(1+sqrt(x-1)) == 2,
	//    it falls off slower than 2/(1+x), and it sets
	//    $tatpower to slightly below 10 when $tatnumber == 10,
	//    so that any modules that assumed that one could only
	//    have ten tattoos can be easily balanced again.
	// this curve will also allow players to get nice immediate benefits
	//    even when they only have one tattoo, and tapers off significant
	//    benefits around the tenth tattoo.
	// note that the integral of 2/(1+sqrt(x-1)) is
	//    4 * ( sqrt(x-1) - log(1+sqrt(x-1)) ).

	// here are some example values of the tatpower function.
	//    [0] 0
	//    [0.25] 0.5
	//    [0.5] 1
	//    [0.75] 1.5
	//    [1] 2
	//    [2] 3.2274112777602
	//    [3] 4.1313599014142
	//    [4] 4.907993075306
	//    [5] 5.6055508453276
	//    [6] 6.246835887521
	//    [7] 6.8450536932834
	//    [8] 7.4087553833544
	//    [9] 7.9438923131707
	//    [10] 8.4548225555204
	//    [12] 9.416603968004
	//    [14] 10.313155602207
	//    [16] 11.157107999666
	//    [18] 11.957379211802
	//    [20] 12.720561642164
	//    [25] 14.496800472654
	//    [30] 16.12475002549
	//    [35] 17.637951445215
	//    [40] 19.058746795597
	//    [45] 20.402943723353
	//    [50] 21.682233833281
	//    [60] 24.07996891722
	//    [70] 26.303589891837
	//    [80] 28.387411419393
	//    [90] 30.355652943086
	//    [100] 32.226185551326
	//    [200] 47.566380975782
	//    [300] 59.540694510137
	//    [400] 69.726615395809
	//    [500] 78.752847108632
	//    [600] 86.947197886643
	//    [700] 94.506628221026
	//    [800] 101.56056609779
	//    [900] 108.19951770055
	//    [1000] 114.48974182977

	if ($tatnum <= 1) {
		if ($tatnum >= 0) return (2*$tatnum);
		else {
			set_module_pref("tatnumber",0);
			return 0;
		}
	}

	$sqrtxm1 = sqrt($tatnum-1);
	return (2 + 4*($sqrtxm1-log(1+$sqrtxm1)));
}

// this function is defined for testing purposes, so that
// anyone who wishes to tweak the petra_tatpower() function
// can easily check how the function of choice behaves
function petra_tatpowerexamplevalues()
{
	for($i=0;$i<1;$i+=.25)
		output("[%s] %s`n", $i, petra_tatpower($i));
	for($i=1;$i<10;$i++)
		output("[%s] %s`n", $i, petra_tatpower($i));
	for($i=10;$i<20;$i+=2)
		output("[%s] %s`n", $i, petra_tatpower($i));
	for($i=20;$i<50;$i+=5)
		output("[%s] %s`n", $i, petra_tatpower($i));
	for($i=50;$i<100;$i+=10)
		output("[%s] %s`n", $i, petra_tatpower($i));
	for($i=100;$i<=1000;$i+=100)
		output("[%s] %s`n", $i, petra_tatpower($i));
}

?>

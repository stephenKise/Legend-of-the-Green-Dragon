<?php
/*
Details:
 * This is a module that allows you to enhance PvP
 * Idea by Perdition
History Log:
 v1.1:
 o Requires Alignment module if the file exists
 v1.2:
 o allow blocking restricted attacks from even showing in list.
 o small cleanup in code logic
// Added per clan objpref, and enum'd "ownclan" to reflect this - Red
 v1.2.1
 o Now checks to make sure bands are valid - kickme
*/
require_once("lib/addnews.php");
require_once("lib/http.php");
require_once("lib/systemmail.php");
require_once("lib/villagenav.php");

function pvpblock_getmoduleinfo(){
	$info = array(
		"name"=>"PvP Block",
		"version"=>"1.2.1",
		"author"=>"`^CortalUX",
		"category"=>"PVP",
		"allowanonymous"=>true,
		"override_forced_nav"=>true,
		"download"=>"core_module",
		"settings"=>array(
			"PvP Block Settings - General,title",
			"ownBand"=>"Can players attack outside of their band?,bool|0",
			"ownClan"=>"Can players attack members of their own clan?,enum,0,Never,1,Always,2,Per Clan|0",
			"mailClan"=>"Mail the clan if player attacks own clan?,bool|1",
			"addnews"=>"Should restricted PVP behavior be announced in the news?,bool|0",
			"showBad"=>"Should restricted targets be listed?,bool|0",
			"PvP Block Settings - Penalties,title",
			"goldLose"=>"How much gold should the player lose?,int|0",
			"buff"=>"How many days of penalty buff for each restricted PVP attempt?,int|5",
			"(these effects will only apply to those who attack those in a lesser DK band than themselves or those who attack users of their own clan),note",
			"PvP Block Settings - DK Bands,title",
			"bandOne"=>"Band one is DK 0 up to what DK?,range,1,100,1|3",
			"bandTwo"=>"Band two is from band one up to what DK?,range,1,100,1|7",
			"bandThree"=>"Band three is from band two up to what DK?,range,1,100,1|12",
			"bandFour"=>"Band four is from band three to up to what DK?,range,1,100,1|20",
			"Band five will be from band four up, note",
			"(IE: if band one is set to 3 then band one is DKs zero to 3),note",
			"(IE: if band one is set to 3 and band two is set to 6 then band two is DKs 4 to 6),note",
		),
		"prefs"=>array(
			"PvP Block - Info,title",
			"msgshow"=>"Number of days to be penalized for breaking the PvP rules?,int|0",
		),
		"prefs-clans"=>array(
			"blockclan"=>"Can members of this clan PvP each other?,bool|1",
		),
	);
	return $info;
}

function pvpblock_install(){
	module_addhook("pvpadjust");
	module_addhook("pvpmodifytargets");
	module_addhook("newday");
	module_addhook("validatesettings");
	module_addhook("faq-toc");
	return true;
}

function pvpblock_uninstall(){
	return true;
}

function pvpblock_dohook($hookname,$args){
	global $session;

	switch($hookname){
	case "validatesettings":
		if($args['bandOne'] >= $args['bandTwo'] || $args['bandTwo'] >= $args['bandThree'] || $args['bandThree'] >= $args['bandFour'])
			$args['validation_error'] = "Bands must increase in dks";
	break;
	case "pvpadjust":
		$sql = "SELECT dragonkills,clanid FROM " . db_prefix("accounts") . " WHERE acctid={$args['acctid']}";
		$result = db_fetch_assoc(db_query($sql));
		if (!pvpblock_bandcheck($session['user']['dragonkills'],
					$result['dragonkills'])) {
			redirect("runmodule.php?module=pvpblock&op=bandrange&id=".$args['acctid']."&dk=".$result['dragonkills']);
		}
		if ((get_module_setting('ownClan')==0 ||
			(get_module_setting('ownClan')==2 &&
			 get_module_objpref("clans",$result['clainid'],"blockclan")==1)) &&
				$result['clanid'] != 0) {
			if ($session['user']['clanid']==$result['clanid']) {
				redirect("runmodule.php?module=pvpblock&op=clan&id=" .
						$args['acctid']);
			}
		}
		if (is_module_active('clanwar')) {
			$sql = "SELECT clanid FROM " . db_prefix("accounts") .
				" WHERE acctid=".$args['acctid'];
			$result = db_fetch_assoc(db_query($sql));
			$cl = get_module_objpref("clans",$result['clanid'],"alignto");
			if (get_module_objpref("clans",
						$session['user']['clanid'],"alignto")==$cl &&
					get_module_setting('warstat', 'clanwar')==2&&$cl!=0) {
				redirect("runmodule.php?module=pvpblock&op=clanside&id=".
						$args['acctid']);
			}
		}
		break;
	case "newday":
		$x = get_module_pref('msgshow');
		if ($args['resurrection'] != 'true') {
			if ($x>0) {
				$x--;
				set_module_pref('msgshow',$x);
			}
		}
		if ($x>0) {
			pvpblock_penalty();
		}
		break;
	case "faq-toc":
		$t = translate_inline("`@Rules on PvP`0");
		output_notl("&#149;<a href='runmodule.php?module=pvpblock&op=faq'>$t</a><br/>", true);
		addnav("","runmodule.php?module=pvpblock&op=faq");
		break;
	case "pvpmodifytargets":
		if (get_module_setting("showBad"))
			break;

		if (get_module_setting("ownBand") &&
				get_module_setting("ownClan")==2 &&
				!is_module_active("clanwar")) {
			break;
		}

		for($i = 0; $i < count($args); $i++) {
			debug("Testing " . $args[$i]['name']);
			if (!pvpblock_bandcheck($session['user']['dragonkills'],
						$args[$i]['dragonkills'])) {
				debug("excluding based on DK");
				$args[$i]['invalid'] = 1;
			}
			if ($args[$i]['clanid'] != 0 &&
				(get_module_setting('ownClan')==0 ||
				 (get_module_setting('ownClan')==2 &&
				  get_module_objpref("clans",
					  $args[$i]['clanid'],"blockclan")==1))) {
				if ($session['user']['clanid']==$args[$i]['clanid']) {
					debug("excluding based on clan");
					$args[$i]['invalid'] = 1;
				}
			}
			if (is_module_active("clanwar")) {
				$cl = get_module_objpref("clans",
						$args[$i]['clanid'],"alignto");
				if (get_module_objpref("clans",
							$session['user']['clanid'],"alignto")== $cl &&
						get_module_setting('warstat', "clanwar")==2 &&
						$cl !=0) {
					$args[$i]['invalid'] = 1;
				}
			}
		}
		break;
	}
	return $args;
}

function pvpblock_run(){
	global $session;
	$op = httpget("op");

	// Make sure users canot cheat.
	if ($op != "faq") {
		require_once("lib/forcednavigation.php");
		do_forced_nav(false, false);
	}

	switch ($op) {
	case "faq":
		pvpblock_faq();
		break;
	case "bandrange":
		pvpblock_bandrange();
		break;
	case "clan":
		pvpblock_clan();
		break;
	case "clanside":
		pvpblock_clanside();
		break;
	}
}

function pvpblock_faq() {
	global $session;
	tlschema("faq");
	popup_header("Rules on PvP");
	$c = translate_inline("Return to Contents");
	rawoutput("<a href='petition.php?op=faq'>$c</a><hr>");
	output("`n`n`c`bRules on PvP`b`c`n");
	output("`^1. Can I attack people in my own clan?`n");
	if (get_module_setting("ownClan")==1) {
		output("`@Yes.. but that's not very cooperative, is it?`n`n");
	} elseif(get_module_setting("ownClan")==0) {
		output("`@Of course not!`n`n");
	}else {
		output("`@It depends on your clan, be sure to check with them.`n`n");
	}
	output("`^2. Are there any Dragon Kill restrictions?`n");
	if (!get_module_setting("ownBand")) {
		output("`@Yes, there are five bands.");
		output("You cannot attack people outside of the band you are in.`n");
		rawoutput("<ol>");
		rawoutput("<li>");
		output("Band One: DK 0-%s", get_module_setting("bandOne"));
		rawoutput("</li><li>");
		output("Band Two: DK %s-%s", get_module_setting("bandOne")+1,
				get_module_setting("bandTwo"));
		rawoutput("</li><li>");
		output("Band Three: DK %s-%s", get_module_setting("bandTwo")+1,
				get_module_setting("bandThree"));
		rawoutput("</li><li>");
		output("Band Four: DK %s-%s", get_module_setting("bandThree")+1,
				get_module_setting("bandFour"));
		rawoutput("</li><li>");
		output("Band Five: DK %s and up", get_module_setting("bandFour")+1);
		rawoutput("</li>");
		rawoutput("</ol>");
		output("`n`@You are in band %s.`n",
				pvpblock_bandcalc($session['user']['dragonkills']));
	} else {
		output("`@Not at the present time.`n`n");
	}

	if (is_module_active("clanwar")) {
		output("`^3. What about when a war is on?`n");
		output("`@When a war is on, you are not allowed to attack users in clans from your own side.`n");
		output("If you do, your clan will be mailed and points will be deducted.`n`n");
	}
	rawoutput("<hr><a href='petition.php?op=faq'>$c</a>");
	popup_footer();
}

function pvpblock_bandrange() {
	global $session;
	$id = httpget("id");
	$dk = httpget("dk");
	$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid=$id";
	$result = db_fetch_assoc(db_query($sql));
	$result = $result['name'];
	page_header("PvP Notice!");
	output("`@You are not allowed to attack users outside of your DK band!`n");
	$me = pvpblock_bandcalc($session['user']['dragonkills']);
	$him =  pvpblock_bandcalc($dk);
	output("You are in DK band `^%s`@ and `&%s`0 is in DK band `^%s`@!",
			$me, $result, $him);
	if ($me > $him) {
		output("`n`%Shame on you! You really could have hurt `&%s`0`%.",
				$result);
		if (is_module_active('alignment')) {
			require_once("modules/alignment.php");
			align("-1");
			output("`n`\$You feel more `bEVIL`b...");
		}
		set_module_pref('msgshow',
				get_module_pref('msgshow')+get_module_setting('buff'));
		pvpblock_penalty();
	}
	if (get_module_setting('addnews')) {
		if($session['user']['sex'] == SEX_MALE) {
			addnews("%s`0`@ tried to attack someone outside of his own DK band!",$session['user']['name']);
		} else {
			addnews("%s`0`@ tried to attack someone outside of her own DK band!",$session['user']['name']);
		}
	}
	villagenav();
	page_footer();
}

function pvpblock_clan() {
	global $session;
	$id = httpget("id");
	$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid=$id";
	$result = db_fetch_assoc(db_query($sql));
	$result = $result['name'];
	page_header("PvP Notice!");
	output("`@You are not allowed to attack users in your own clan!`n");
	if ($session['user']['sex'] == SEX_MALE) {
		$mailsubj = "`^%s`@ has tried to attack someone from his own clan!";
	} else {
		$mailsubj = "`^%s`@ has tried to attack someone from her own clan!";
	}

	if (get_module_setting('mailClan')==1) {
		$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE clanid='{$session['user']['clanid']}'";
		$cn = db_query($sql);
		while ($row = db_fetch_assoc($cn)){
			$subj = "`@Uh oh!";
			$msg = array("$mailsubj\n%s`0`@ tried to attack %s`0`@!",$session['user']['name'],$session['user']['name'],$result['name']);
			systemmail($row['acctid'],$subj,$msg);
		}
	}
	if (get_module_setting('addnews')==1) {
		addnews($mailsubj,$session['user']['name']);
	}
	output("`n`%Shame on you! You really could have hurt `&%s`0`%.",$result);
	if (is_module_active('alignment')) {
		require_once("modules/alignment.php");
		align("-1");
		output("`n`\$You feel more `bEVIL`b...");
	}
	set_module_pref('msgshow',
			get_module_pref('msgshow')+get_module_setting('buff'));
	pvpblock_penalty();
	villagenav();
	page_footer();
}

function pvpblock_clanside() {
	global $session;
	$id = httpget("id");
	$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid=$id";
	$result = db_fetch_assoc(db_query($sql));
	$result = $result['name'];
	page_header("PvP Notice!");
	output("`@You are not allowed to attack users on your own side of the war!`n");
	if ($session['user']['sex'] == SEX_MALE) {
		$mailsubj = "`^%s`@ has tried to attack someone from his own side of the war!";
	} else {
		$mailsubj = "`^%s`@ has tried to attack someone from her own side of the war!";
	}

	if (get_module_setting('mailClan')==1) {
		$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE clanid='{$session['user']['clanid']}'";
		$cn = db_query($sql);
		while ($row = db_fetch_assoc($cn)){
			$subj = "`@Uh oh!";
			$msg = array("$mailsubj\n%s`0`@ tried to attack %s`0`@!",$session['user']['name'],$session['user']['name'],$result['name']);
			systemmail($row['acctid'],$subj,$msg);
		}
	}
	if (get_module_setting('addnews')==1) {
		addnews($mailsubj, $session['user']['name']);
	}
	output("`n`%Shame on you! You really could have hurt `&%s`0`%.",$result);
	if (is_module_active('alignment')) {
		require_once("modules/alignment.php");
		align("-1");
		output("`n`\$You feel more `bEVIL`b...");
	}
	set_module_pref('msgshow',
			get_module_pref('msgshow')+get_module_setting('buff'));
	pvpblock_penalty();
	villagenav();
	page_footer();
}

function pvpblock_bandcalc($dks) {
	$bo = get_module_setting("bandOne");
	$bt = get_module_setting("bandTwo");
	$bh = get_module_setting("bandThree");
	$bf = get_module_setting("bandFour");
	$band = 5;
	if ($dks<=$bo) {
		$band = 1;
	} elseif ($dks<=$bt && $dks>$bo) {
		$band = 2;
	} elseif ($dks<=$bh && $dks>$bt) {
		$band = 3;
	} elseif ($dks<=$bf && $dks>$bh) {
		$band = 4;
	}
	return $band;
}

function pvpblock_bandcheck($mdks=1,$tdks=1) {
	if (get_module_setting("ownBand")) {
		return true;
	} else {
		$mband = pvpblock_bandcalc($mdks);
		$oband = pvpblock_bandcalc($tdks);

		if ($mband==$oband) {
			return true;
		} else {
			return false;
		}
	}
}

function pvpblock_penalty() {
	global $session;
	output("`n`%Your conscience haunts you...");
	if (get_module_setting('goldLose')>0) {
		if ($session['user']['gold']>=get_module_setting('goldLose')) {
			output("`n`^You empty your purse of `@%s`^ gold to assuage your conscience.",get_module_setting('goldLose'));
			$session['user']['gold']-=get_module_setting('goldLose');
		} elseif ($session['user']['goldinbank']>=get_module_setting('goldLose')) {
			output("`n`^You withdraw `@%s`^ gold from the bank, then throw it on the floor to assuage your conscience.",get_module_setting('goldLose'));
			$session['user']['goldinbank']-=get_module_setting('goldLose');
		} elseif ($session['user']['goldinbank']+$session['user']['gold']>=get_module_setting('goldLose')) {
			output("`n`^You withdraw all your money from the bank, then empty your purse of `@%s`^ gold to assuage your conscience.",get_module_setting('goldLose'));
			$session['user']['gold']+=$session['user']['goldinbank'];
			$session['user']['goldinbank']=0;
			$session['user']['gold']-=get_module_setting('goldLose');
		} else {
			output("`n`^You empty your purse completely, to assuage your conscience.",get_module_setting('goldLose'));
			$session['user']['gold']=0;
			$session['user']['goldinbank']=0;
		}
	}
	apply_buff('module-pvpblock',array(
		"name"=>"`@PvP Revenge",
		"rounds"=>60,
		"wearoff"=>"`^You feel better about your attempted PvP...",
		"atkmod"=>0.95,
		"defmod"=>0.95,
		"survivenewday"=>1,
		"roundmsg"=>"`@Your conscience haunts you..."
	));
}
?>

<?php

// mail ready
// addnews ready
// translator ready

function clantrees_getmoduleinfo(){
	$info = array(
		"name"=>"Clan Christmas Trees",
		"version"=>"0.1",
		"author"=>"Sneakabout",
		"category"=>"Clan",
		"download"=>"core_module",
		"settings"=>array(
			"Clan Christmas Trees Settings,title",
			"treebuy"=>"Can you buy a tree?,bool|0",
			"treereward"=>"How many turns do they get the buff for?,int|15",
			"besttree"=>"Clan ID which the best tree?,viewonly",
			"competitive"=>"Is the tree decoration competitive?,bool|0",
			"treebonus"=>"How many tree points do they get a buff at?,int|100",
			"salesman"=>"Name of the Salesman?,|Sativ",
		),
		"prefs"=>array(
			"gotbuff"=>"Has player gained the tree buff today?,bool|0",
		),
		"prefs-clans"=>array(
			"Clan Christmas Trees Clan Preferences,title",
			"havetree"=>"Does this clan have a tree yet?,bool|0",
			"treepoints"=>"How many points are in this tree?,int|0",
			"basetree"=>"What size tree at start?,enum,0,None,10,Small,20,Medium,50,Grand|0",
			"time"=>"How many turns have clan members put in?,int|0",
			"gold"=>"How much gold spent on tinsel?,int|0",
			"gems"=>"How many gems spent on baubles?,int|0",
		),
	);
	return $info;
}

function clantrees_install(){
	module_addhook("village");
	module_addhook("header-clan");
	module_addhook("newday");
	return true;
}

function clantrees_uninstall(){
	return true;
}

function clantrees_decoratenav($havetree, $treebuy)
{
	addnav("Christmas Options");
	if (!$havetree && $treebuy) {
		addnav("Buy a Tree", "runmodule.php?module=clantrees&op=buytree");
	} elseif ($havetree) {
		addnav("Work on Tree","runmodule.php?module=clantrees&op=treetime");
		addnav("Buy Baubles","runmodule.php?module=clantrees&op=treebaubles");
		addnav("Buy Tinsel", "runmodule.php?module=clantrees&op=treetinsel");
	}
	addnav("Clan Options");
}

function clantrees_buff($turns)
{
	$gotbuff=get_module_pref("gotbuff");
	if ($gotbuff) return;
	apply_buff('besttreespirit', array(
		"name"=>"`@Christmas Spirit",
		"rounds"=>$turns,
		"wearoff"=>"The cold around saps your joy.",
		"defmod"=>1.15,
		"roundmsg"=>"You feel the blessing of the season!",
		"schema"=>"module-clantrees"));
	set_module_pref("gotbuff",1);
}

function clantrees_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "village":
		if (get_module_setting("treebuy")) {
			$salesman=get_module_setting("salesman");
			output("`n`&Outside the Clan Halls you can see %s selling Christmas trees to clans!`n",$salesman);
			$besttree=(int)get_module_setting("besttree");

			if ($besttree) {
				$sql = "SELECT clanname FROM " . db_prefix("clans") . " WHERE clanid='$besttree'";
				$res = db_query($sql);
				$row = db_fetch_assoc($res);
				if (get_module_setting("competitive")) {
					output("`nThe biggest, sparkliest, twinkliest tree belongs to the %s clan!`n",$row['clanname']);
					output("Many of the other halls, also sport beautifully decorated trees in their windows.`n");
				} else {
					output("`nMany of the clan hall windows sport beautifully decorated trees!`n");
				}
			}
		}
		break;
	case "header-clan":
		if (!get_module_setting("treebuy")) break;
		$op = httpget('op');
		$clanid = $session['user']['clanid'];
		$detail = httpget('detail');
		if ($op=="" && (!$detail || ($detail == $clanid))) {
			$salesman=get_module_setting("salesman");
			if ($clanid && $session['user']['clanrank'] > CLAN_APPLICANT) {
				$treebuy=get_module_setting("treebuy");
				$hastree = get_module_objpref("clans",$clanid,"havetree");
				$treepoints = get_module_objpref("clans",$clanid,"treepoints");
				$besttree=get_module_setting("besttree");
				$treereward=get_module_setting("treereward");
				$treebonus=get_module_setting("treebonus");
				if ($hastree || $treebuy)
					clantrees_decoratenav($hastree, $treebuy);
				if (!$hastree && $treebuy) {
					output("`@Your clan hall seems somewhat bare this holiday - a christmas tree would cheer things up no end!`0`n`n");
					if ($session['user']['clanrank']==CLAN_LEADER) {
						output("`@Just outside you can see %s offering trees - it could be good for morale to get one for your members!`0`n`n",$salesman);
					}
					break;
				}
				// We don't have a tree, so we cannot do anything else.
				if (!$hastree) break;

				// From here down we know we have a tree, so....
				if ($clanid==$besttree)
					output("`@Your tree is the best tree in the Clan Halls!`n");

				// Handle the bonus in just one place
				if ($treepoints>=$treebonus) {
					output("Your tree is very pretty, and as you look at it your spirit lifts.`n`n");
					clantrees_buff($treereward*($besttree==$clanid?2:1));
					if (get_module_setting("competitive")) {
						// Encourage the non-best to better their tree.
						if ($clanid != $besttree) {
							output("However, it could still do with some brightening...");
							output("Maybe you should spend some time, money or gems in livening it up.`0`n`n");
						}
						else {
							output("However, there is always room to brighten it further.");
							output("Maybe you should spend some time, money or gems in livening it up.`0`n`n");
						}
					}
				}
			}
		}
		break;
	case "newday":
		set_module_pref("gotbuff",0);
		break;
	}
	return $args;
}

function clantrees_runevent(){
}

function clantrees_run(){
	global $session;
	$op = httpget("op");
	$gems = $session['user']['gems'];
	$gold = $session['user']['gold'];
	$clanid = $session['user']['clanid'];
	$salesman=get_module_setting("salesman");

	page_header("Christmas Trees");
	if ($op=="buytree") {
		output("`@You decide that your clan does indeed need cheering up this winter, and go outside to talk to %s about getting a tree for your clan.",$salesman);
		output("Since he is busy talking to another customer, you take a quick look over his stock to see what is available.`n`n");
		output("There seem to be three varieties of tree here - a rather small scraggly group near the entrance look cheap, but nasty.`n");
		output("Near %s himself are some very reasonably sized, and priced, trees which look quite pretty and would look great by the entrance to your hall.`n",$salesman);
		output("However, at the back of the enclosure, guarded by a brutish looking troll, are a couple of grand trees - one of these would be incredible, but they have a price tag to match.`n`n");
		output("As you browse over the selection and decide which ones would be good to have, %s finishes up with the customer and turns to you. Which tree will you have?`n`n",$salesman);
		output("`&Small, Scraggly Tree - `^5,000 Gold `&and `%5 Gems`&.`n");
		output("`@Medium, Pretty Tree - `^10,000 Gold `@and `%10 Gems`@.`n");
		output("`2Large, Grand Tree - `^25,000 Gold `2and `%25 Gems`2.`n`n");
		addnav("Leave the Enclosure","clan.php");

		if ($gold<5000 || $gems < 5) {
			output("`@You realize you don't have enough money on you to purchase any of the trees, even the smallest, and vow to return later with more money.");
		}

		addnav("Buy a Tree");
		if (($gold>=5000) && ($gems>=5)) {
			addnav("Buy A Scraggly Tree",
					"runmodule.php?module=clantrees&op=tree&size=small");
		}
		if (($gold>=10000) && ($gems>=10)) {
			addnav("Buy A Pretty Tree",
					"runmodule.php?module=clantrees&op=tree&size=normal");
		}
		if (($gold>=25000) && ($gems>=25)) {
			addnav("Buy A Grand Tree",
					"runmodule.php?module=clantrees&op=tree&size=grand");
		}
	} elseif ($op=="tree") {
		$size=httpget("size");
		$salesman=get_module_setting("salesman");
		if ($size=="small") {
			output("`@%s nods at your request, and motions to his assistant `\$Sktim`@ to take the little thing into your clan hall.",$salesman);
			output("He carries it one-handed and places it in a pot near your entrance.");
			output("It looks very plain at the moment, and although even the Dwarves of your clan are taller than it, a sense of holiday spirit spreads through the Hall because of its presence.");
			output("Now the members of your clan will have something to decorate - it needs tinsel and baubles to liven it up, as well as goodly time arranging it.");
			$basetree = 10;
			$session['user']['gold']-=5000;
			$session['user']['gems']-=5;
		} elseif ($size=="normal") {
			output("`@%s nods at your request, and motions to his assistant `\$Sktim`@ to take the tree into your clan hall.",$salesman);
			output("Though he struggles a bit with the awkwardness of it, he eventually makes it through the entrance and places it in a goodly-sized pot to one side of the entrance.");
			output("It looks very plain at the moment, but it's a decent size and a sense of holiday spirit spreads through the Hall because of its presence.");
			output("Now the members of your clan will have something to decorate - it needs tinsel and baubles to liven it up, as well as goodly time arranging it.");
			$basetree=20;
			$session['user']['gold']-=10000;
			$session['user']['gems']-=10;
		} elseif ($size=="grand") {
			output("`@%s nods at your request, and motions to his assistant `\$Sktim`@ to take the tree into your clan hall.",$salesman);
			output("He takes one look at the leviathan which you ordered and goes to get backup. Within five minutes the trolls he gathered to help give up - it is too heavy.");
			output("While you are wondering how to get the tree into the hall, a mage wanders by, lecturing his apprentices on the basics of levitation as they scurry after him.");
			output("`\$Sktim`@ quickly takes advantage of this, and within minutes the wizards are all helping move the tree into your hall for levitation practice.");
			output("It looks very plain at the moment, but with the topmost branch scraping the ceiling a sense of holiday spirit spreads through the Hall because of its presence.");
			output("Now the members of your clan will have something to decorate - it needs tinsel and baubles to liven it up, as well as goodly time arranging it.");
			$basetree=50;
			$session['user']['gold']-=25000;
			$session['user']['gems']-=25;
		}
		set_module_objpref("clans", $clanid, "basetree", $basetree);
		set_module_objpref("clans", $clanid, "havetree", 1);
		addnav("Return to the Hall","clan.php");
	} elseif ($op=="treetime") {
		output("`&Though your tree looks very pretty, you reckon that if you spent a while working on it, rearranging the ornaments, it could look a lot better.");
		output("However, you only have so much time in a day.... and other things to do with your time...`n`n");
		output("How many turns do you wish to spend on the tree?");
		// Restrict the player only spending the turns they have!
		$replyinfo = array(
			"replystuff"=>"Turns to spend,range,0,".$session['user']['turns'].",1",
		);
		require_once("lib/showform.php");
	   	rawoutput("<form action='runmodule.php?module=clantrees&op=alter&what=time' method='POST'>");
		showform($replyinfo,array(),true);
		addnav("","runmodule.php?module=clantrees&op=alter&what=time");
		$turns = translate_inline("Spend Turns");
	   	rawoutput("<input name='reply' id='reply' type='submit' class='button' value='$turns'>");
	   	rawoutput("</form>");
	   	addnav("Return to the Hall","clan.php");
	} elseif ($op=="treetinsel") {
		output("`^Though your tree looks very pretty, you reckon that it could do with some more tinsel wrapped around it.");
		output("However, it is expensive... and `7Abigail`^ charges through the nose for it due to the difficulty of supply.`n`n");
		output("How much gold can you spare to give to her?");
		$replyinfo = array(
			"replystuff"=>"Gold to spend,int",
		);
		require_once("lib/showform.php");
	   	rawoutput("<form action='runmodule.php?module=clantrees&op=alter&what=gold' method='POST'>");
	   	showform($replyinfo,array(),true);
		addnav("","runmodule.php?module=clantrees&op=alter&what=gold");
		$spend = translate_inline("Spend Gold");
	   	rawoutput("<input name='reply' id='reply' type='submit' class='button' value='$spend'>");
	   	rawoutput("</form>");
	   	addnav("Return to the Hall","clan.php");
	} elseif ($op=="treebaubles") {
		output("`%Though your tree looks very pretty, you reckon that it could do with some more baubles and ornaments livening it up.");
		output("However, although the baubles are startlingly beautiful, being hand-crafted by a remote tribe of elves (or so `7Abigail`% says), they are also incredibly hard to get, as she only accepts gems in payment for them.`n`n");
		output("How many can you spare to buy the baubles?");
		$replyinfo = array(
			"replystuff"=>"Gems to invest,int",
		);
		require_once("lib/showform.php");
	   	rawoutput("<form action='runmodule.php?module=clantrees&op=alter&what=gems' method='POST'>");
	   	showform($replyinfo,array(),true);
		addnav("","runmodule.php?module=clantrees&op=alter&what=gems");
		$invest = translate_inline("Invest Gems");
	   	rawoutput("<input name='reply' id='reply' type='submit' class='button' value='$invest'>");
	   	rawoutput("</form>");
	   	addnav("Return to the Hall","clan.php");
	} elseif ($op=="alter") {
		$what=httpget("what");
		$howmuch = httppost('replystuff');
		$cur = get_module_objpref("clans", $clanid, $what);
		$field = $what;
		if ($field == "time") $field = 'turns';
		if ($howmuch == 0) {
			output("`&You decide not to spend any %s on the tree right now.",
					translate_inline($what));
		} elseif ($session['user'][$field] < $howmuch) {
			switch($what) {
			case "time":
				output("`&You realise that you don't have that much time to spend, and curse your lack of mathematics.");
				break;
			case "gold":
				output("`7Abigail`^ stares at the small pile of gold in your hand. How can you buy that much tinsel without enough money?");
				break;
			case "gems":
				output("`7Abigail`% stares at the small pile of gems in your hand. How can you buy that many baubles without enough gems?");
				break;
			}
		} else {
			// Everything works;
			set_module_objpref("clans", $clanid, $what, $cur+$howmuch);
			$session['user'][$field]-=$howmuch;
			switch($what) {
			case "time":
				output("`&Though you don't have as much materials, time or space to do as much as you would like to, when you finish you feel a definite improvement has taken place.`n`n");
				output("`@You return to your main Hall, your spirits lifted.");
				break;
			case "gold":
				output("`7Abigail`^ takes the gold you offer and within the hour a nice, albeit smaller than you would like, pile of tinsel has arrived in your clan hall. You hope that someone else has the time to arrange it properly.");
				break;
			case "gems":
				output("`7Abigail`% takes the gems you offer and hands you a couple of beautiful ornaments to hang on your tree. You go back to the clan and hang them up on a few of the branches - maybe someone else will have time to hang them so they look good.");
				break;
			}
			// Recalculate the tree since it changed.
			$points = get_module_objpref("clans", $clanid, "basetree");
			$points += get_module_objpref("clans", $clanid, "gems");
			$points += floor(get_module_objpref("clans", $clanid, "time")/10);
			$points += floor(sqrt(get_module_objpref("clans", $clanid, "gold")/1000));
			set_module_objpref("clans", $clanid, "treepoints", $points);
			$besttree = get_module_setting("besttree");
			if ($points>get_module_objpref("clans", $besttree, "treepoints")) {
				set_module_setting("besttree", $clanid);
				if ($clanid != $besttree) {
					output("`n`n`@Your contribution has made your tree so much better it is now the best one in the Halls! The twinkliness is nearly blinding!");
				}
			}
		}
		addnav("Return to the Hall","clan.php");
	}
	page_footer();
}
?>

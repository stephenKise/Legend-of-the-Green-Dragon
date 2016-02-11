<?php
// translator ready
//addnews ready
// mail ready
function goldmine_getmoduleinfo(){
	$info = array(
		"name"=>"Gold Mine",
		"version"=>"1.0",
		"author"=>"Ville Valtokari",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Goldmine Event Settings,title",
			"alwaystether"=>"Chance the player will tether their mount automatically,range,0,100,1|10",
			"percentgemloss"=>"Percentage of gems lost on death in mine,range,0,100,1|0",
			"percentgoldloss"=>"Percentage of gold lost on death in mine,range,0,100,1|0",
		),
		"prefs-mounts"=>array(
			"Goldmine Mount Preferences,title",
			"entermine"=>"Chance of entering mine,range,0,100,1|0",
			"dieinmine"=>"Chance of dying in the mine,range,0,100,1|0",
			"saveplayer"=>"Chance of saving player in mine,range,0,100,1|0",
			"tethermsg"=>"Message when mount is tethered|",
			"deathmsg"=>"Message when mount dies|",
			"savemsg"=>"Message when mount saves player|",
		),
	);
	return $info;
}

function goldmine_install(){
	module_addeventhook("forest", "return 100;");
	$sql = "DESCRIBE " . db_prefix("mounts");
	$result = db_query($sql);
	while($row = db_fetch_assoc($result)) {
		if ($row['Field'] == "mine_canenter") {
			debug("Migrating mine_canenter for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'goldmine','mounts','entermine',mountid,mine_canenter FROM " . db_prefix("mounts") . " WHERE mine_canenter>0";
			db_query($sql);
			debug("Dropping mine_canenter field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP mine_canenter";
			db_query($sql);
		}
		if ($row['Field'] == "mine_candie") {
			debug("Migrating mine_candie for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'goldmine','mounts','dieinmine',mountid,mine_candie FROM " . db_prefix("mounts") . " WHERE mine_candie>0";
			db_query($sql);
			debug("Dropping mine_candie field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP mine_candie";
			db_query($sql);
		}
		if ($row['Field'] == "mine_cansave") {
			debug("Migrating mine_cansave for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'goldmine','mounts','saveplayer',mountid,mine_cansave FROM " . db_prefix("mounts") . " WHERE mine_cansave>0";
			db_query($sql);
			debug("Dropping mine_cansave field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP mine_cansave";
			db_query($sql);
		}
		if ($row['Field'] == "mine_tethermsg") {
			debug("Migrating mine_tethermsg for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'goldmine','mounts','tethermsg',mountid,mine_tethermsg FROM " . db_prefix("mounts") . " WHERE mine_tethermsg!=''";
			db_query($sql);
			debug("Dropping mine_tethermsg field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP mine_tethermsg";
			db_query($sql);
		}
		if ($row['Field'] == "mine_deathmsg") {
			debug("Migrating mine_deathmsg for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'goldmine','mounts','deathmsg',mountid,mine_deathmsg FROM " . db_prefix("mounts") . " WHERE mine_deathmsg!=''";
			db_query($sql);
			debug("Dropping mine_deathmsg field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP mine_deathmsg";
			db_query($sql);
		}
		if ($row['Field'] == "mine_savemsg") {
			debug("Migrating mine_savemsg for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'goldmine','mounts','savemsg',mountid,mine_savemsg FROM " . db_prefix("mounts") . " WHERE mine_savemsg!=''";
			db_query($sql);
			debug("Dropping mine_savemsg field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP mine_savemsg";
			db_query($sql);
		}
	}
	return true;
}

function goldmine_uninstall(){
	return true;
}

function goldmine_dohook($hookname,$args){
	return $args;
}

function goldmine_runevent($type)
{
	global $session;
	// The only type of event we care about are the forest.
	$from = "forest.php?";

	$hashorse = $session['user']['hashorse'];
	$horsecanenter = 0;
	$horsecandie = 0;
	$horsecansave = 0;
	if ($hashorse) {
		$horsecanenter = get_module_objpref('mounts', $hashorse, 'entermine');
		// See if we automatically tether;
		if (e_rand(1, 100) <= get_module_setting("alwaystether"))
			$horsecanenter = 0;
		if ($horsecanenter) {
			// The mount cannot die or save you if it cannot enter
			$horsecandie = get_module_objpref('mounts', $hashorse, 'dieinmine');
			$horsecansave = get_module_objpref('mounts', $hashorse, 'saveplayer');
		}
		require_once("lib/mountname.php");
		list($mountname, $lcmountname) = getmountname();
	}
	$session['user']['specialinc']="module:goldmine";
	$op = httpget('op');
	if ($op == "" || $op == "search") {
		output("`2You found an old abandoned mine in the depths of the forest.");
		output("There is some old mining equipment nearby.`n`n");
		output("`^As you look around you realize that this is going to be a lot of work.");
		output("So much so in fact that you will lose a forest fight for the day if you attempt it.`n`n");
		output("`^Looking around a bit more, you do notice what looks like evidence of occasional cave-ins in the mine.`n`n");
		addnav("Mine for gold and gems", $from . "op=mine");
		addnav("Return to the forest", $from . "op=no");
	} elseif ($op == "no") {
		output("`2You decide you don't have time for this slow way to gain gold and gems, and so leave the old mine behind and go on your way...`n");
		$session['user']['specialinc']="";
	} elseif ($op=="mine") {
		if ($session['user']['turns']<=0) {
			output("`2You are too tired to mine anymore..`n");
			$session['user']['specialinc']="";
	 	} else {
			// Horsecanenter is a percent, so, if rand(1-100) > enterpercent,
			// tether it.  Set enter percent to 0 (the default), to always
			// tether.
			if (e_rand(1, 100) > $horsecanenter && $hashorse) {
				$msg = get_module_objpref('mounts',$hashorse, 'tethermsg');
				if ($msg) output ($msg);
				else {
					output("`&Seeing that the mine entrance is too small for %s`&, you tether it off to the side of the entrance.`n", $lcmountname);
				}
				// The mount it tethered, so it cannot die nor save the player
				$horsecanenter = 0;
				$horsecandie = 0;
				$horsecansave = 0;
			}
			output("`2You pick up the mining equipment and start mining for gold and gems...`n`n");
			$rand = e_rand(1,20);
			switch ($rand){
			case 1:case 2:case 3:case 4: case 5:
				output("`2After a few hours of hard work you have only found worthless stones and one skull...`n`n");
				output("`^You lose one forest fight while digging.`n`n");
				if ($session['user']['turns']>0) $session['user']['turns']--;
				$session['user']['specialinc']="";
				break;
			case 6: case 7: case 8:case 9: case 10:
				$gold = e_rand($session['user']['level']*5, $session['user']['level']*20);
				output("`^After a few hours of hard work, you find %s gold!`n`n", $gold);
				$session['user']['gold'] += $gold;
				debuglog("found $gold gold in the goldmine");
				output("`^You lose one forest fight while digging.`n`n");
				if ($session['user']['turns']>0) $session['user']['turns']--;
				$session['user']['specialinc']="";
				break;
			case 11: case 12: case 13: case 14: case 15:
				$gems = e_rand(1, round($session['user']['level']/7)+1);
				output("`^After a few hours of hard work, you find `%%s %s`^!`n`n", $gems, translate_inline($gems == 1 ? "gem" : "gems"));
				$session['user']['gems'] += $gems;
				debuglog("found $gems gems in the goldmine");
				output("`^You lose one forest fight while digging.`n`n");
				if ($session['user']['turns']>0) $session['user']['turns']--;
				$session['user']['specialinc']="";
				break;
			case 16: case 17: case 18:
				$gold = e_rand($session['user']['level']*10, $session['user']['level']*40);
				$gems = e_rand(1, round($session['user']['level']/3)+1);
				output("`^You have found the mother lode!`n`n");
				output("`^After a few hours of hard work, you find `%%s %s`^ and %s gold!`n`n", $gems, translate_inline($gems==1?"gem":"gems"), $gold);
				$session['user']['gems'] += $gems;
				$session['user']['gold'] += $gold;
				debuglog("found $gold gold and $gems gems in the goldmine");
				output("`^You lose one forest fight while digging.`n`n");
				if ($session['user']['turns']>0) $session['user']['turns']--;
				$session['user']['specialinc']="";
				break;
			case 19: case 20:
				output("`2After a lot of hard work you believe you have spotted a `&huge`2 `%gem`2 and some `6gold`2.`n");
				output("`2Anxious to be rich, you rear back and slam the pick home, knowing that the harder you hit, the quicker you will be done....`n");
				output("`7Unfortunately, you are quickly done in.`n");
				output("Your over-exuberant hit caused a massive cave in.`n");
				// Find the chance of dying based on race
				$vals = modulehook("raceminedeath");
				$dead = 0;
				$racesave = 1;
				if (isset($vals['racesave']) && $vals['racesave']) {
					if ($vals['schema']) tlschema($vals['schema']);
					$racemsg = translate_inline($vals['racesave']);
					if ($vals['schema']) tlschema();
				}

				if (isset($vals['chance']) && (e_rand(1, 100) < $vals['chance'])) {
					$dead = 1;
					$racesave = 0;
					$racemsg = "";
				}
				if ($dead) {
					// The player has died, see if their horse saves them
					if (isset($horsecansave) && (e_rand(1,100) <= $horsecansave)) {
						$dead = 0;
						$horsesave = 1;
					}
				}
				// If we are still dead, see if the horse dies too.
				$session['user']['specialinc']="";
				if ($dead) {
					if (e_rand(1,100) <= $horsecandie) $horsedead = 1;
					output("You have been crushed under a ton of rock.`n`nPerhaps the next adventurer will recover your body and bury it properly.`n");
					if ($horsedead) {
						$msg = get_module_objpref('mounts', $hashorse, 'deathmsg');
						if ($msg) output ($msg);
						else {
							output("%s`7's bones were buried right alongside yours.", $mountname);
						}
						global $playermount;
						$debugmount = $playermount['mountname'];
						debuglog("lost their mount, a $debugmount, in a mine collapse.");
						$session['user']['hashorse'] = 0;
						if(isset($session['bufflist']['mount']))
							strip_buff("mount");
					} elseif ($hashorse) {
						if ($horsecanenter) {
							output("%s`7 managed to escape being crushed.", $mountname);
							output("You know that it is trained to return to the village.`n");
						} else {
							output("Fortunately you left %s`7 tethered outside.", $lcmountname);
							output("You know that it is trained to return to the village.`n");
						}
					}
					$exp=round($session['user']['experience']*0.1, 0);
					output("At least you learned something about mining from this experience and have gained %s experience.`n`n", $exp);
					output("`3You may continue to play tomorrow`n");
					$session['user']['experience']+=$exp;
					$session['user']['alive']=false;
					$session['user']['hitpoints']=0;
					$gemlost = round(get_module_setting("percentgemloss")/100 * $session['user']['gems'], 0);
					$goldlost = round(get_module_setting("percentgoldloss")/100 * $session['user']['gold'], 0);
					debuglog("lost $goldlost gold and $gemlost gems by dying in the goldmine");
					output("`^%s gold `&and `%%s %s`& were lost when you were buried!", $goldlost, $gemlost, translate_inline($gemlost == 1?"gem":"gems"));
					$session['user']['gold'] -= $goldlost;
					$session['user']['gems'] -= $gemlost;
					addnav("Daily News","news.php");
					addnews("%s was completely buried after becoming greedy digging in the mines.",$session['user']['name']);
				} else {
					if (isset($horsesave) && $horsesave) {
						$msg = get_module_objpref('mounts', $hashorse, 'savemsg');
						if ($msg) output ($msg);
						else {
							output("%s`7 managed to drag you to safety in the nick of time!`n", $mountname);
						}
					} elseif ($racesave) {
						if (isset($racemsg) && $racemsg) output_notl($racemsg);
						else {
							output("Through sheer luck, you manage to escape the cave-in intact!`n");
						}
					}
					output("`n`&Your close call scared you so badly that you cannot face any more opponents today.`n");
					debuglog("`&has lost all turns for the day due to a close call in the mine.");
					$session['user']['turns']=0;
				}
				break;
			}
		}
	}
}

function goldmine_run(){
}
?>

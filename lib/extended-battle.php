<?php
// addnews ready
// mail ready
// translation ready
//

/**
 * Outputs a list of all enemies and the player.
 *
 * @param array $enemies The enemies to be displayed.
 */
function show_enemies($enemies) {
	global $enemycounter, $session;
	foreach ($enemies as $index => $badguy) {
		if ((isset($badguy['istarget']) && $badguy['istarget'] == true) && $enemycounter > 1)
			$ccode = "`#";
		else
			$ccode = "`2";
		if (isset($badguy['hidehitpoints']) && $badguy['hidehitpoints'] == true) {
			$health = "???";
		} else {
			$health = $badguy['creaturehealth'];
		}
		if ($session['user']['alive']){
			output("%s%s%s%s's Hitpoints%s (Level %s): `6%s`0`n",$ccode,(isset($badguy['istarget'])&&$badguy['istarget']&&$enemycounter>1)?"*":"", $badguy['creaturename'],$ccode,$ccode, $badguy['creaturelevel'],$badguy['creaturehealth']>0?$health:translate_inline("`7DEAD`0"));
		}else{
			output("`2%s`2's Soulpoints: `6%s`0`n",$badguy['creaturename'],$badguy['creaturehealth']>0?$health:translate_inline("`7DEFEATED`0"));
		}
	}
	if ($session['user']['alive']){
		output("`2YOUR Hitpoints: `6%s`0`n",$session['user']['hitpoints']);
	}else{
		output("`2YOUR Soulpoints: `6%s`0`n",$session['user']['hitpoints']);
	}
}

/**
 * This function prepares the fight, sets up options and gives hook a hook to change options on a per-player basis.
 *
 * @param array $options The options given by a module or basics.
 * @return array The complete options.
 */
function prepare_fight($options=false) {
	global $companions;
	$basicoptions = array(
		"maxattacks"=>getsetting("maxattacks", 4),
	);
	if (!is_array($options)) {
		$options = array();
	}
	$fightoptions = $options + $basicoptions;
	$fightoptions = modulehook("fightoptions", $fightoptions);

	// We'll also reset the companions here...
	prepare_companions();
	return $fightoptions;
}

/**
 * This functions prepares companions to be able to take part in a fight. Uses global copies.
 *
 */
function prepare_companions() {
	global $companions;
	$newcompanions = array();
	if (is_array($companions)) {
		foreach ($companions as $name => $companion) {
			if (!isset($companion['suspended']) || $companion['suspended'] == false) {
				$companion['used'] = false;
			}
			$newcompanions[$name] = $companion;
		}
	}
	$companions = $newcompanions;
}

/**
 * Suspends companions on a given parameter.
 *
 * @param string $susp The type of suspension
 * @param mixed $nomsg The message to be displayed upon suspending. If false, no message will be displayed.
 */
function suspend_companions($susp, $nomsg=false) {
	global $companions;
	$newcompanions = array();
	$suspended = false;
	if (is_array($companions)) {
		foreach ($companions as $name => $companion) {
			if ($susp) {
				if (isset($companion[$susp]) && $companion[$susp] == true) {
				} else {
					if (isset($companion['suspended']) && $companion['suspended'] == true){
					} else {
						$suspended = true;
						$companion['suspended'] = true;
					}
				}
			}
			$newcompanions[$name] = $companion;
		}
	}

	if ($suspended) {
		$schema = false;
		if ($nomsg === false) {
			$schema = "battle";
			$nomsg = "`&Your companions stand back during this fight!`n";
		}
		if ($nomsg !== true){
			if ($schema) tlschema($schema);
			output($nomsg);
			if ($schema) tlschema();
		}
	}
	$companions = $newcompanions;
}

/**
 * Enables suspended companions.
 *
 * @param string $susp The type of suspension
 * @param mixed $nomsg The message to be displayed upon unsuspending. If false, no message will be displayed.
 */
function unsuspend_companions($susp, $nomsg=false) {
	global $companions;
	$notify = false;
	$newcompanions = array();
	if (is_array($companions)) {
		foreach ($companions as $name => $companion) {
			if (isset($companion['suspended']) && $companion['suspended'] == true) {
				$notify = true;
				$companion['suspended'] = false;
			}
			$newcompanions[$name] = $companion;
		}
	}

	if ($notify) {
		$schema = false;
		if ($nomsg === false) {
			$schema = "battle";
			$nomsg = "`&Your companions return to stand by your side!`n";
		}
		if ($nomsg !== true){
			if ($schema) tlschema($schema);
			output($nomsg);
			if ($schema) tlschema();
		}
	}
	$companions = $newcompanions;
}

/**
 * Automatically chooses the first still living enemy as target for attacks.
 *
 * @param array $localenemies The stack of enemies to find a valid one from.
 * @return array $localenemies The stack with changed targetting.
 */
function autosettarget($localenemies) {
	$targetted = 0;
	if (is_array($localenemies)) {
		foreach ($localenemies as $index=>$badguy) {
			$localenemies[$index] += array("dead"=>false, "istarget"=>false); // This line will add these two indices if they haven't been set.
			if (count($localenemies) == 1)
				$localenemies[$index]['istarget'] = true;
			if ($localenemies[$index]['istarget'] == true && $localenemies[$index]['dead'] == false)
				$targetted++;
		}
	}
	if (!$targetted && is_array($localenemies)) {
		foreach ($localenemies as $index=>$badguy) {
			if ($localenemies[$index]['dead'] == false && (!isset($badguy['cannotbetarget']) || $badguy['cannotbetarget'] === false)) {
				$localenemies[$index]['istarget'] = true;
				$targetted = true;
				break;
			} else {
				continue;
			}
		}
	}
	return $localenemies;
}

/**
 * Based upon the type of the companion different actions are performed and the companion is marked as "used" after that.
 *
 * @param array $companion The companion itself
 * @param string $activate The stage of activation. Can be one of these: "fight", "defend", "heal" or "magic".
 * @return array The changed companion
 */
function report_companion_move($companion, $activate="fight") {
	global $badguy,$session,$creatureattack,$creatureatkmod,$adjustment;
	global $creaturedefmod,$defmod,$atkmod,$atk,$def,$count,$defended,$needtosstopfighting;

	if (isset($companion['suspended']) && $companion['suspended'] == true) {
		return $companion;
	}
	if ($activate == "fight" && isset($companion['abilities']['fight']) && $companion['abilities']['fight'] == true && $companion['used'] == false) {
		$roll = rollcompaniondamage($companion);
		$damage_done = $roll['creaturedmg'];
		$damage_received = $roll['selfdmg'];
		if ($damage_done==0){
			output("`^%s`4 tries to hit %s but `\$MISSES!`n",$companion['name'],$badguy['creaturename']);
		}else if ($damage_done<0){
			output("`^%s`4 tries to hit %s but %s `\$RIPOSTES`4 for `^%s`4 points of damage!`n",$companion['name'],$badguy['creaturename'], $badguy['creaturename'], abs($damage_done));
			$companion['hitpoints']+=$damage_done;
		}else{
			output("`^%s`4 hits %s for `^%s`4 points of damage!`n",$companion['name'],$badguy['creaturename'],$damage_done);
			$badguy['creaturehealth']-=$damage_done;
		}

		if ($badguy['creaturehealth'] >= 0) {
			if ($damage_received==0){
				output("`^%s`4 tries to hit `\$%s`4 but `^MISSES!`n",$badguy['creaturename'], $companion['name']);
			}else if ($damage_received<0){
				output("`^%s`4 tries to hit `\$%s`4 but %s `^RIPOSTES`4 for `^%s`4 points of damage!`n",$badguy['creaturename'], $companion['name'], $companion['name'], abs($damage_received));
				$badguy['creaturehealth']+=$damage_received;
			}else{
				output("`^%s`4 hits `\$%s`4 for `\$%s`4 points of damage!`n",$badguy['creaturename'],$companion['name'],$damage_received);
				$companion['hitpoints']-=$damage_received;
			}
		}
		$companion['used'] = true;
	} else if ($activate == "heal" && isset($companion['abilities']['heal']) && $companion['abilities']['heal'] == true && $companion['used'] == false) {
		// This one will be tricky! We are looking for the first target which can be healed. This can be the player himself
		// or any other companion or our fellow companion himself.
		// But if our little friend is the second companion, all other companions will have been copied to the newenemies
		// array already  ...
		if ($session['user']['hitpoints'] < $session['user']['maxhitpoints']) {
			$hptoheal = min($companion['abilities']['heal'], $session['user']['maxhitpoints'] - $session['user']['hitpoints']);
			$session['user']['hitpoints'] += $hptoheal;
			$companion['used'] = true;
			$msg = $companion['healmsg'];
			if ($msg == "") $msg = "{companion} heals your wounds. You regenerate {damage} hitpoints.";
			$msg = substitute_array("`)".$msg."`0`n", array("{companion}","{damage}"),array($companion['name'],$hptoheal));
			tlschema(isset($companion['schema'])?$companion['schema']:"battle");
			output($msg);
			tlschema();
		} else {
			// Okay. We really have to do this :(
			global $newcompanions;
			$mynewcompanions = $newcompanions;
			if (!is_array($mynewcompanions)) $mynewcompanions = array();
			$healed = false;
			foreach ($mynewcompanions as $myname => $mycompanion) {
				if ($mycompanion['hitpoints'] >= $mycompanion['maxhitpoints'] || $healed || (isset($companion['cannotbehealed']) && $companion['cannotbehealed'] == true)) {
					continue;
				} else {
					$hptoheal = min($companion['abilities']['heal'], $mycompanion['maxhitpoints'] - $mycompanion['hitpoints']);
					$mycompanion['hitpoints'] += $hptoheal;
					$companion['used'] = true;
					$msg = $companion['healcompanionmsg'];
					if ($msg == "") $msg = "{companion} heals {target}'s wounds. {target} regenerates {damage} hitpoints.";
					$msg = substitute_array("`)".$msg."`0`n", array("{companion}","{damage}","{target}"),array($companion['name'],$hptoheal,$mycompanion['name']));
					tlschema(isset($companion['schema'])?$companion['schema']:"battle");
					output($msg);
					tlschema();
					$healed = true;
					$newcompanions[$myname] = $mycompanion;
				}
			}
			if (!$healed) {
				global $companions,$name;
				$mycompanions = $companions;
				$foundmyself = false;
				foreach ($mycompanions as $myname => $mycompanion) {
					if (!$foundmyself || (isset($companion['cannotbehealed']) && $companion['cannotbehealed'] == true)) {
						if ($myname == $name) {
							$foundmyself = true;
						}
						continue;
					} else {
						//There's someone hiding behind us...
						foreach ($mycompanions as $myname => $mycompanion) {
							if ($mycompanion['hitpoints'] >= $mycompanion['maxhitpoints'] || $healed) {
								continue;
							} else {
								$hptoheal = min($companion['abilities']['heal'], $mycompanion['maxhitpoints'] - $mycompanion['hitpoints']);
								$mycompanion['hitpoints'] += $hptoheal;
								$companion['used'] = true;
								$msg = $companion['healcompanionmsg'];
								if ($msg == "") $msg = "{companion} heals {target}'s wounds. {target} regenerates {damage} hitpoints.";
								$msg = substitute_array("`)".$msg."`0`n", array("{companion}","{damage}","{target}"),array($companion['name'],$hptoheal,$mycompanion['name']));
								tlschema(isset($companion['schema'])?$companion['schema']:"battle");
								output($msg);
								tlschema();
								$healed = true;
								$companions[$myname] = $mycompanion;
							} // else	// These
						} // foreach	// are
					} // else			// some
				} // foreach			// totally
			} // if						// senseless
		} // else						// comments.
		unset($mynewcompanions);
		unset($mycompanions);
		$roll = rollcompaniondamage($companion);
		$damage_done = $roll['creaturedmg'];
		$damage_received = $roll['selfdmg'];
		if ($badguy['creaturehealth'] >= 0) {
			if ($damage_received==0){
				output("`^%s`4 tries to hit `\$%s`4 but `^MISSES!`n",$badguy['creaturename'], $companion['name']);
			}else if ($damage_received<0){
				output("`^%s`4 tries to hit `\$%s`4 but %s `^RIPOSTES`4 for `^%s`4 points of damage!`n",$badguy['creaturename'], $companion['name'], $companion['name'], abs($damage_received));
				$badguy['creaturehealth']+=$damage_received;
			}else{
				output("`^%s`4 hits `\$%s`4 for `\$%s`4 points of damage!`n",$badguy['creaturename'],$companion['name'],$damage_received);
				$companion['hitpoints']-=$damage_received;
			}
		}
		$companion['used'] = true;
	} else if ($activate == "defend" && isset($companion['abilities']['defend']) && $companion['abilities']['defend'] == true && $defended == false && $companion['used'] == false) {
		$defended = 1;
		$roll = rollcompaniondamage($companion);
		$damage_done = $roll['creaturedmg'];
		$damage_received = $roll['selfdmg'];
		if ($damage_done==0){
			output("`^%s`4 tries to hit %s but `^MISSES!`n",$companion['name'],$badguy['creaturename']);
		}else if ($damage_done<0){
			output("`^%s`4 tries to hit %s but %s `^RIPOSTES`4 for `^%s`4 points of damage!`n",$companion['name'],$badguy['creaturename'], $badguy['creaturename'], abs($damage_done));
			$companion['hitpoints']+=$damage_done;
		}else{
			output("`^%s`4 hits %s for `\$%s`4 points of damage!`n",$companion['name'],$badguy['creaturename'],$damage_done);
			$badguy['creaturehealth']-=$damage_done;
		}

		if ($badguy['creaturehealth'] >= 0) {
			if ($damage_received==0){
				output("`^%s`4 tries to hit `\$%s`4 but `^MISSES!`n",$badguy['creaturename'], $companion['name']);
			}else if ($damage_received<0){
				output("`^%s`4 tries to hit `\$%s`4 but %s `^RIPOSTES`4 for `^%s`4 points of damage!`n",$badguy['creaturename'], $companion['name'], $companion['name'], abs($damage_received));
				$badguy['creaturehealth']+=$damage_received;
			}else{
				output("`^%s`4 hits `\$%s`4 for `\$%s`4 points of damage!`n",$badguy['creaturename'],$companion['name'],$damage_received);
				$companion['hitpoints']-=$damage_received;
			}
		}
		$companion['used'] = true;
	} else if ($activate == "magic" && isset($companion['abilities']['magic']) && $companion['abilities']['magic'] == true && $companion['used'] == false) {
		$roll = rollcompaniondamage($companion);
		$damage_done = abs($roll['creaturedmg']);
		if ($damage_done==0){
			$msg = $companion['magicfailmsg'];
			if ($msg == "") $msg = "{companion} shoots a magical arrow at {badguy} but misses.";
			$msg = substitute_array("`)".$msg."`0`n", array("{companion}"), array($companion['name']));
			tlschema(isset($companion['schema'])?$companion['schema']:"battle");
			output($msg);
			tlschema();
		}else{
			if (isset($companion['magicmsg'])) {
				$msg = $companion['magicmsg'];
			} else {
				$msg = "{companion} shoots a magical arrow at {badguy} and deals {damage} damage.";
			}
			$msg = substitute_array("`)".$msg."`0`n", array("{companion}","{damage}"), array($companion['name'],$damage_done));
			tlschema(isset($companion['schema'])?$companion['schema']:"battle");
			output($msg);
			tlschema();
			$badguy['creaturehealth']-=$damage_done;
		}
		$companion['hitpoints'] -= $companion['abilities']['magic'];
		$companion['used'] = true;
	}
	if ($badguy['creaturehealth'] <= 0) {
		$badguy['dead'] = true;
		$badguy['istarget'] = false;
		$count = 1;
		$needtosstopfighting = true;
	}
	if ($companion['hitpoints'] <= 0) {
		if (isset($companion['dyingtext']) && $companion['dyingtext']>"") {
			$msg = $companion['dyingtext'];
		} else {
			$msg = "`5Your companion catches his last breath before it dies.";
		}
		tlschema(isset($companion['schema'])?$companion['schema']:"battle");
		output("`5".$msg."`0`n");
		tlschema();
		if (isset($companion['cannotdie']) && $companion['cannotdie'] == true) {
			$companion['hitpoints'] = 0;
		}else{
			return false;
		}
	}

	return $companion;
}

/**
 * Based upon the companion's stats damage values are calculated.
 *
 * @param array $companion
 * @return array
 */

function rollcompaniondamage($companion){
	global $badguy,$creatureattack,$creatureatkmod,$adjustment,$options;
	global $creaturedefmod,$compdefmod,$compatkmod,$buffset,$atk,$def;

	if ($badguy['creaturehealth']>0 && $companion['hitpoints']>0){
		if ($options['type']=='pvp') {
			$adjustedcreaturedefense = $badguy['creaturedefense'];
		} else {
			$adjustedcreaturedefense =
				($creaturedefmod*$badguy['creaturedefense'] /
				 ($adjustment*$adjustment));
		}

		$creatureattack = $badguy['creatureattack']*$creatureatkmod;
		$adjustedselfdefense = ($companion['defense'] * $adjustment * $compdefmod);

		/*
		debug("Base creature defense: " . $badguy['creaturedefense']);
		debug("Creature defense mod: $creaturedefmod");
		debug("Adjustment: $adjustment");
		debug("Adjusted creature defense: $adjustedcreaturedefense");
		debug("Adjusted creature attack: $creatureattack");
		debug("Adjusted self defense: $adjustedselfdefense");
		*/

		while(!isset($creaturedmg) || !isset($selfdmg) || $creaturedmg==0 && $selfdmg==0){
			$atk = $companion['attack']*$compatkmod;
			if (e_rand(1,20)==1 && $options['type'] != "pvp") $atk*=3;
			/*
			debug("Attack score: $atk");
			*/

			$patkroll = bell_rand(0,$atk);
			/*
			debug("Player Attack roll: $patkroll");
			*/

			// Set up for crit detection
			$atk = $patkroll;
			$catkroll = bell_rand(0,$adjustedcreaturedefense);
			/*
			debug("Creature defense roll: $catkroll");
			*/

			$creaturedmg = 0-(int)($catkroll - $patkroll);
			if ($creaturedmg<0) {
				$creaturedmg = (int)($creaturedmg/2);
				$creaturedmg = round($buffset['badguydmgmod'] * $creaturedmg, 0);
			}
			if ($creaturedmg > 0) {
				$creaturedmg = round($buffset['compdmgmod']*$creaturedmg,0);
			}
			$pdefroll = bell_rand(0,$adjustedselfdefense);
			$catkroll = bell_rand(0,$creatureattack);
			/*
			   debug("Creature attack roll: $catkroll");
			   debug("Player defense roll: $pdefroll");
			 */
			$selfdmg = 0-(int)($pdefroll - $catkroll);
			if ($selfdmg<0) {
				$selfdmg=(int)($selfdmg/2);
				$selfdmg = round($selfdmg*$buffset['compdmgmod'], 0);
			}
			if ($selfdmg > 0) {
				$selfdmg = round($selfdmg*$buffset['badguydmgmod'], 0);
			}
		}
	}else{
		$creaturedmg=0;
		$selfdmg=0;
	}
	// Handle god mode's invulnerability
	if ($buffset['invulnerable']) {
		$creaturedmg = abs($creaturedmg);
		$selfdmg = -abs($selfdmg);
	}
	return array("creaturedmg"=>(isset($creaturedmg)?$creaturedmg:0),"selfdmg"=>(isset($selfdmg)?$selfdmg:0));
}

/**
 * Adds a new creature to the badguy array.
 *
 * @param mixed $creature A standard badguy array. If numeric, the corresponding badguy will be loaded from the database.
 */
function battle_spawn($creature) {
	global $enemies, $newenemies, $badguy,$nextindex;
	if (!isset($nextindex)) {
		$nextindex = count($enemies);
	} else {
		$nextindex++;
	}
	if(is_numeric($creature)) {
		$sql = "SELECT * FROM " . db_prefix("creatures") . " WHERE creatureid = $creature LIMIT 1";
		$result = db_query($sql);
		if ($row = db_fetch_assoc($result)) {
			$newenemies[$nextindex] = $row;
			output("`^%s`2 summons `^%s`2 for help!`n", $badguy['creaturename'], $row['creaturename']);
		}
	} else if(is_array($creature)){
		$newenemies[$nextindex] = $creature;
	}
	ksort($newenemies);
}

/**
 * Allows creatures to heal themselves or another badguy.
 *
 * @param int $amount Amount of helath to be restored
 * @param mixed $target If false badguy will heal itself otherwise the enemy with this index.
 */
function battle_heal($amount, $target=false) {
	global $newenemies, $enemies, $badguy;
	if ($amount > 0) {
		if ($target === false) {
			$badguy['creaturehealth']+=$amount;
			output("`^%s`2 heals itself for `^%s`2 hitpoints.", $badguy['creaturename'], $amount);
		} else {
			if (isset($newenemies[$target])) {
				// Target had its turn already...
				if ($newenemies[$target]['dead'] == false) {
					$newenemies[$target]['creaturehealth'] += $amount;
					output("`^%s`2 heal `^%s`2 for `^%s`2 hitpoints.", $badguy['creaturename'], $newenemies[$target]['creaturename'], $amount);
				}
			}else{
				if ($enemies[$target]['dead'] == false) {
					$enemies[$target]['creaturehealth'] += $amount;
					output("`^%s`2 heal `^%s`2 for `^%s`2 hitpoints.", $badguy['creaturename'], $enemies[$target]['creaturename'], $amount);
				}
			}
		}
	}
}

/**
 * Executes the given script or loads the script and then executes it.
 *
 * @param mixed $script If numeric the corresponding script will be loaded from the database, otherwise the script to be executed.
 */
function execute_ai_script($script) {
	global $unsetme;
	if (is_numeric($script)) {
		$script = load_ai_script($script);
	}
	if ($script > "") {
		eval($script);
	}
}

/**
 * Returns an A.I. Script form the database
 *
 * @param int $spriptid The id for the script
  * @return string The script itself. An empty string is returned, if script is found.
 */
function load_ai_script($scriptid) {
	if ($scriptid == 0) {
		return "";
	} else {
		$sql = "SELECT script FROM ".db_prefix("ai")." WHERE scriptid = $scriptid";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		return $row['script'];
	}
}

?>
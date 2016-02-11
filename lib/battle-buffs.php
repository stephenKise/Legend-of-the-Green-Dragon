<?php
// translator ready
// addnews ready
// mail ready
/**
 *
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */

require_once("lib/substitute.php");

function activate_buffs($tag) {
	global $session, $badguy, $count;
	tlschema("buffs");
	$result = array();
	$result['invulnerable'] = 0;
	$result['dmgmod'] = 1;
	$result['compdmgmod'] = 1;
	$result['badguydmgmod'] = 1;
	$result['atkmod'] = 1;
	$result['compatkmod'] = 1;
	$result['badguyatkmod'] = 1;
	$result['defmod'] = 1;
	$result['compdefmod'] = 1;
	$result['badguydefmod'] = 1;
	$result['lifetap'] = array();
	$result['dmgshield'] = array();

	foreach($session['bufflist'] as $key=>$buff) {
		if (array_key_exists('suspended',$buff) && $buff['suspended']) continue;
		if ($buff['schema']) tlschema($buff['schema']);
		if (isset($buff['startmsg'])) {
			if (is_array($buff['startmsg'])) {
				$buff['startmsg'] = str_replace("`%", "`%%", $buff['startmsg']);
				$msg = sprintf_translate($buff['startmsg']);
				$msg = substitute("`5".$msg."`0`n");
				output_notl($msg); //Here it's already translated
			}else{
				$msg = substitute_array("`5".$buff['startmsg']."`0`n");
				output($msg);
			}
			unset($session['bufflist'][$key]['startmsg']);
		}

		// Figure out activate based on buff features
		$activate = false;
		if ($tag == "roundstart") {
			if (isset($buff['regen'])) $activate = true;
			if (isset($buff['minioncount'])) $activate = true;
		} else if ($tag == "offense") {
			if (isset($buff['invulnerable']) && $buff['invulnerable'])
				$activate = true;
			if (isset($buff['atkmod'])) $activate = true;
			if (isset($buff['dmgmod'])) $activate = true;
			if (isset($buff['badguydefmod'])) $activate = true;
			if (isset($buff['lifetap'])) $activate = true;
			if (isset($buff['damageshield'])) $activate = true;
		} else if ($tag == "defense") {
			if (isset($buff['invulnerable']) && $buff['invulnerable'])
				$activate = true;
			if (isset($buff['defmod'])) $activate = true;
			if (isset($buff['badguyatkmod'])) $activate = true;
			if (isset($buff['badguydmgmod'])) $activate = true;
			if (isset($buff['lifetap'])) $activate = true;
			if (isset($buff['damageshield'])) $activate = true;
		}

		// If this should activate now and it hasn't already activated,
		// do the round message and mark it.
		if ($activate && (!array_key_exists('used',$buff) || !$buff['used'])) {
			// mark it used.
			$session['bufflist'][$key]['used'] = 1;
			// if it has a 'round message', run it.
			if (isset($buff['roundmsg'])) {
				if (is_array($buff['roundmsg'])) {
					$buff['roundmsg'] = str_replace("`%", "`%%", $buff['roundmsg']);
					$msg = sprintf_translate($buff['roundmsg']);
					$msg = substitute("`5".$msg."`0`n");
					output_notl($msg); //Here it's already translated
				}else{
					$msg = substitute_array("`5".$buff['roundmsg']."`0`n");
					output($msg);
				}
			}
		}

		// Now, calculate any effects and run them if needed.
		if (isset($buff['invulnerable']) && $buff['invulnerable']) {
			$result['invulnerable'] = 1;
		}
		if (isset($buff['atkmod'])) {
			$result['atkmod'] *= $buff['atkmod'];
			if (isset($buff['aura']) && $buff['aura']) {
				$result['compatkmod'] *= $buff['atkmod'];
			}
		}
		if (isset($buff['badguyatkmod'])) {
			$result['badguyatkmod'] *= $buff['badguyatkmod'];
		}
		if (isset($buff['defmod'])) {
			$result['defmod'] *= $buff['defmod'];
			if (isset($buff['aura']) && $buff['aura']) {
				$result['compdefmod'] *= $buff['defmod'];
			}
		}
		if (isset($buff['badguydefmod'])) {
			$result['badguydefmod'] *= $buff['badguydefmod'];
		}
		if (isset($buff['dmgmod'])) {
			$result['dmgmod'] *= $buff['dmgmod'];
			if (isset($buff['aura']) && $buff['aura']) {
				$result['compdmgmod'] *= $buff['dmgmod'];
			}
		}
		if (isset($buff['badguydmgmod'])) {
			$result['badguydmgmod'] *= $buff['badguydmgmod'];
		}
		if (isset($buff['lifetap'])) {
			array_push($result['lifetap'], $buff);
		}
		if (isset($buff['damageshield'])) {
			array_push($result['dmgshield'], $buff);
		}
		if (isset($buff['regen']) && $tag == "roundstart" && $badguy['istarget'] == true) {
			$hptoregen = (int)$buff['regen'];
			$hpdiff = $session['user']['maxhitpoints'] - $session['user']['hitpoints'];
			// Don't regen if we are above max hp
			if ($hpdiff < 0) $hpdiff = 0;
			if ($hpdiff < $hptoregen) $hptoregen = $hpdiff;
			$session['user']['hitpoints'] += $hptoregen;
			// Now, take abs value just incase this was a damaging buff
			$hptoregen = abs($hptoregen);
			if ($hptoregen == 0) $msg = $buff['effectnodmgmsg'];
			else $msg = $buff['effectmsg'];

			if (is_array($msg)) {
				$msg = sprintf_translate($msg);
				$msg = substitute("`)".$msg."`0`n", array("{damage}"), array($hptoregen));
				output_notl($msg); //Here it's already translated
			}elseif ($msg!="") {
				$msg = substitute_array("`)".$msg."`0`n", array("{damage}"), array($hptoregen));
				output($msg);
			}
			if (isset($buff['aura']) && $buff['aura'] == true) {
				global $companions;
				$auraeffect = (int)round($buff['regen']/3);
				if (is_array($companions) && count($companions)>0 && $auraeffect != 0) {
					foreach ($companions as $name => $companion) {
						$unset = false;
						// Need this for <PHP 5 support
						$companion = &$companions[$name];
						// if a companion is damaged AND ( a companion ist still alive OR ( a companion is unconscious AND it's a healing effect))
						if ($companion['hitpoints'] < $companion['maxhitpoints'] && ($companion['hitpoints'] > 0 || ($companion['cannotdie'] == true && $auraeffect > 0))) {
							$hptoregen = min($auraeffect, $companion['maxhitpoints']-$companion['hitpoints']);
							$companion['hitpoints'] += $hptoregen;
							$msg = substitute_array("`)".$buff['auramsg']."`0`n", array("{damage}","{companion}"),array($hptoregen,$companion['name']));
							output($msg);
							if ($hptoregen < 0 && $companion['hitpoints'] <= 0) {
								if (isset($companion['dyingtext'])) {
									tlschema("battle");
									output($companion['dyingtext']);
									tlschema();
								}
								if (isset($companion['cannotdie']) && $companion['cannotdie'] == true) {
									$companion['hitpoints'] = 0;
								} else {
									$unset = true;
								}
							}
						}
						if (!$unset) $newcompanions[$name] = $companion;
					}
					$companions = $newcompanions; // Seemed to need this...
				}
			}
		}
		if (isset($buff['minioncount']) && $tag == "roundstart" && ((isset($buff['areadamage']) && $buff['areadamage'] == true) || $badguy['istarget'] == true) && $badguy['dead'] == false) {
			$who = -1;
			if (isset($buff['maxbadguydamage']) &&
					$buff['maxbadguydamage']  <> 0) {
				$max = $buff['maxbadguydamage'];
				$min = isset($buff['minbadguydamage'])?$buff['minbadguydamage']:0;
				$who = 0;
			} else {
				$max = isset($buff['maxgoodguydamage'])?$buff['maxgoodguydamage']:0;
				$min = isset($buff['mingoodguydamage'])?$buff['mingoodguydamage']:0;
				$who = 1;
			}
			$minioncounter = 1;
			while ($minioncounter <= $buff['minioncount'] && $who >= 0) {
				$damage = e_rand($min, $max);
				if ($who == 0) {
					$badguy['creaturehealth'] -= $damage;
					if ($badguy['creaturehealth'] <= 0) {
						$badguy['istarget'] = false;
						$badguy['dead'] = true;
						$count = 1;
					}
				} else if ($who == 1) {
					$session['user']['hitpoints'] -= $damage;
				}
				if ($damage < 0) {
					$msg = $buff['effectfailmsg'];
				} else if ($damage == 0) {
					$msg = $buff['effectnodmgmsg'];
				} else if ($damage > 0) {
					$msg = $buff['effectmsg'];
				}
				if (is_array($msg)) {
					$msg = sprintf_translate($msg);
					$msg = substitute("`)".$msg."`0`n", array("{damage}"), array(abs($damage)));
					output_notl($msg); //Here it's already translated
				}else if ($msg>"") {
					$msg = substitute_array("`)".$msg."`0`n", array("{damage}"), array(abs($damage)));
					output($msg);
				}
				if ($badguy['dead'] == true) break;
				$minioncounter++;
			}
		}
		if ($buff['schema']) tlschema();
	}
	tlschema();
	return $result;
}

function process_lifetaps($ltaps, $damage) {
	global $session, $badguy;
	tlschema("buffs");
	foreach($ltaps as $buff) {
		if (isset($buff['suspended']) && $buff['suspended']) continue;
		if ($buff['schema']) tlschema($buff['schema']);
		$healhp = $session['user']['maxhitpoints'] - $session['user']['hitpoints'];
		if ($healhp < 0) {
			$healhp = 0;
		}
		if ($healhp == 0) {
			$msg = isset($buff['effectnodmgmsg'])?$buff['effectnodmgmsg']:"";
		} else {
			if ($healhp > $damage * $buff['lifetap']) {
				$healhp = round($damage * $buff['lifetap'], 0);
			}
			if ($healhp < 0) {
				$healhp = 0;
			}
			if ($healhp > 0) {
				$msg = isset($buff['effectmsg'])?$buff['effectmsg']:"";
			} else if ($healhp == 0) {
				$msg = isset($buff['effectfailmsg'])?$buff['effectfailmsg']:"";
			}
		}
		$session['user']['hitpoints'] += $healhp;
		if (is_array($msg)) {
			$msg = sprintf_translate($msg);
			$msg = substitute("`)".$msg."`0`n", array("{damage}"), array($healhp));
			output_notl($msg); //Here it's already translated
		}else if ($msg>"") {
			$msg = substitute_array("`)".$msg."`0`n", array("{damage}"), array($healhp));
			output($msg);
		}
		if ($buff['schema']) tlschema();
	}
	tlschema();
}

function process_dmgshield($dshield, $damage) {
	global $session, $badguy;
	tlschema("buffs");
	foreach($dshield as $buff) {
		if (isset($buff['suspended']) && $buff['suspended']) {
			continue;
		}
		if ($buff['schema']) {
			tlschema($buff['schema']);
		}
		$realdamage = round($damage * $buff['damageshield'], 0);
		if ($realdamage < 0) {
			$realdamage = 0;
		}
		$msg = "";
		if ($realdamage > 0) {
			if (isset($buff['effectmsg'])) {
				$msg = $buff['effectmsg'];
			}
		} else if ($realdamage == 0) {
			if (isset($buff['effectfailmsg'])) {
				$msg = $buff['effectfailmsg'];
			}
		}
		$badguy['creaturehealth'] -= $realdamage;
		if ($badguy['creaturehealth'] <= 0) {
			$badguy['istarget'] = false;
			$badguy['dead'] = true;
			$count = 1;
		}
		if (is_array($msg)) {
			$msg = sprintf_translate($msg);
			$msg = substitute("`)".$msg."`0`n", array("{damage}"), array($realdamage));
			output_notl($msg); //Here it's already translated
		}else if ($msg>"") {
			$msg = substitute_array("`)".$msg."`0`n", array("{damage}"), array($realdamage));
			output($msg);
		}
		if ($buff['schema']) {
			tlschema();
		}
	}
	tlschema();
}

function expire_buffs() {
	global $session, $badguy;
	tlschema("buffs");
	foreach($session['bufflist'] as $key=>$buff) {
		if (array_key_exists('suspended',$buff) && $buff['suspended']) continue;
		if ($buff['schema']) tlschema($buff['schema']);
		if (array_key_exists('used',$buff) && $buff['used']) {
			$session['bufflist'][$key]['used'] = 0;
			if ($session['bufflist'][$key]['rounds']>0) {
				$session['bufflist'][$key]['rounds']--;
			}
			if ((int)$session['bufflist'][$key]['rounds'] == 0) {
				if (isset($buff['wearoff']) && $buff['wearoff']) {
					if (is_array($buff['wearoff'])) {
						$buff['wearoff'] = str_replace("`%", "`%%", $buff['wearoff']);
						$msg = sprintf_translate($buff['wearoff']);
						$msg = substitute("`5".$msg."`0`n");
						output_notl($msg); //Here it's already translated
					}else{
						$msg = substitute_array("`5".$buff['wearoff']."`0`n");
						output($msg);
					}
				}
				//unset($session['bufflist'][$key]);
				strip_buff($key);
			}
		}
		if ($buff['schema']) tlschema();
	}
	tlschema();
}

?>

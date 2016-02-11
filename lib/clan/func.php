<?php
/**
 * Clan functions
 * 
 * This file contains functions
 * that are specific to the clan
 * system.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
/**
 * Returns the numeric value of the next rank from the given array
 *
 * @param array $ranks The ranks
 * @param int $current The numeric value of the current rank
 * @return int The numeric value of the next rank
 */
function clan_nextrank($ranks,$current) {
	$temp=array_pop($ranks);
	$ranks=array_keys($ranks);
	while (count($ranks)>0) {
		$key=array_shift($ranks);
		if ($key>$current) return $key;
	}
	return 30;

}

/**
 * Returns the numeric value of the previous rank from the given array
 *
 * @param array $ranks The ranks
 * @param int $current The numeric value of the current rank
 * @return int The numeric value of the previous rank
 */
function clan_previousrank($ranks,$current) {
	$temp=array_pop($ranks);
	$ranks=array_keys($ranks);
	while (count($ranks)>0) {
		$key=array_pop($ranks);
		if ($key<$current) return $key;
	}
	return 0;
}
?>

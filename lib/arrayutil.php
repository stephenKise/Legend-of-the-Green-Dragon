<?php
// translator ready
// addnews ready
// mail ready
/**
 * Misc array functions
 * 
 * Contains functions that perform
 * various functions on arrays.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
/**
 * Turns the given parameter into a string
 *
 * If the given parameter is an array or object,
 * it is serialized, and the serialized string is
 * return.
 * 
 * Otherwise, the parameter is cast as a string
 * and returned.
 * 
 * @param mixed $array
 * @return string The parameter converted to a string
 */
function createstring($array){
	if (is_array($array) || is_object($array)){
		$out = serialize($array);
	} else {
		$out = (string)$array;
	}
	return $out;
}

?>

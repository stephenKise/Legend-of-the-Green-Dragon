<?php

/**
 * URL <-> array functions
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
/**
 * Turns an array into an URL argument string
 * 
 * Takes an array and encodes it in key=val&key=val form.
 * Does not add starting ?
 *
 * @param array $array The array to turn into an URL
 * @return string The URL
 */
function arraytourl(array $array): string
{
	$url = "";
	$i = 0;
	foreach ($array as $key => $val) {
        $encodedKey = rawurlencode($key);
        $encodedVal = rawurlencode($val);
		if ($i > 0) $url .= "&";
		$i++;
		$url .= "{$encodedKey}={$encodedVal}";
	}
	return $url;
}
/**
 * Takes a url and returns its arguments in an array
 *
 * @param string $url The URL
 * @return array The arguments from the URL
 */
function urltoarray(string $url): array
{
    $array = [];
    // No arguments in url, return empty array
	if (strpos($url, '?') === false) {
        return $array;
    }
	$url = substr($url, strpos($url, '?') + 1);
	$requestParams = explode('&', $url);
	foreach ($requestParams as $param) {
		[$key, $val] = explode('=', $param);
		$array[urldecode($key)] = urldecode($val);
	}
	return $array;
}

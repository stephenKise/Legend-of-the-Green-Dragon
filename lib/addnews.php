<?php
// addnews ready (duh ;))
// translator ready
// mail ready
/**
 * News functions
 * 
 * Contains functions for adding news
 * items.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
/**
 * Adds a news item for the current user
 *
 * @param $text The text of the news
 * @param $arguments... The sprintf style replacement
 * @param $hidefrombio Set to true to hide from bio
 * @return resource The result resource from the inserting query
 * @see addnews_for_user()
 * @uses addnews_for_user() Function actually adds the news item to the database
 */
function addnews(){
	// Format: addnews($text[, $sprintf_style_replacement1
	//					  [, $sprintf_style_replacement2...]]
	//					  [, $hidefrombio]);
	// We can pass arrays for the sprintf style replacements, which
	// represent separate translation sets in the same format as output().
	// Eg:
	//   addnews("%s defeated %s in %s `n%s","Joe","Hank","the Inn",
	//		   array("\"Your mother smelt of elderberries,\" taunted %s.",
	//				 "Joe"));
	// Note that the sub-translation does need its own %s location in the
	// master output.
	global $session;
	$args = func_get_args();
	array_unshift($args, $session['user']['acctid']);
	return call_user_func_array("addnews_for_user", $args);
}
/**
 * Adds a news item for a user
 *
 * @param $user The id of the user for the news
 * @param $text The text of the news
 * @param $arguments... The sprintf style replacement
 * @param $hidefrombio Set to true to hide from bio
 * @return resource The result resource from the inserting query
 * @see addnews()
 */
function addnews_for_user()
{
	global $translation_namespace;
	// this works just like addnews, except it can be used to add a message
	// to a different player other than the triggering player.
	$args = func_get_args();
	$user = array_shift($args);
	$news = array_shift($args);
	$hidefrombio = false;

	if (count($args)>0){
		$arguments=array();
		foreach($args as $key=>$val){
			if ($key==count($args)-1 && $val===true){
				//if the last argument is true, we're hiding from bio;
				//don't put this in the array.
				$hidefrombio=true;
			}else{
				array_push($arguments,$val);
			}
		}
		$arguments = serialize($arguments);
	}else{
		$arguments="";
	}
	if ($hidefrombio === true) $user = 0;
	$sql = "INSERT INTO " . db_prefix("news") .
		" (newstext,newsdate,accountid,arguments,tlschema) VALUES ('" .
		addslashes($news) . "','" . date("Y-m-d H:i:s") . "'," .
		$user .",'".addslashes($arguments)."','".$translation_namespace."')";
	return db_query($sql);
}

?>

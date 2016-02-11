<?php
// translator ready
// addnews ready
// mail ready

function translator_setup(){
	//Determine what language to use
	if (defined("TRANSLATOR_IS_SET_UP")) return;
	define("TRANSLATOR_IS_SET_UP",true);

	global $language, $session;
	$language = "";
	if (isset($session['user']['prefs']['language'])) {
		$language = $session['user']['prefs']['language'];
	}elseif(isset($_COOKIE['language'])){
		$language = $_COOKIE['language'];
	}
	if ($language=="") {
		$language=getsetting("defaultlanguage","en");
	}

	define("LANGUAGE",preg_replace("/[^a-z]/i","",$language));
}

$translation_table = array();
function translate($indata,$namespace=FALSE){
	if (getsetting("enabletranslation", true) == false) return $indata;
	global $session,$translation_table,$translation_namespace;
	if (!$namespace) $namespace=$translation_namespace;
	$outdata = $indata;
	if (!isset($namespace) || $namespace=="")
		tlschema();

	$foundtranslation = false;
	if ($namespace != "notranslate") {
		if (!isset($translation_table[$namespace]) ||
				!is_array($translation_table[$namespace])){
			//build translation table for this page hit.
			$translation_table[$namespace] =
				translate_loadnamespace($namespace,(isset($session['tlanguage'])?$session['tlanguage']:false));
		}
	}

	if (is_array($indata)){
		//recursive translation on arrays.
		$outdata = array();
		while (list($key,$val)=each($indata)){
			$outdata[$key] = translate($val,$namespace);
		}
	}else{
		if ($namespace != "notranslate") {
			if (isset($translation_table[$namespace][$indata])) {
				$outdata = $translation_table[$namespace][$indata];
				$foundtranslation = true;
				// Remove this from the untranslated texts table if it is
				// in there and we are collecting texts
				// This delete is horrible on very heavily translated games.
				// It has been requested to be removed.
				/*
				if (getsetting("collecttexts", false)) {
					$sql = "DELETE FROM " . db_prefix("untranslated") .
						" WHERE intext='" . addslashes($indata) .
						"' AND language='" . LANGUAGE . "'";
					db_query($sql);
				}
				*/
			} elseif (getsetting("collecttexts", false)) {
				$sql = "INSERT IGNORE INTO " .  db_prefix("untranslated") .  " (intext,language,namespace) VALUES ('" .  addslashes($indata) . "', '" . LANGUAGE . "', " .  "'$namespace')";
				db_query($sql,false);
			}
			tlbutton_push($indata,!$foundtranslation,$namespace);
		} else {
			$outdata = $indata;
		}
	}
	return $outdata;
}

function sprintf_translate(){
	$args = func_get_args();
	$setschema = false;
	// Handle if an array is passed in as the first arg
	if (is_array($args[0])) {
		$args[0] = call_user_func_array("sprintf_translate", $args[0]);
	} else {
		// array_shift returns the first element of an array and shortens this array by one...
		if (is_bool($args[0]) && array_shift($args)) {
			tlschema(array_shift($args));
			$setschema = true;
		}
		$args[0] = str_replace("`%","`%%",$args[0]);
		$args[0] = translate($args[0]);
		if ($setschema) {
			tlschema();
		}
 	}
	reset($args);
	each($args);//skip the first entry which is the output text
	while (list($key,$val)=each($args)){
		if (is_array($val)){
			//When passed a sub-array this represents an independant
			//translation to happen then be inserted in the master string.
			$args[$key]=call_user_func_array("sprintf_translate",$val);
		}
	}
	ob_start();
	$return = call_user_func_array("sprintf",$args);
	$err = ob_get_contents();
	ob_end_clean();
	if ($err > ""){
		$args['error'] = $err;
		debug($err);
	}
	return $return;
}

function translate_inline($in,$namespace=FALSE){
	$out = translate($in,$namespace);
	rawoutput(tlbutton_clear());
	return $out;
}

function translate_mail($in,$to=0){
	global $session;
	tlschema("mail"); // should be same schema like systemmails!
	if (!is_array($in)) $in=array($in);
	//this is done by sprintf_translate.
	//$in[0] = str_replace("`%","`%%",$in[0]);
	if ($to>0){
		$language = db_fetch_assoc(db_query("SELECT prefs FROM ".db_prefix("accounts")." WHERE acctid=$to"));
		$language['prefs'] = unserialize($language['prefs']);
		$session['tlanguage'] = $language['prefs']['language']?$language['prefs']['language']:getsetting("defaultlanguage","en");
	}
	reset($in);
	// translation offered within translation tool here is in language
	// of sender!
	// translation of mails can't be done in language of recipient by
	// the sender via translation tool.

	$out = call_user_func_array("sprintf_translate", $in);

	tlschema();
	unset($session['tlanguage']);
	return $out;
}

function tl($in){
	$out = translate($in);
	return tlbutton_clear().$out;
}

function translate_loadnamespace($namespace,$language=false){
	if ($language===false) $language = LANGUAGE;
	$page = translator_page($namespace);
	$uri = translator_uri($namespace);
	if ($page==$uri)
		$where = "uri = '$page'";
	else
		$where = "(uri='$page' OR uri='$uri')";
	$sql = "
		SELECT intext,outtext
		FROM ".db_prefix("translations")."
		WHERE language='$language'
			AND $where";
/*	debug(nl2br(htmlentities($sql, ENT_COMPAT, getsetting("charset", "ISO-8859-1")))); */
	if (!getsetting("cachetranslations",0)) {
		$result = db_query($sql);
	} else {
		$result = db_query_cached($sql,"translations-".$namespace."-".$language,600);
		//store it for 10 Minutes, normally you don't need to refresh this often
	}
	$out = array();
	while ($row = db_fetch_assoc($result)){
		$out[$row['intext']] = $row['outtext'];
	}
	return $out;
}

$translatorbuttons = array();
$seentlbuttons = array();
function tlbutton_push($indata,$hot=false,$namespace=FALSE){
	global $translatorbuttons;
	global $translation_is_enabled,$seentlbuttons,$session;
	if (!$translation_is_enabled) return;
	if (!$namespace) $namespace="unknown";
	if ($session['user']['superuser'] & SU_IS_TRANSLATOR){
		if (preg_replace("/[ 	\n\r]|`./",'',$indata)>""){
			if (isset($seentlbuttons[$namespace][$indata])){
				$link = "";
			}else{
				$seentlbuttons[$namespace][$indata] = true;
				require_once("lib/sanitize.php");
				$uri = cmd_sanitize($namespace);
				$uri = comscroll_sanitize($uri);
				$link = "translatortool.php?u=".
					rawurlencode($uri)."&t=".rawurlencode($indata);
				$link = "<a href='$link' target='_blank' onClick=\"".
					popup($link).";return false;\" class='t".
					($hot?"hot":"")."'>T</a>";
			}
			array_push($translatorbuttons,$link);
		}
		return true;
	}else{
		//when user is not a translator, return false.
		return false;
	}
}

function tlbutton_pop(){
	global $translatorbuttons,$session;
	if ($session['user']['superuser'] & SU_IS_TRANSLATOR){
		return array_pop($translatorbuttons);
	}else{
		return "";
	}
}

function tlbutton_clear(){
	global $translatorbuttons,$session;
	if ($session['user']['superuser'] & SU_IS_TRANSLATOR){
		$return = tlbutton_pop().join("",$translatorbuttons);
		$translatorbuttons = array();
		return $return;
	}else{
		return "";
	}
}

$translation_is_enabled = true;
function enable_translation($enable=true){
	global $translation_is_enabled;
	$translation_is_enabled = $enable;
}

$translation_namespace = "";
$translation_namespace_stack = array();
function tlschema($schema=false){
	global $translation_namespace,$translation_namespace_stack,$REQUEST_URI;
	if ($schema===false){
		$translation_namespace = array_pop($translation_namespace_stack);
		if ($translation_namespace=="")
			$translation_namespace = translator_uri($REQUEST_URI);
	}else{
		array_push($translation_namespace_stack,$translation_namespace);
		$translation_namespace = $schema;
	}
}

function translator_check_collect_texts()
{
	$tlmax = getsetting("tl_maxallowed",0);

	if (getsetting("permacollect", 0)) {
		savesetting("collecttexts", 1);
	} elseif ($tlmax && getsetting("OnlineCount", 0) <= $tlmax) {
		savesetting("collecttexts", 1);
	} else {
		savesetting("collecttexts", 0);
	}
}

?>

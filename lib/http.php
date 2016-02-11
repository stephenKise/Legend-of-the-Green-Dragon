<?php
// translator ready
// addnews ready
// mail ready
function httpget($var){
	global $HTTP_GET_VARS;

	$res = isset($_GET[$var]) ? $_GET[$var] : false;
	if ($res === false) {
		$res = isset($HTTP_GET_VARS[$var]) ? $HTTP_GET_VARS[$var] : false;
	}
	return $res;
}

function httpallget() {
	return $_GET;
}

function httpset($var, $val,$force=false){
	global $HTTP_GET_VARS;
	if (isset($_GET[$var]) || $force) $_GET[$var] = $val;
	if (isset($HTTP_GET_VARS[$var])) $HTTP_GET_VARS[$var] = $val;
}

function httppost($var){
	global $HTTP_POST_VARS;

	$res = isset($_POST[$var]) ? $_POST[$var] : false;
	if ($res === false) {
		$res = isset($HTTP_POST_VARS[$var]) ?
			$HTTP_POST_VARS[$var] : false;
	}
	return $res;
}

function httppostisset($var) {
	global $HTTP_POST_VARS;

	$res = isset($_POST[$var]) ? 1 : 0;
	if ($res === 0) {
		$res = isset($HTTP_POST_VARS[$var]) ? 1 : 0;
	}
	return $res;
}

function httppostset($var, $val, $sub=false){
	global $HTTP_POST_VARS;
	if ($sub === false) {
		if (isset($_POST[$var])) $_POST[$var] = $val;
		if (isset($HTTP_POST_VARS[$var])) $HTTP_POST_VARS[$var] = $val;
	} else {
		if (isset($_POST[$var]) && isset($_POST[$var][$sub]))
			$_POST[$var][$sub]=$val;
		if (isset($HTTP_POST_VARS[$var]) && isset($HTTP_POST_VARS[$var][$sub]))
			$HTTP_POST_VARS[$var][$sub]=$val;
	}
}

function httpallpost(){
	return $_POST;
}

function postparse($verify=false, $subval=false){
	if ($subval) $var = $_POST[$subval];
	else $var = $_POST;

	reset($var);
	$sql = "";
	$keys = "";
	$vals = "";
	$i = 0;
	while(list($key, $val) = each($var)) {
		if ($verify === false || isset($verify[$key])) {
			if (is_array($val)) $val = addslashes(serialize($val));
			$sql .= (($i > 0) ? "," : "") . "$key='$val'";
			$keys .= (($i > 0) ? "," : "") . "$key";
			$vals .= (($i > 0) ? "," : "") . "'$val'";
			$i++;
		}
	}
	return array($sql, $keys, $vals);
}
?>

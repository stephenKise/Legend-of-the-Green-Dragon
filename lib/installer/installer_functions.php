<?php
function create_db(string $db = 'lotgd') {
	global $mysqli_resource;
	output("`n`2Attempting to create your database...`n");
	$sql = "CREATE DATABASE $db";
	db_query($sql);
	$error = db_error();
	if ($error == '') {
		if (mysqli_select_db($mysqli_resource, $db))
			output("`@Success!`2  I was able to create the database and connect to it!`n");
		else
			output("`\$Database connection error, please check MySQL server.");
	}
	else {
		output("`\$Database error:`2 ");
		rawoutput("<blockquote>$error</blockquote>");
	}
}

$tipid=0;
function tip(){
	global $tipid;
	$tip = translate_inline("Tip");
	output_notl("<div style='cursor: pointer; cursor: hand; display: inline;' onMouseOver=\"tip$tipid.style.visibility='visible'; tip$tipid.style.display='inline';\" onMouseOut=\"tip$tipid.style.visibility='hidden'; tip$tipid.style.display='none';\">`i[ `b{$tip}`b ]`i",true);
	rawoutput("<div class='debug' id='tip$tipid' style='position: absolute; width: 200px; max-width: 200px; float: right;'>");
	$args = func_get_args();
	call_user_func_array("output",$args);
	rawoutput("</div></div>");
	rawoutput("<script language='JavaScript'>var tip$tipid = document.getElementById('tip$tipid'); tip$tipid.style.visibility='hidden'; tip$tipid.style.display='none';</script>");
	$tipid++;
}

function descriptors($prefix = '') {
	require_once('lib/all_tables.php');
	$array = get_all_tables();
	$out = array();
	foreach ($array as $key => $val) {
		$out[$prefix . $key] = $val;
	}
	return $out;
}

//This function is borrowed from the php manual.
function return_bytes($val) {
	$val = trim($val);
	$last = strtolower($val[strlen($val) - 1]);
	switch ($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
		$val *= 1024;
		case 'M':
		$val *= 1024;
		case 'k':
		$val *= 1024;
	}
	return $val;
}
?>
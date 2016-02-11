<?php
// translator ready
// addnews ready
// mail ready

function sanitize($in){
	$out = preg_replace("/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*AabicnHw]/", "", $in);
	return $out;
}

function newline_sanitize($in){
	$out = preg_replace("/`n/", "", $in);
	return $out;
}

function color_sanitize($in){
	$out = preg_replace("/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*Aabi]/", "", $in);
	return $out;
}

function comment_sanitize($in) {
	// to keep the regexp from boinging this, we need to make sure
	// that we're not replacing in with the ` mark.
	$out=preg_replace("/[`](?=[^1234567890!@#\$%^&)~QqRVvGgTteEjJlLxXyYkKpPmM?*Aa])/", chr(1).chr(1), $in);
	$out = str_replace(chr(1),"`",$out);
	return $out;
}

function logdnet_sanitize($in)
{
	// to keep the regexp from boinging this, we need to make sure
	// that we're not replacing in with the ` mark.
	$out=preg_replace("/[`](?=[^1234567890!@#\$%^&)Qqbi])/", chr(1).chr(1), $in);
	$out = str_replace(chr(1),"`",$out);
	return $out;
}

function full_sanitize($in) {
	$out = preg_replace("/[`]./", "", $in);
	return $out;
}

function cmd_sanitize($in) {
	$out = preg_replace("'[&?]c=[[:digit:]-]+'", "", $in);
	return $out;
}

function comscroll_sanitize($in) {
	$out = preg_replace("'&c(omscroll)?=([[:digit:]]|-)*'", "", $in);
	$out = preg_replace("'\\?c(omscroll)?=([[:digit:]]|-)*'", "?", $out);
	$out = preg_replace("'&(refresh|comment)=1'", "", $out);
	$out = preg_replace("'\\?(refresh|comment)=1'", "?", $out);
	return $out;
}

function prevent_colors($in) {
	$out = str_replace("`", "&#0096;", $in);
	return $out;
}

function translator_uri($in){
	$uri = comscroll_sanitize($in);
	$uri = cmd_sanitize($uri);
	if (substr($uri,-1)=="?") $uri = substr($uri,0,-1);
	return $uri;
}

function translator_page($in){
	$page = $in;
	if (strpos($page,"?")!==false) $page=substr($page,0,strpos($page,"?"));
	//if ($page=="runmodule.php" && 0){
	//	//we should handle this in runmodule.php now that we have tlschema.
	//	$matches = array();
	//	preg_match("/[&?](module=[^&]*)/i",$in,$matches);
	//	if (isset($matches[1])) $page.="?".$matches[1];
	//}
	return $page;
}

function modulename_sanitize($in){
	return preg_replace("'[^0-9A-Za-z_]'","",$in);
}

// the following function borrowed from mike-php at emerge2 dot com's post
// to php.net documentation.
//Original post is available here: http://us3.php.net/stripslashes
function stripslashes_array( $given ) {
   return is_array( $given ) ?
	   array_map( 'stripslashes_array', $given ) : stripslashes( $given );
}

// Handle spaces in character names
function sanitize_name($spaceallowed, $inname)
{
	if ($spaceallowed)
		$expr = "([^[:alpha:] _-])";
	else
		$expr = "([^[:alpha:]])";
	return preg_replace($expr, "", $inname);
}

// Handle spaces and color in character names
function sanitize_colorname($spaceallowed, $inname, $admin = false)
{
	if ($admin && getsetting("allowoddadminrenames", 0)) return $inname;
	if ($spaceallowed)
		$expr = "([^[:alpha:]`!@#$%^&\\)12345670 _-])";
	else
		$expr = "([^[:alpha:]`!@#$%^&\\)12345670])";
	return preg_replace($expr, "", $inname);
}

// Strip out <script>...</script> blocks and other HTML tags to try and
// detect if we have any actual output.  Used by the collapse code to try
// and make sure we don't add spurious collapse boxes.
// Also used by the rename code to remove HTML that some admins try to
// insert.. Bah
function sanitize_html($str)
{
	//take out script blocks
	$str = preg_replace("/<script[^>]*>.+<\\/script[^>]*>/", "", $str);
	//take out css blocks
	$str = preg_replace("/<style[^>]*>.+<\\/style[^>]*>/", "", $str);
	//take out comments
	$str = preg_replace("/<!--.*-->/", "", $str);
	$str = strip_tags($str);
	return $str;
}

?>

<?php

function sanitize(string $in): array|string|null
{
    return preg_replace(
        "/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*AabicnHw]/",
        "",
        $in
    );
}

function newline_sanitize(string $in): array|string|null
{
    return preg_replace("/`n/", "", $in);
}

function color_sanitize(string $in): array|string|null
{
    return preg_replace(
        "/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*Aabi]/",
        "",
        $in
    );
}

function comment_sanitize(string $in): array|string
{
    // to keep the regexp from boinging this, we need to make sure
    // that we're not replacing in with the ` mark.
    $out = preg_replace(
        "/[`](?=[^1234567890!@#\$%^&)~QqRVvGgTteEjJlLxXyYkKpPmM?*Aa])/",
        chr(1).chr(1),
        $in
    );
    $out = str_replace(chr(1), "`", $out);
    return $out;
}

function logdnet_sanitize(string $in): array|string
{
    // to keep the regexp from boinging this, we need to make sure
    // that we're not replacing in with the ` mark.
    $out = preg_replace(
        "/[`](?=[^1234567890!@#\$%^&)Qqbi])/",
        chr(1).chr(1),
        $in
    );
    $out = str_replace(chr(1),"`",$out);
    return $out;
}

function full_sanitize(string $in): array|string|null
{
    return preg_replace("/[`]./", "", $in);
}

function cmd_sanitize(string $in): array|string|null
{
    return preg_replace("'[&?]c=[[:digit:]-]+'", "", $in);
}

function comscroll_sanitize(string $in): string
{
    $out = preg_replace("'&c(omscroll)?=([[:digit:]]|-)*'", "", $in);
    $out = preg_replace("'\\?c(omscroll)?=([[:digit:]]|-)*'", "?", $out);
    $out = preg_replace("'&(refresh|comment)=1'", "", $out);
    $out = preg_replace("'\\?(refresh|comment)=1'", "?", $out);
    return $out;
}

function prevent_colors(string $in): array|string
{
    return str_replace("`", "&#0096;", $in);
}

function translator_uri(string $in): array|string|null
{
    $uri = comscroll_sanitize($in);
    $uri = cmd_sanitize($uri);
    if (substr($uri,-1)=="?") $uri = substr($uri,0,-1);
    return $uri;
}

function translator_page($in): string
{
    $page = $in;
    if (strpos($page,"?")!==false) $page=substr($page,0,strpos($page,"?"));
    //if ($page=="runmodule.php" && 0){
    //  //we should handle this in runmodule.php now that we have tlschema.
    //  $matches = array();
    //  preg_match("/[&?](module=[^&]*)/i",$in,$matches);
    //  if (isset($matches[1])) $page.="?".$matches[1];
    //}
    return $page;
}

function modulename_sanitize(string $in): array|string|null
{
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


/**
 *  Replace all html entities, based on your server's charset setting.
 *  This was made to replace all of those needlessly long strings in the core.
 *
 *  @param string $string The string to convert with htmlentities().
 *  @return string $string The string ran through htmlentities().
 */
function htmlent(string $string): string
{
    return htmlentities(
        $string,
        ENT_COMPAT,
        getsetting('charset', 'UTF-8')
    );
}

function is_serialized($value, &$result = null): bool
{
	if (!is_string($value)) {
		return false;
	}

	if ($value === 'b:0;') {
		$result = false;
		return true;
	}

	$length = strlen($value);
	$end = '';

	switch ($value[0]) {
		case 's':
			if ($value[$length - 2] !== '"') {
				return false;
			}
		case 'b':
		case 'i':
		case 'd':
			$end .= ';';
		case 'a':
		case 'O':
			$end .= '}';

			if ($value[1] !== ':') {
				return false;
			}

			switch ($value[2]) {
				case 0:
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
				case 6:
				case 7:
				case 8:
				case 9:
				break;

				default:
					return false;
			}
		case 'N':
			$end .= ';';

			if ($value[$length - 1] !== $end[0]) {
				return false;
			}
		break;

		default:
			return false;
	}

	if (($result = @unserialize($value)) === false) {
		$result = null;
		return false;
	}
	return true;
}
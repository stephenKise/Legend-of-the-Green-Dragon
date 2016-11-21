<?php

function sanitize(string $input): string
{
    return preg_replace(
        "/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*AabicnHw]/",
        "",
        $in
    );
}

function newline_sanitize(string $input): string
{
    return sanitizeNewline($input);
}

function sanitizeNewline(string $input): string
{
    return preg_replace("/`n/", "", $input);
}

function color_sanitize(string $input): string
{
    return sanitizeColor($input);
}

function sanitizeColor(string $input): string
{
    return preg_replace(
        "/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*Aabi]/",
        "",
        $input
    );
}

function comment_sanitize(string $input): string
{
    return sanitizeComment($input);
}

function sanitizeComment(string $input): string
{
    // to keep the regexp from boinging this, we need to make sure
    // that we're not replacing in with the ` mark.
    $out=preg_replace("/[`](?=[^1234567890!@#\$%^&)~QqRVvGgTteEjJlLxXyYkKpPmM?*Aa])/", chr(1).chr(1), $input);
    $out = str_replace(chr(1),"`",$out);
    return $out;
}

function logdnet_sanitize(string $input): string
{
    return sanitizeLogdNet($input);
}

function sanitizeLogdNet(string $input): string
{
    // to keep the regexp from boinging this, we need to make sure
    // that we're not replacing in with the ` mark.
    $out=preg_replace("/[`](?=[^1234567890!@#\$%^&)Qqbi])/", chr(1).chr(1), $input);
    $out = str_replace(chr(1),"`",$out);
    return $out;
}

function full_sanitize(string $input): string
{
    return sanitizeFull($input);
}

function sanitizeFull(string $input): string
{
    return preg_replace("/[`]./", "", $input);
}

function cmd_sanitize(string $input): string
{
    return sanitizeCounter($input);
}

function sanitizeCounter(string $input): string
{
    return preg_replace("'[&?]c=[[:digit:]-]+'", "", $input);
}

function comscroll_sanitize(string $input): string
{
    return sanitizeComscroll($input);
}

function sanitizeComscroll(string $input): string
{
    $out = preg_replace("'&c(omscroll)?=([[:digit:]]|-)*'", "", $input);
    $out = preg_replace("'\\?c(omscroll)?=([[:digit:]]|-)*'", "?", $out);
    $out = preg_replace("'&(refresh|comment)=1'", "", $out);
    $out = preg_replace("'\\?(refresh|comment)=1'", "?", $out);
    return $out;
}

function prevent_colors(string $input): string
{
    return sanitizeBackticks($input);
}

function sanitizeBackticks(string $input): string
{
    return str_replace("`", "&#0096;", $input);
}

function translator_uri(string $input): string
{
    $uri = comscroll_sanitize($input);
    $uri = cmd_sanitize($uri);
    if (substr($uri,-1)=="?") $uri = substr($uri,0,-1);
    return $uri;
}

function translator_page(string $input): string
{
    if (strpos($input,"?")!==false) {
        $input = substr($input, 0, strpos($input, '?'));
    }
    //if ($page=="runmodule.php" && 0){
    //  //we should handle this in runmodule.php now that we have tlschema.
    //  $matches = array();
    //  preg_match("/[&?](module=[^&]*)/i",$in,$matches);
    //  if (isset($matches[1])) $page.="?".$matches[1];
    //}
    return $input;
}

function modulename_sanitize(string $input): string
{
    return sanitizeModuleName($input);
}

function sanitizeModuleName(string $input): string
{
    return preg_replace("'[^0-9A-Za-z_]'","",$input);
}

function stripslashes_array(array $array): array
{
    return stripslashesArray($array);
}

function stripslashesArray(array $array): array
{
    foreach ($array as $key => $value) {
        $array[stripslashes($key)] = stripslashes($value);
    }
   return $array;
}

function sanitize_name(bool $spaceallowed = true, string $input): string
{
    return sanitizeName($input);
}

function sanitizeName(string $input): string
{
    return preg_replace("([^[:alpha:] _-])", "", $input);
}

function sanitize_colorname(bool $spaceallowed, string $input, bool $admin
): string
{
    return sanitizeNameColor($input);
}

function sanitizeNameColor(string $input): string
{
    return preg_replace("([^[:alpha:]`!@#$%^&\\)12345670~ _-])", "", $input);
}


function sanitize_html(string $input): string
{
    return sanitizeHTML($input);
}

function sanitizeHTML(string $input): string
{
    $input = preg_replace("/<script[^>]*>.+<\\/script[^>]*>/", "", $input);
    $input = preg_replace("/<style[^>]*>.+<\\/style[^>]*>/", "", $input);
    $input = preg_replace("/<!--.*-->/", "", $input);
    $input = strip_tags($input);
    return $input;
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
        getsetting('charset', 'ISO-8859-1')
    );
}

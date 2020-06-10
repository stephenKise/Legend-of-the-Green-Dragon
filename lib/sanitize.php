<?php

/**
 * Sanitizes color and styling tags from an input.
 *
 * @param  string $input
 * @return string
 */
function sanitize(string $input): string
{
    return preg_replace(
        "/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*AabicnHw]/",
        "",
        $input
    );
}

/**
 * Alias of sanitizeNewline().
 *
 * @param  string $input
 * @return string
 */
function newline_sanitize(string $input): string
{
    return sanitizeNewline($input);
}

/**
 * Strips newline characters from an input.
 *
 * @param  string $input
 * @return string
 */
function sanitizeNewline(string $input): string
{
    return preg_replace("/`n/", "", $input);
}

/**
 * Alias of sanitizeColor().
 *
 * @param  string $input
 * @return string
 */
function color_sanitize(string $input): string
{
    return sanitizeColor($input);
}

/**
 * Removes only color tags from an input.
 *
 * @param  string $input
 * @return string
 */
function sanitizeColor(string $input): string
{
    return preg_replace(
        "/[`][1234567890!@#\$%^&)~QqRVvGgTtjJeElLxXyYkKpPmM?*Aabi]/",
        "",
        $input
    );
}

/**
 * Alias of sanitizeComment().
 *
 * @param  string $input
 * @return string
 */
function comment_sanitize(string $input): string
{
    return sanitizeComment($input);
}

/**
 * Sanitizes input so that it is suitable for commentary.
 *
 * @param  string $input
 * @return string
 */
function sanitizeComment(string $input): string
{
    return preg_replace(
        "/[`](?=[^1234567890!@#\$%^&)~QqRVvGgTteEjJlLxXyYkKpPmM?*Aa])/",
        "`0",
        $input
    );
}

/**
 * Alias of sanitizeLogdNet().
 *
 * @param  string $input
 * @return string
 */
function logdnet_sanitize(string $input): string
{
    return sanitizeLogdNet($input);
}

/**
 * Removes colors introduced in DragonPrime Edition versions, so that LoGD Net's
 * server list page looks the same across all servers.
 *
 * @param  string $input
 * @return string
 */
function sanitizeLogdNet(string $input): string
{
    $output = preg_replace(
        "/[`](?=[^1234567890!@#\$%^&)Qqbi])/",
        chr(1) . chr(1),
        $input
    );
    return str_replace(chr(1), "`", $output);
}

/**
 * Alias of sanitize().
 *
 * @param  string $input
 * @return string
 */
function full_sanitize(string $input): string
{
    return sanitize($input);
}

/**
 * Alias of sanitizeCounter().
 *
 * @param  string $input
 * @return string
 */
function cmd_sanitize(string $input): string
{
    return sanitizeCounter($input);
}

/**
 * Removes the 'counter' feature in request uri.
 *
 * @param  string $input
 * @return string
 */
function sanitizeCounter(string $input): string
{
    return preg_replace("'[&?]c=[[:digit:]-]+'", "", $input);
}

/**
 * Alias of sanitizeComscroll().
 *
 * @param  string $input
 * @return string
 */
function comscroll_sanitize(string $input): string
{
    return sanitizeComscroll($input);
}

/**
 * Sanitizes the commentary functions from request uri.
 *
 * @param  string $input
 * @return string
 */
function sanitizeComscroll(string $input): string
{
    $out = preg_replace("'&c(omscroll)?=([[:digit:]]|-)*'", "", $input);
    $out = preg_replace("'\\?c(omscroll)?=([[:digit:]]|-)*'", "?", $out);
    $out = preg_replace("'&(refresh|comment)=1'", "", $out);
    $out = preg_replace("'\\?(refresh|comment)=1'", "?", $out);
    return $out;
}

/**
 * Alias of sanitizeBackticks().
 *
 * @param  string $input
 * @return string
 */
function prevent_colors(string $input): string
{
    return sanitizeBackticks($input);
}

/**
 * Removes all backticks, to prevent color and style tags from being used.
 *
 * @param  string $input
 * @return string
 */
function sanitizeBackticks(string $input): string
{
    return str_replace("`", "&#0096;", $input);
}

/**
 * Removes the commentary and counter functions from a request uri.
 *
 * @param  string $input
 * @return string
 */
function translator_uri(string $input): string
{
    $uri = comscroll_sanitize($input);
    $uri = cmd_sanitize($uri);
    if (substr($uri, -1) == "?") {
        $uri = substr($uri, 0, -1);
    }
    return $uri;
}

/**
 * Returns the file name from a request uri.
 *
 * @param  string $input
 * @return string
 */
function translator_page(string $input): string
{
    if (strpos($input, "?") !== false) {
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

/**
 * Alias of sanitizeModuleName().
 *
 * @param  string $input
 * @return string
 */
function modulename_sanitize(string $input): string
{
    return sanitizeModuleName($input);
}

/**
 * Removes any illegal characters from module names.
 *
 * @param  string $input
 * @return string
 */
function sanitizeModuleName(string $input): string
{
    return preg_replace("'[^0-9A-Za-z_]'", "", $input);
}

/**
 * Alias of stripslashesArray().
 *
 * @param  array $array
 * @return array
 */
function stripslashes_array(array $array): array
{
    return stripslashesArray($array);
}

/**
 * Runs through all keys and values of an array and maps them to stripslashes().
 *
 * @param  array $array
 * @return array
 */
function stripslashesArray(array $array): array
{
    foreach ($array as $key => $value) {
        $array[stripslashes($key)] = stripslashes($value);
    }
    return $array;
}

/**
 * Alias of sanitizeName(). Passes $input to sanitizeName().
 *
 * @param  bool   $spaceallowed Deprecated variable left over for legacy code.
 * @param  string $input
 * @return string
 */
function sanitize_name(bool $spaceallowed = true, string $input): string
{
    return sanitizeName($input);
}

/**
 * Cleans a character's base name to not allow special characters or tags.
 *
 * @param  string $input
 * @return string
 */
function sanitizeName(string $input): string
{
    return preg_replace("([^[:alpha:] _-])", "", $input);
}

/**
 * Alias of sanitizeNameColor().
 *
 * @param  bool   $spaceallowed Deprecated variable left over for legacy code.
 * @param  string $input        Passed on to sanitizeNameColor().
 * @param  bool   $admin        Deprecated variable left over for legacy code.
 * @return string
 */
function sanitize_colorname(bool $spaceallowed, string $input, bool $admin): string
{
    return sanitizeNameColor($input);
}

/**
 * Removes any illegal characters from a character's colored name, while leaving
 * color and style tags in tact.
 *
 * @param  string $input
 * @return string
 */
function sanitizeNameColor(string $input): string
{
    return preg_replace("([^[:alpha:]`!@#$%^&\\)12345670~ _-])", "", $input);
}

/**
 * Alias of sanitizeHTMl().
 *
 * @param  string $input
 * @return string
 */
function sanitize_html(string $input): string
{
    return sanitizeHTML($input);
}

/**
 * Strips HTML codes and the contents within script, style, and comment tags.
 *
 * @param  string $input
 * @return string
 */
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
 * @param  string $string The string to convert with htmlentities().
 * @return string $string The string ran through htmlentities().
 */
function htmlent(string $string): string
{
    return htmlentities(
        $string,
        ENT_COMPAT,
        getsetting('charset', 'ISO-8859-1')
    );
}

function isValidEmail(string $string): bool
{
    $match = "/[[:alnum:]_.-]+[@][[:alnum:]_.-]{2,}\\.[[:alnum:]_.-]{2,}/";
    return preg_match($match, $string);
}

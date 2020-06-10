<?php

function httpget($var)
{
    global $HTTP_GET_VARS;

    $res = isset($_GET[$var]) ? $_GET[$var] : false;
    if ($res === false) {
        $res = isset($HTTP_GET_VARS[$var]) ? $HTTP_GET_VARS[$var] : false;
    }
    return $res;
}

function httpallget()
{
    return $_GET;
}

function httpset($var, $val, $force = false)
{
    global $HTTP_GET_VARS;
    if (isset($_GET[$var]) || $force) {
        $_GET[$var] = $val;
    }
    if (isset($HTTP_GET_VARS[$var])) {
        $HTTP_GET_VARS[$var] = $val;
    }
}

function httppost($var)
{
    global $HTTP_POST_VARS;

    $res = isset($_POST[$var]) ? $_POST[$var] : false;
    if ($res === false) {
        $res = isset($HTTP_POST_VARS[$var]) ?
                $HTTP_POST_VARS[$var] : false;
    }
    return $res;
}

/**
 * Clean a variable from $_POST, but leave $_POST untouched.
 *
 * @var string $variable Key from $_POST to clean.
 */
function httpPostClean(string $variable): string
{
    global $sqlite_resource, $mysqli_resource;
    if ($sqlite_resource) {
        return sqlite_real_escape_string(
            soap($_POST[$variable] ?: '', true, true)
        );
    }
    return mysqli_real_escape_string(
        $mysqli_resource,
        soap($_POST[$variable] ?: '', true, true)
    );
}

function isHttpPostSet(string $post): bool
{
    return !empty((string) filter_input(INPUT_POST, $post));
}

function isHttpGetSet(string $get): bool
{
    return !empty((string) filter_input(INPUT_GET, $get));
}

function httppostisset($var)
{
    global $HTTP_POST_VARS;

    $res = isset($_POST[$var]) ? 1 : 0;
    if ($res === 0) {
        $res = isset($HTTP_POST_VARS[$var]) ? 1 : 0;
    }
    return $res;
}

function httppostset($var, $val, $sub = false)
{
    global $HTTP_POST_VARS;
    if ($sub === false) {
        if (isset($_POST[$var])) {
            $_POST[$var] = $val;
        }
        if (isset($HTTP_POST_VARS[$var])) {
            $HTTP_POST_VARS[$var] = $val;
        }
    } else {
        if (isset($_POST[$var]) && isset($_POST[$var][$sub])) {
            $_POST[$var][$sub] = $val;
        }
        if (isset($HTTP_POST_VARS[$var]) && isset($HTTP_POST_VARS[$var][$sub])) {
            $HTTP_POST_VARS[$var][$sub] = $val;
        }
    }
}

function httpallpost()
{
    return $_POST;
}

/**
 * Clean all variables from $_POST, but leave $_POST itself untouched.
 *
 * @return array Array of cleaned $_POST variables.
 */
function httpAllPostClean(): array
{
    global $sqlite_resource, $mysqli_resource;
    $post = [];
    foreach ($_POST as $key => $value) {
        $post[$key] = httpPostClean($key);
    }
    return $post;
}

function postparse($verify = false, $subval = false)
{
    if ($subval) {
        $var = $_POST[$subval];
    } else {
        $var = $_POST;
    }

    reset($var);
    $sql = "";
    $keys = "";
    $vals = "";
    $i = 0;
    while (list($key, $val) = each($var)) {
        if ($verify === false || isset($verify[$key])) {
            if (is_array($val)) {
                $val = addslashes(serialize($val));
            }
            $sql .= (($i > 0) ? "," : "") . "$key='$val'";
            $keys .= (($i > 0) ? "," : "") . "$key";
            $vals .= (($i > 0) ? "," : "") . "'$val'";
            $i++;
        }
    }
    return array($sql, $keys, $vals);
}

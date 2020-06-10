<?php

// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS", true);
require_once "common.php";
require_once "lib/showform.php";
require_once "lib/http.php";

tlschema("about");

page_header("About Legend of the Green Dragon");
$details = gametimedetails();

checkday();
$op = httpget('op');

switch ($op) {
    case "setup":
    case "listmodules":
    case "license":
        include "lib/about/about_$op.php";
        break;
    default:
        include "lib/about/about_default.php";
        break;
}
if ($session['user']['loggedin']) {
    addnav("Return to the news", "news");
} else {
    addnav("Login Page", "home");
}
page_footer();

<?php
// addnews ready
// translator ready
// mail ready
require_once("lib/errorhandling.php");
require_once("lib/datacache.php");

/**
 * Avaiable database drivers:
 *
 * - mysqli_sqlite:     The SQLite3 extension.
 * - mysqli_proc:       The MySQLi extension of PHP5.4+, procedural style.
 * - mysqli_oos:        The MySQLi extension of PHP5.4+, object oriented style
 * @todo Configure this in a commandline installer package instead of a weak
 *  web interface.
 */
define('DBTYPE', 'mysqli_proc');
$dbinfo['queriesthishit'] = 0;

require_once('lib/dbwrapper_' . DBTYPE . '.php');

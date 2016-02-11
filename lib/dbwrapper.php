<?php
// addnews ready
// translator ready
// mail ready
require_once("lib/errorhandling.php");
require_once("lib/datacache.php");

/* * * *
 * Avaiable values for DBTYPE:
 *
 * - mysql:				The default value. Are you unsure take this.
 * - mysqli_oos:		The MySQLi extension of PHP5, object oriented style
 * - mysqli_proc:		The MySQLi extension of PHP5, procedural style
 *
 */
define('DBTYPE',"mysql");

$dbinfo = array();
$dbinfo['queriesthishit']=0;

switch(DBTYPE) {
	case 'mysql':
		require('lib/dbwrapper_mysql.php');
		break;
	case 'mysqli_oos':
		require('lib/dbwrapper_mysqli_oos.php');
		break;
	case 'mysqli_proc':
		require('lib/dbwrapper_mysqli_proc.php');
		break;
}

?>

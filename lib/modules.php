<?php
require_once("lib/arraytourl.php");
require_once('lib/modules/bootstrap.php');

function module_editor_navs($like, $linkprefix)
{
	$modules = db_prefix('modules');
	$sql = "SELECT formalname,modulename,active,category 
					FROM " . db_prefix("modules") . " WHERE infokeys LIKE '%|$like|%' ORDER BY category,formalname";
	$result = db_query($sql);
	$curcat = "";
	while($row = db_fetch_assoc($result)) {
		if ($curcat != $row['category']) {
			$curcat = $row['category'];
			addnav(sprintf("%s Modules",$curcat));
		}
		//I really think we should give keyboard shortcuts even if they're
		//susceptible to change (which only happens here when the admin changes
		//modules around).  This annoys me every single time I come in to this page.
		addnav_notl(($row['active'] ? "" : "`)") . $row['formalname']."`0",
		$linkprefix . $row['modulename']);
	}
}

/**
 * Acquires a lock for writing on the "module_settings" table.
 *
 * It is important to release the acquired lock using the `module_sem_release()`
 * function when it is no longer needed to prevent blocking other operations.
 *
 * @see module_sem_release()
 * @return void
 */
function module_sem_acquire()
{
	$sql = "LOCK TABLES " . db_prefix("module_settings") . " WRITE";
	db_query($sql);
}

/**
 * Releases the locks acquired using module_sem_acquire().
 *
 * This function releases any locks acquired using the "LOCK TABLES" statement
 * in the database. It is important to release locks when they are no longer
 * needed to prevent blocking other database operations.
 *
 * @see module_sem_release()
 *  @return void
 */
function module_sem_release()
{
	$sql = "UNLOCK TABLES";
	db_query($sql);
}

?>
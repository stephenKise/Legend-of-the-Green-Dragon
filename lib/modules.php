<?php

require_once('lib/arraytourl.php');
require_once('lib/modules/bootstrap.php');

/**
 * Adds navigation for modules with like-value infokeys.
 * @param string $like The key to search for.
 * @param string $linkPrefix Base uri to navigate to.
 * @return void
 */
function module_editor_navs(string $like = '', string $linkPrefix = ''): void
{
	$modules = db_prefix('modules');
	$result = db_query(
        "SELECT formalname, modulename, active, category 
        FROM $modules
        WHERE infokeys LIKE '%|$like|%'
        ORDER BY category, formalname"
    );
	$currentCategory = '';
	while($row = db_fetch_assoc($result)) {
		if ($currentCategory != $row['category']) {
			$currentCategory = $row['category'];
			addnav(sprintf("%s Modules", $currentCategory));
		}
		//I really think we should give keyboard shortcuts even if they're
		//susceptible to change (which only happens here when the admin changes
		//modules around).  This annoys me every single time I come in to this page.
		addnav_notl(
            ($row['active'] ? "" : "`)") . $row['formalname']."`0",
    		$linkPrefix . $row['modulename']
        );
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
function module_sem_acquire(): void
{
    $moduleSettings = db_prefix('module_settings');
	db_query("LOCK TABLES $moduleSettings WRITE");
}

/**
 * Releases the locks acquired using module_sem_acquire().
 *
 * This function releases any locks acquired using the "LOCK TABLES" statement
 * in the database. It is important to release locks when they are no longer
 * needed to prevent blocking other database operations.
 *
 * @see module_sem_release()
 * @return void
 */
function module_sem_release(): void
{
	db_query("UNLOCK TABLES");
}

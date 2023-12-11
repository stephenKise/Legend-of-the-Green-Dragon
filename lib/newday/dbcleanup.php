<?php

require_once('lib/gamelog.php');
savesetting('lastdboptimize', date('Y-m-d H:i:s'));
$result = db_query('SHOW TABLES');
$tables = [];
$start = getmicrotime();
for ($i = 0; $i < db_num_rows($result); $i++) {
	foreach (db_fetch_assoc($result) as $key => $val) {
    	db_query("OPTIMIZE TABLE $val");
    	array_push($tables, $val);
    }
}
$time = round(getmicrotime() - $start, 2);
$tableString = join(', ', $tables);
gamelog("Optimized tables: $tableString in $time seconds.", 'maintenance');
?>

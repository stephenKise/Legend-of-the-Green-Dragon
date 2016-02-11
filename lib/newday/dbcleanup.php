<?php
//db cleanup
savesetting("lastdboptimize",date("Y-m-d H:i:s"));
$result = db_query("SHOW TABLES");
$tables = array();
$start = getmicrotime();
for ($i=0;$i<db_num_rows($result);$i++){
	list($key,$val)=each(db_fetch_assoc($result));
	db_query("OPTIMIZE TABLE $val");
	array_push($tables,$val);
}
$time = round(getmicrotime() - $start,2);
require_once("lib/gamelog.php");
gamelog("Optimized tables: ".join(", ",$tables)." in $time seconds.","maintenance");
?>

<?php
// translator ready
// addnews ready
// mail ready
function getmount($horse=0) {
	$sql = "SELECT * FROM " . db_prefix("mounts") . " WHERE mountid='$horse'";
	$result = db_query_cached($sql, "mountdata-$horse", 3600);
	if (db_num_rows($result)>0){
		return db_fetch_assoc($result);
	}else{
		return array();
	}
}
?>

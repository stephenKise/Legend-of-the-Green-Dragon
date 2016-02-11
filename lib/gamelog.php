<?php
function gamelog($message,$category="general",$filed=false){
	global $session;
	$sql = "INSERT INTO ".db_prefix("gamelog")." (message,category,filed,date,who) VALUES (
		'".addslashes($message)."',
		'".addslashes($category)."',
		'".($filed?"1":"0")."',
		'".date("Y-m-d H:i:s")."',
		'".(int)$session['user']['acctid']."'
	)";
	db_query($sql);
}
?>

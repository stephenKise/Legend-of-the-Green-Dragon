<?php
if (httppost("newday") !=""){
#	$offset = "-".(24 / (int)getsetting("daysperday",4))." hours";
#	$newdate = date("Y-m-d H:i:s",strtotime($offset));
#	$sql = "UPDATE " . db_prefix("accounts") . " SET lasthit='$newdate' WHERE acctid='$userid'";
	$sql = "UPDATE " . db_prefix("accounts") . " SET lasthit='0000-00-00 00:00:00' WHERE acctid='$userid'";
	db_query($sql);
}elseif(httppost("fixnavs")!=""){
	$sql = "UPDATE " . db_prefix("accounts") . " SET allowednavs='', restorepage='', specialinc='' WHERE acctid='$userid'";
	db_query($sql);
	$sql = "DELETE FROM ".db_prefix("accounts_output")." WHERE acctid='$userid';";
	db_query($sql);
} elseif(httppost("clearvalidation")!=""){
	$sql = "UPDATE " . db_prefix("accounts") . " SET emailvalidation='' WHERE acctid='$userid'";
	db_query($sql);
}
$op = "edit";
httpset("op", "edit");
?>
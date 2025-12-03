<?php
if (httppost("newday") !=""){
#	$offset = "-".(24 / (int)getsetting("daysperday",4))." hours";
#	$newdate = date("Y-m-d H:i:s",strtotime($offset));
#	$sql = "UPDATE " . db_prefix("accounts") . " SET lasthit='$newdate' WHERE acctid='$userid'";
    $newDay = date('Y-m-d H:i:s', strtotime('-1 day'));
	$sql = "UPDATE " . db_prefix("accounts") . " SET lasthit = '$newDay' WHERE acctid='$userid'";
	db_query($sql);
}elseif(httppost("fixnavs")!=""){
	$sql = "UPDATE " . db_prefix("accounts") . " SET allowednavs='', restorepage='', specialinc='' WHERE acctid='$userid'";
	db_query($sql);
	unlink("accounts-output/$userid.html");
} elseif(httppost("clearvalidation")!=""){
	$sql = "UPDATE " . db_prefix("accounts") . " SET emailvalidation='' WHERE acctid='$userid'";
	db_query($sql);
}
$op = "edit";
httpset("op", "edit");
?>
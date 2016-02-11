<?php
$sql = "INSERT INTO " . db_prefix("bans") . " (banner,";
$type = httppost("type");
if ($type=="ip"){
	$sql.="ipfilter";
}else{
	$sql.="uniqueid";
}
$sql.=",banexpire,banreason) VALUES ('" . addslashes($session['user']['name']) . "',";
if ($type=="ip"){
	$sql.="\"".httppost("ip")."\"";
}else{
	$sql.="\"".httppost("id")."\"";
}
$duration = (int)httppost("duration");
if ($duration == 0) $duration="0000-00-00";
else $duration = date("Y-m-d", strtotime("+$duration days"));
	$sql.=",\"$duration\",";
$sql.="\"".httppost("reason")."\")";
if ($type=="ip"){
	if (substr($_SERVER['REMOTE_ADDR'],0,strlen(httppost("ip"))) ==
			httppost("ip")){
		$sql = "";
		output("You don't really want to ban yourself now do you??");
		output("That's your own IP address!");
	}
}else{
	if ($_COOKIE['lgi']==httppost("id")){
		$sql = "";
		output("You don't really want to ban yourself now do you??");
		output("That's your own ID!");
	}
}
if ($sql!=""){
	db_query($sql);
	output("%s ban rows entered.`n`n", db_affected_rows());
	output_notl("%s", db_error(LINK));
	debuglog("entered a ban: " .  ($type=="ip"?  "IP: ".httppost("ip"): "ID: ".httppost("id")) . " Ends after: $duration  Reason: \"" .  httppost("reason")."\"");
}
?>
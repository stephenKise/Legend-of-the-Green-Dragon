<?php

$bansPrefix = db_prefix('bans');
$type = httppost('type');
$id = httppost('id') ?: 'NULL';
$ip = httppost('ip') ?: 'NULL';
$duration = (int) httppost('duration') ?: 'NULL';
$reason = addslashes(httppost('reason'));
$banner = addslashes(getSessionUser('name'));
$lastHit = date('Y-m-d H:i:s');
if ($duration !== 'NULL') {
    $duration = date('Y-m-d', strtotime("+$duration days"));
    $duration = "'$duration'";
}


if ($type == 'ip' && substr($_SERVER['REMOTE_ADDR'], 0, strlen($ip)) == $ip) {
	output("You don't really want to ban yourself now do you??");
	output("That's your own IP address!");
    return;
} else if ($_COOKIE['lgi'] == $id) {
	output("You don't really want to ban yourself now do you??");
	output("That's your own ID!");
    return;
}

$sql = "INSERT INTO $bansPrefix (ipfilter, uniqueid, banexpire, banreason, banner, lasthit)
    VALUES ('$ip', '$id', $duration, '$reason', '$banner', '$lastHit')";
// $type = httppost("type");
// if ($type=="ip"){
// 	$sql.="ipfilter";
// }else{
// 	$sql.="uniqueid";
// }
// $sql.=",banexpire,banreason) VALUES ('" . addslashes($session['user']['name']) . "',";
// if ($type=="ip"){
// 	$sql.="\"".httppost("ip")."\"";
// }else{
// 	$sql.="\"".httppost("id")."\"";
// }
// $duration = (int)httppost("duration");
// if ($duration == 0) $duration="0000-00-00";
// else $duration = date("Y-m-d", strtotime("+$duration days"));
// 	$sql.=",\"$duration\",";
// $sql.="\"".httppost("reason")."\")";
db_query($sql);
output("%s ban rows entered.`n`n", db_affected_rows());
output_notl("%s", db_error(LINK));
debuglog("entered a ban: " .  ($type == "ip"?  "IP: ".httppost("ip"): "ID: ".httppost("id")) . " Ends after: $duration  Reason: \"" .  httppost("reason")."\"");
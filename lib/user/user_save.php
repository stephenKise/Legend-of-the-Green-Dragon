<?php
$sql = "";
$updates=0;
$oldvalues = stripslashes(httppost('oldvalues'));
$oldvalues = unserialize($oldvalues);
// Handle recombining the old name
$otitle = $oldvalues['title'];
if ($oldvalues['ctitle']) $otitle = $oldvalues['ctitle'];
$oldvalues['name'] = $otitle . ' ' . $oldvalues['name'];
	$post = httpallpost();
reset($post);
while (list($key,$val)=each($post)){
	if (isset($userinfo[$key])){
		if ($key=="newpassword" ){
			if ($val>"") {
				$sql.="password=\"".md5(md5($val))."\",";
				$updates++;
				output("Password value has been updated.`n");
				debuglog($session['user']['name']."`0 changed password to $val",$userid);
				if ($session['user']['acctid']==$userid) {
					$session['user']['password']=md5(md5($val));
				}
			}
		}elseif ($key=="superuser"){
			$value = 0;
			while (list($k,$v)=each($val)){
				if ($v) $value += (int)$k;
			}
				//strip off an attempt to set privs that the user doesn't
			//have authority to set.
			$stripfield = ((int)$oldvalues['superuser'] | $session['user']['superuser'] | SU_ANYONE_CAN_SET | ($session['user']['superuser'] & SU_MEGAUSER ? 0xFFFFFFFF : 0));
			$value = $value & $stripfield;
				//put back on privs that the user used to have but the
			//current user can't set.
			$unremovable = ~ ((int)$session['user']['superuser'] | SU_ANYONE_CAN_SET | ($session['user']['superuser'] & SU_MEGAUSER ? 0xFFFFFFFF : 0));
			$filteredunremovable = (int)$oldvalues['superuser'] & $unremovable;
			$value = $value | $filteredunremovable;
			if ((int)$value != (int)$oldvalues['superuser']){
				$sql.="$key = \"$value\",";
				$updates++;
				output("Superuser values have changed.`n");
				if ($session['user']['acctid']==$userid) {
					$session['user']['superuser']=$value;
				}
				debuglog($session['user']['name']."`0 changed superuser to ".show_bitfield($value),$userid);
				debug("superuser has changed to $value");
			}
		} elseif ($key=="name" && stripslashes($val)!=$oldvalues[$key]) {
			$updates++;
			$tmp = sanitize_colorname(getsetting("spaceinname", 0),
					stripslashes($val), true);
			$tmp = preg_replace("/[`][cHw]/", "", $tmp);
			$tmp = sanitize_html($tmp);
			if ($tmp != stripslashes($val)) {
				output("`\$Illegal characters removed from player name!`0`n");
			}
			if (soap($tmp) != ($tmp)) {
				output("`^The new name doesn't pass the bad word filter!`0");
			}
				$newname = change_player_name($tmp, $oldvalues);
			$sql.="$key = \"".addslashes($newname)."\",";
			output("Changed player name to %s`0`n", $newname);
			debuglog($session['user']['name'] . "`0 changed player name to $newname`0", $userid);
			$oldvalues['name']=$newname;
			if ($session['user']['acctid']==$userid) {
				$session['user']['name'] = $newname;
			}
		} elseif ($key=="title" && stripslashes($val)!=$oldvalues[$key]) {
			$updates++;
			$tmp = sanitize_colorname(true, stripslashes($val), true);
			$tmp = preg_replace("/[`][cHw]/", "", $tmp);
			$tmp = sanitize_html($tmp);
			if ($tmp != stripslashes($val)) {
				output("`\$Illegal characters removed from player title!`0`n");
			}
			if (soap($tmp) != ($tmp)) {
				output("`^The new title doesn't pass the bad word filter!`0");
			}
				$newname = change_player_title($tmp, $oldvalues);
			$sql.="$key = \"$val\",";
			output("Changed player title from %s`0 to %s`0`n", $oldvalues['title'], $tmp);
			$oldvalues[$key]=$tmp;
			if ($newname != $oldvalues['name']) {
				$sql.="name = \"".addslashes($newname)."\",";
				output("Changed player name to %s`0 due to changed dragonkill title`n", $newname);
				debuglog($session['user']['name'] . "`0 changed player name to $newname`0 due to changed dragonkill title", $userid);
				$oldvalues['name']=$newname;
				if ($session['user']['acctid']==$userid) {
					$session['user']['name'] = $newname;
				}
			}
			if ($session['user']['acctid']==$userid) {
				$session['user']['title'] = $tmp;
			}
		} elseif ($key=="ctitle" && stripslashes($val)!=$oldvalues[$key]) {
			$updates++;
			$tmp = sanitize_colorname(true, stripslashes($val), true);
			$tmp = preg_replace("/[`][cHw]/", "", $tmp);
			$tmp = sanitize_html($tmp);
			if ($tmp != stripslashes($val)) {
				output("`\$Illegal characters removed from custom title!`0`n");
			}
			if (soap($tmp) != ($tmp)) {
				output("`^The new custom title doesn't pass the bad word filter!`0");
			}
			$newname = change_player_ctitle($tmp, $oldvalues);
			$sql.="$key = \"$val\",";
			output("Changed player ctitle from %s`0 to %s`0`n", $oldvalues['ctitle'], $tmp);
			$oldvalues[$key]=$tmp;
			if ($newname != $oldvalues['name']) {
				$sql.="name = \"".addslashes($newname)."\",";
				output("Changed player name to %s`0 due to changed custom title`n", $newname);
				debuglog($session['user']['name'] . "`0 changed player name to $newname`0 due to changed custom title", $userid);
				$oldvalues['name']=$newname;
				if ($session['user']['acctid']==$userid) {
					$session['user']['name'] = $newname;
				}
			}
			if ($session['user']['acctid']==$userid) {
				$session['user']['ctitle'] = $tmp;
			}
		}elseif ($key=="oldvalues"){
			//donothing.
		}elseif ($oldvalues[$key]!=stripslashes($val) && isset($oldvalues[$key])){
			$sql.="$key = \"$val\",";
			$updates++;
			output("%s has changed to %s.`n", $key, stripslashes($val));
			debuglog($session['user']['name']."`0 changed $key to $val",$userid);
			if ($session['user']['acctid']==$userid) {
				$session['user'][$key]=stripslashes($val);
			}
		}
	}
}
	$sql=substr($sql,0,strlen($sql)-1);
$sql = "UPDATE " . db_prefix("accounts") . " SET " . $sql . " WHERE acctid=\"$userid\"";
	$petition = httpget("returnpetition");
if ($petition!="")
	addnav("","viewpetition.php?op=view&id=$petition");
addnav("","user.php");
	if ($updates>0){
	db_query($sql);
	debug("Updated $updates fields in the user record with:\n$sql");
	output("%s fields in the user's record were updated.", $updates);
}else{
	output("No fields were changed in the user's record.");
}
$op = "edit";
httpset($op, "edit");
?>
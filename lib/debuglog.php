<?php
// translator ready
// addnews ready
// mail ready
function debuglog($message,$target=false,$user=false,$field=false,$value=false,$consolidate=true){
	if ($target===false) $target=0;
	static $needsdebuglogdelete = true;
	global $session;
	$args = func_get_args();
	if ($user === false) $user = $session['user']['acctid'];
	$corevalue = $value;
	$id=0;
	if ($field !== false && $value !==false && $consolidate){
		$sql = "SELECT * FROM ".db_prefix("debuglog")." WHERE actor=$user AND field='$field' AND date>'".date("Y-m-d 00:00:00")."'";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			$row = db_fetch_assoc($result);
			$value = $row['value']+$value;
			$message = $row['message'];
			$id = $row['id'];
		}
	}
	if ($corevalue!==false) $message.=" ($corevalue)";
	if ($field===false) $field="";
	if ($value===false) $value=0;
	if ($id > 0){
		$sql = "UPDATE ".db_prefix("debuglog")."
			SET
				date='".date("Y-m-d H:i:s")."',
				actor='$user',
				target='$target',
				message='".addslashes($message)."',
				field='$field',
				value='$value'
			WHERE
				id=$id
				";
	}else{
		$sql = "INSERT INTO " . db_prefix("debuglog") . " (id,date,actor,target,message,field,value) VALUES($id,'".date("Y-m-d H:i:s")."',$user,$target,'".addslashes($message)."','$field','$value')";
	}
	db_query($sql);
}

?>

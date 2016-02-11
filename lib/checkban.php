<?php
// translator ready
// addnews ready
// mail ready
function checkban($login=false){
	global $session;
	if (isset($session['banoverride']) && $session['banoverride'])
		return false;
	if ($login===false){
		$ip=$_SERVER['REMOTE_ADDR'];
		$id=$_COOKIE['lgi'];
	}else{
		$sql = "SELECT lastip,uniqueid,banoverride,superuser FROM " . db_prefix("accounts") . " WHERE login='$login'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if ($row['banoverride'] || ($row['superuser'] &~ SU_DOESNT_GIVE_GROTTO)){
			$session['banoverride']=true;
			return false;
		}
		db_free_result($result);
		$ip=$row['lastip'];
		$id=$row['uniqueid'];
	}
	$sql = "SELECT * FROM " . db_prefix("bans") . " where ((substring('$ip',1,length(ipfilter))=ipfilter AND ipfilter<>'') OR (uniqueid='$id' AND uniqueid<>'')) AND (banexpire='0000-00-00' OR banexpire>='".date("Y-m-d")."')";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$session=array();
		tlschema("ban");
		$session['message'].=translate_inline("`n`4You fall under a ban currently in place on this website:`n");
		while ($row = db_fetch_assoc($result)) {
			$session['message'].=$row['banreason']."`n";
			if ($row['banexpire']=='0000-00-00')
				$session['message'].=translate_inline("  `\$This ban is permanent!`0");
				else
				$session['message'].=sprintf_translate("  `^This ban will be removed `\$after`^ %s.`0",date("M d, Y",strtotime($row['banexpire'])));
			$sql = "UPDATE " . db_prefix("bans") . " SET lasthit='".date("Y-m-d H:i:s")."' WHERE ipfilter='{$row['ipfilter']}' AND uniqueid='{$row['uniqueidid']}'";
			db_query($sql);
			$session['message'].="`n";
		}
		$session['message'].=translate_inline("`4If you wish, you may appeal your ban with the petition link.");
		tlschema();
		header("Location: index.php");
		exit();
	}
	db_free_result($result);
}

?>

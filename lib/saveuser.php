<?php
// translator ready
// addnews ready
// mail ready

function saveuser(){
	global $session,$dbqueriesthishit,$baseaccount,$companions;
	if (defined("NO_SAVE_USER")) return false;
	$acctId = getSessionUser('acctid');

	if (getSession('loggedin') && getSessionUser('acctid') != '') {
		// Any time we go to save a user, make SURE that any tempstat changes
		// are undone.
		restore_buff_fields();

		if (!getSessionUser('alive')) {
			$session['user']['alive'] = 0;
		}
		$session['user']['allowednavs']=serialize($session['allowednavs']);
		$session['user']['bufflist']=serialize($session['bufflist']);
		if (isset($companions) && is_array($companions)) $session['user']['companions']=serialize($companions);
		$sql="";
		reset($session['user']);
		foreach (getSession('user') as $key => $val) {
			if (is_array($val)) $val = serialize($val);
			//only update columns that have changed.
			if ($baseaccount[$key]!=$val){
				$sql.="$key='".addslashes($val)."', ";
			}
		}
		//due to the change in the accounts table -> moved output -> save everyhit
		$sql.="laston='".date("Y-m-d H:i:s")."', ";
		$sql = substr($sql,0,strlen($sql)-2);
		$sql="UPDATE " . db_prefix("accounts") . " SET " . $sql .
			" WHERE acctid = ".$session['user']['acctid'];
		db_query($sql);
		if (isset($session['output']) && $session['output']) {
			file_put_contents("accounts-output/$acctId.html", $session['output']);
		}
        unset($session['output']);
		unset($session['bufflist']);
		$session['user'] = array(
			"acctid"=>$session['user']['acctid'],
			"login"=>$session['user']['login'],
		);
	}
}

?>

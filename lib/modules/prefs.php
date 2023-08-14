<?php



function load_module_prefs($module, $user=false){
	global $module_prefs,$session;
	if ($user===false) $user = $session['user']['acctid'];
	if (!isset($module_prefs[$user])) $module_prefs[$user] = array();
	if (!isset($module_prefs[$user][$module])){
		$module_prefs[$user][$module] = array();
		$sql = "SELECT setting,value FROM " . db_prefix("module_userprefs") . " WHERE modulename='$module' AND userid='$user'";
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)){
			$module_prefs[$user][$module][$row['setting']] = $row['value'];
		}//end while
	}//end if
}//end function


function module_delete_objprefs($objtype, $objid)
{
	$sql = "DELETE FROM " . db_prefix("module_objprefs") . " WHERE objtype='$objtype' AND objid='$objid'";
	db_query($sql);
	massinvalidate("objpref-$objtype-$objid");
}

function get_module_objpref($type, $objid, $name, $module=false){
	global $mostrecentmodule;
	if ($module === false) $module = $mostrecentmodule;
	$sql = "SELECT value FROM ".db_prefix("module_objprefs")." WHERE modulename='$module' AND objtype='$type' AND setting='".addslashes($name)."' AND objid='$objid' ";
	$result = db_query_cached($sql, "objpref-$type-$objid-$name-$module", 86400);
	if (db_num_rows($result)>0){
		$row = db_fetch_assoc($result);
		return $row['value'];
	}
	//we couldn't find this elsewhere, load the default value if it exists.
	$info = get_module_info($module);
	if (isset($info['prefs-'.$type][$name])){
		if (is_array($info['prefs-'.$type][$name])) {
			$v = $info['prefs-'.$type][$name][0];
			$x = explode("|", $v);
		} else {
			$x = explode("|",$info['prefs-'.$type][$name]);
		}
		if (isset($x[1])){
			set_module_objpref($type,$objid,$name,$x[1],$module);
			return $x[1];
		}
	}
	return NULL;
}

function set_module_objpref($objtype,$objid,$name,$value,$module=false){
	global $mostrecentmodule;
	if ($module === false) $module = $mostrecentmodule;
	// Delete the old version and insert the new
	$sql = "REPLACE INTO " . db_prefix("module_objprefs") . "(modulename,objtype,setting,objid,value) VALUES ('$module', '$objtype', '$name', '$objid', '".addslashes($value)."')";
	db_query($sql);
	invalidatedatacache("objpref-$objtype-$objid-$name-$module");
}

function increment_module_objpref($objtype,$objid,$name,$value=1,$module=false) {
	global $mostrecentmodule;
	$value = (float)$value;
	if ($module === false) $module = $mostrecentmodule;
	$sql = "UPDATE " . db_prefix("module_objprefs") . " SET value=value+$value WHERE modulename='$module' AND setting='".addslashes($name)."' AND objtype='".addslashes($objtype)."' AND objid=$objid;";
	$result= db_query($sql);
	if (db_affected_rows($result)==0){
		//if the update did not do anything, insert the row
		$sql = "INSERT INTO " . db_prefix("module_objprefs") . "(modulename,objtype,setting,objid,value) VALUES ('$module', '$objtype', '$name', '$objid', '".addslashes($value)."')";
		db_query($sql);
	}
	invalidatedatacache("objpref-$objtype-$objid-$name-$module");
}

function module_objpref_edit($type, $module, $id)
{
	$info = get_module_info($module);
	if (count($info['prefs-'.$type]) > 0) {
		$data = array();
		$msettings = array();
		foreach ($info["prefs-$type"] as $key => $val) {
			if (is_array($val)) {
				$v = $val[0];
				$x = explode("|", $v);
				$val[0] = $x[0];
				$x[0] = $val;
			} else {
				$x = explode("|", $val);
			}
			$msettings[$key]=$x[0];
			// Set up default
			if (isset($x[1])) $data[$key]=$x[1];
		}
		$sql = "SELECT setting, value FROM " . db_prefix("module_objprefs") . " WHERE modulename='$module' AND objtype='$type' AND objid='$id'";
		$result = db_query($sql);
		while($row = db_fetch_assoc($result)) {
			$data[$row['setting']] = $row['value'];
		}
		tlschema("module-$module");
		showform($msettings, $data);
		tlschema();
	}
}

function module_delete_userprefs($user){
	$sql = "DELETE FROM " . db_prefix("module_userprefs") . " WHERE userid='$user'";
	db_query($sql);
}

$module_prefs=array();
function get_all_module_prefs($module=false,$user=false){
	global $module_prefs,$mostrecentmodule,$session;
	if ($module === false) $module = $mostrecentmodule;
	if ($user === false) $user = $session['user']['acctid'];
	load_module_prefs($module,$user);

	return $module_prefs[$user][$module];
}

function get_module_pref($name,$module=false,$user=false){
	global $module_prefs,$mostrecentmodule,$session;
	if ($module === false) $module = $mostrecentmodule;
	if ($user===false) {
		if(isset($session['user']['loggedin']) && $session['user']['loggedin']) $user = $session['user']['acctid'];
		else $user = 0;
	}

	if (isset($module_prefs[$user][$module][$name])) {
		return $module_prefs[$user][$module][$name];
	}

	//load here, not before
	load_module_prefs($module,$user);
	//check if *now* it's loaded
	if (isset($module_prefs[$user][$module][$name])) {
		return $module_prefs[$user][$module][$name];
	}

	if (!is_module_active($module)) return NULL;

	//we couldn't find this elsewhere, load the default value if it exists.
	$info = get_module_info($module);
	if (isset($info['prefs'][$name])){
		if (is_array($info['prefs'][$name])) {
			$v = $info['prefs'][$name][0];
			$x = explode("|", $v);
		} else {
			$x = explode("|",$info['prefs'][$name]);
		}
		if (isset($x[1])){
			set_module_pref($name,$x[1],$module,$user);
			return $x[1];
		}
	}
	return NULL;
}

function set_module_pref($name,$value,$module=false,$user=false){
	global $module_prefs,$mostrecentmodule,$session;
	if ($module === false) $module = $mostrecentmodule;
	if ($user === false) $uid=$session['user']['acctid'];
	else $uid = $user;
	load_module_prefs($module, $uid);

	//don't write to the DB if the user isn't logged in.
	if (!$session['user']['loggedin'] && !$user) {
		// We do need to save to the loaded copy here however
		$module_prefs[$uid][$module][$name] = $value;
		return;
	}

	if (isset($module_prefs[$uid][$module][$name])){
		$sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='".addslashes($value)."' WHERE modulename='$module' AND setting='$name' AND userid='$uid'";
		db_query($sql);
	}else{
		$sql = "INSERT INTO " . db_prefix("module_userprefs"). " (modulename,setting,userid,value) VALUES ('$module','$name','$uid','".addslashes($value)."')";
		db_query($sql);
	}
	$module_prefs[$uid][$module][$name] = $value;
}

function increment_module_pref($name,$value=1,$module=false,$user=false){
	global $module_prefs,$mostrecentmodule,$session;
	$value = (float)$value;
	if ($module === false) $module = $mostrecentmodule;
	if ($user === false) $uid=$session['user']['acctid'];
	else $uid = $user;
	load_module_prefs($module, $uid);

	//don't write to the DB if the user isn't logged in.
	if (!$session['user']['loggedin'] && !$user) {
		// We do need to save to the loaded copy here however
		$module_prefs[$uid][$module][$name] += $value;
		return;
	}

	if (isset($module_prefs[$uid][$module][$name])){
		$sql = "UPDATE " . db_prefix("module_userprefs") . " SET value=value+$value WHERE modulename='$module' AND setting='$name' AND userid='$uid'";
		db_query($sql);
		$module_prefs[$uid][$module][$name] += $value;
	}else{
		$sql = "INSERT INTO " . db_prefix("module_userprefs"). " (modulename,setting,userid,value) VALUES ('$module','$name','$uid','".addslashes($value)."')";
		db_query($sql);
		$module_prefs[$uid][$module][$name] = $value;
	}
}

function clear_module_pref($name,$module=false,$user=false){
 	global $module_prefs,$mostrecentmodule,$session;
	if ($module === false) $module = $mostrecentmodule;
	if ($user === false) $uid=$session['user']['acctid'];
	else $uid = $user;
	load_module_prefs($module, $uid);

	//don't write to the DB if the user isn't logged in.
	if (!$session['user']['loggedin'] && !$user) {
		// We do need to trash the loaded copy here however
		unset($module_prefs[$uid][$module][$name]);
		return;
	}

	if (isset($module_prefs[$uid][$module][$name])){
		$sql = "DELETE FROM " . db_prefix("module_userprefs") . " WHERE modulename='$module' AND setting='$name' AND userid='$uid'";
		db_query($sql);
	}
	unset($module_prefs[$uid][$module][$name]);
}
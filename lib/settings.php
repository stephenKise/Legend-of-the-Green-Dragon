<?php
// translator ready
// addnews ready
// mail ready

function savesetting($settingname,$value){
	global $settings;
		loadsettings();
	if (!isset($settings[$settingname]) && $value){
			$sql = "INSERT INTO " . db_prefix("settings") . " (setting,value) VALUES (\"".addslashes($settingname)."\",\"".addslashes($value)."\")";
	}else if (isset($settings[$settingname])) {
			$sql = "UPDATE " . db_prefix("settings") . " SET value=\"".addslashes($value)."\" WHERE setting=\"".addslashes($settingname)."\"";
	} else {
		return false;
	}
	db_query($sql);
	$settings[$settingname]=$value;
	invalidatedatacache("game-settings");
	if (db_affected_rows()>0) {
		return true;
	}else{
		return false;
	}
}

function loadsettings(){
	global $settings;
	// as this seems to be a common complaint, examine the execution path
	// of this function, it will only load the settings once per page hit,
	// in subsequent calls to this function, $settings will be an array,
	// thus this function will do nothing.
	// slight change in 1.1.1 ... let's store a serialized array instead of a cached query
	// we need it too often and the for/while construct necessary is just too much for it.
	if (!is_array($settings)){
		$settings=datacache("game-settings");
		if (!is_array($settings)){
			$settings=array();
			$sql = "SELECT * FROM " . db_prefix("settings");
			$result = db_query($sql);//db_query_cached($sql,"game-settings");
			while ($row = db_fetch_assoc($result)) {
				$settings[$row['setting']] = $row['value'];
			}
			db_free_result($result);
			updatedatacache("game-settings",$settings);
		}
	}
}

function clearsettings(){
	//scraps the loadsettings() data to force it to reload.
	global $settings;
	unset($settings);
}

function getsetting($settingname,$default){
	global $settings;
	global $DB_USEDATACACHE,$DB_DATACACHEPATH;
	if ($settingname=="usedatacache") return $DB_USEDATACACHE;
		elseif ($settingname=="datacachepath") return $DB_DATACACHEPATH;
	if (!isset($settings[$settingname])) {
		loadsettings();
	}else {
		return $settings[$settingname];
	}
	if (!isset($settings[$settingname])){
		savesetting($settingname,$default);
		return $default;
	}else{
		return $settings[$settingname];
	}
}
?>

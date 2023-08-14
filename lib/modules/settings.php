<?php


$module_settings = array();
function get_all_module_settings($module=false){
	//returns an associative array of all the settings for the given module
	global $module_settings,$mostrecentmodule;
	if ($module === false) $module = $mostrecentmodule;

	load_module_settings($module);
	return $module_settings[$module];
}

function get_module_setting($name,$module=false){
	global $module_settings,$mostrecentmodule;
	if ($module === false) $module = $mostrecentmodule;

	load_module_settings($module);
	if (isset($module_settings[$module][$name])) {
		return $module_settings[$module][$name];
	}else{
		$info = get_module_info($module);
		if (isset($info['settings'][$name])){
			if (is_array($info['settings'][$name])) {
				$v = $info['settings'][$name][0];
				$x = explode("|", $v);
			} else {
				$x = explode("|",$info['settings'][$name]);
			}
			if (isset($x[1])){
				return $x[1];
			}
		}
		return NULL;
	}
}

function set_module_setting($name,$value,$module=false){
	global $module_settings,$mostrecentmodule;
	if ($module === false) $module = $mostrecentmodule;
	load_module_settings($module);
	if (isset($module_settings[$module][$name])){
		$sql = "UPDATE " . db_prefix("module_settings") . " SET value='".addslashes($value)."' WHERE modulename='$module' AND setting='".addslashes($name)."'";
		db_query($sql);
	}else{
		$sql = "INSERT INTO " . db_prefix("module_settings") . " (modulename,setting,value) VALUES ('$module','".addslashes($name)."','".addslashes($value)."')";
		db_query($sql);
	}
	invalidatedatacache("modulesettings-$module");
	$module_settings[$module][$name] = $value;
}

function increment_module_setting($name, $value=1, $module=false){
	global $module_settings,$mostrecentmodule;
	$value = (float)$value;
	if ($module === false) $module = $mostrecentmodule;
	load_module_settings($module);
	if (isset($module_settings[$module][$name])){
		$sql = "UPDATE " . db_prefix("module_settings") . " SET value=value+$value WHERE modulename='$module' AND setting='".addslashes($name)."'";
		db_query($sql);
	}else{
		$sql = "INSERT INTO " . db_prefix("module_settings") . " (modulename,setting,value) VALUES ('$module','".addslashes($name)."','".addslashes($value)."')";
		db_query($sql);
	}
	invalidatedatacache("modulesettings-$module");
	$module_settings[$module][$name] += $value;
}

function clear_module_settings($module=false){
	global $module_settings,$mostrecentmodule;
	if ($module === false) $module = $mostrecentmodule;
	if (isset($module_settings[$module])){
		debug("Deleted module settings cache for $module.");
		unset($module_settings[$module]);
		invalidatedatacache("modulesettings-$module");
	}
}

function load_module_settings($module){
	global $module_settings;
	if (!isset($module_settings[$module])){
		$module_settings[$module] = array();
		$sql = "SELECT * FROM " . db_prefix("module_settings") . " WHERE modulename='$module'";
		// TODO: Fix the cached db queries.
		// $result = db_query_cached($sql,"modulesettings-$module");
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)){
			$module_settings[$module][$row['setting']] = $row['value'];
		}//end while
	}//end if
}//end function

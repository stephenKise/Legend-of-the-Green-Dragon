<?php

$injected_modules = array(1=>array(),0=>array());

function injectmodule($modulename,$force=false){
	global $mostrecentmodule,$injected_modules;
	//try to circumvent the array_key_exists() problem we've been having.
	if ($force) $force = 1; else $force = 0;

	//early escape if we already called injectmodule this hit with the
	//same args.
	if (isset($injected_modules[$force][$modulename])) {
		$mostrecentmodule=$modulename;
		return $injected_modules[$force][$modulename];
	}

	$modulename = modulename_sanitize($modulename);
	$modulefilename = "modules/{$modulename}.php";
	if (file_exists($modulefilename)){
		tlschema("module-{$modulename}");
		$sql = "SELECT active,filemoddate,infokeys,version FROM " . db_prefix("modules") . " WHERE modulename='$modulename'";
		// $result = db_query_cached($sql, "inject-$modulename", 3600);
		$result = db_query($sql);
		if (!$force) {
			//our chance to abort if this module isn't currently installed
			//or doesn't meet the prerequisites.
			if (db_num_rows($result)==0) {
				tlschema();
			 	output_notl("`n`3Module `#%s`3 is not installed, but was attempted to be injected.`n",$modulename);
				$injected_modules[$force][$modulename]=false;
				return false;
			}
			$row = db_fetch_assoc($result);
			if ($row['active']){ } else {
				tlschema();
			 	output("`n`3Module `#%s`3 is not active, but was attempted to be injected.`n",$modulename);
				$injected_modules[$force][$modulename]=false;
				return false;
			}
		}
		require_once($modulefilename);
		$mostrecentmodule = $modulename;
		$info = "";
		if (!$force){
			//avoid calling the function if we're forcing the module
			$fname = $modulename."_getmoduleinfo";
			$info = $fname();
			if (!isset($info['requires'])) $info['requires'] = array();
			if (!is_array($info['requires'])) $info['requires'] = array();
			if (!isset($info['download'])) $info['download']="";
			if (!isset($info['description'])) $info['description']="";
			if (!module_check_requirements($info['requires'])) {
				$injected_modules[$force][$modulename]=false;
				tlschema();
				output("`n`3Module `#%s`3 does not meet its prerequisites.`n",$modulename);
				return false;
			}
		}
		//check to see if the module needs to be upgraded.
		if (db_num_rows($result)>0){
			if (!isset($row)) $row = db_fetch_assoc($result);
			$filemoddate = date("Y-m-d H:i:s",filemtime($modulefilename));
			if ($row['filemoddate']!=$filemoddate || $row['infokeys']=="" ||
					$row['infokeys'][0] != '|' || $row['version']==''){
				//The file has recently been modified, lock tables and
				//check again (knowing we're the only one who can do this
				//at one shot)
				$sql = "LOCK TABLES " . db_prefix("modules") . " WRITE";
				db_query($sql);
				//check again after the table has been locked.
				$sql = "SELECT filemoddate FROM " . db_prefix("modules") . " WHERE modulename='$modulename'";
				$result = db_query($sql);
				$row = db_fetch_assoc($result);
				if ($row['filemoddate']!=$filemoddate ||
						!isset($row['infokeys']) || $row['infokeys']=="" || $row['infokeys'][0] != '|' ||
						$row['version']==''){
					//the file mod time is still different from that
					//recorded in the database, time to update the database
					//and upgrade the module.
					debug("The module $modulename was found to have updated, upgrading the module now.");
					if (!is_array($info)){
						//we might have gotten this info above, if not,
						//we need it now.
						$fname = $modulename."_getmoduleinfo";
						$info = $fname();
						if (!isset($info['download']))
							$info['download']="";
						if (!isset($info['version']))
							$info['version']="0.0";
						if (!isset($info['description']))
							$info['description'] = '';
					}
					//Everyone else will block at the initial lock tables,
					//we'll update, and on their second check, they'll fail.
					//Only we will update the table.

					$keys = "|".join("|", array_keys($info))."|";

					$sql = "UPDATE ". db_prefix("modules") . " SET moduleauthor='".addslashes($info['author'])."', category='".addslashes($info['category'])."', formalname='".addslashes($info['name'])."', description='".addslashes($info['description'])."', filemoddate='$filemoddate', infokeys='$keys',version='".addslashes($info['version'])."',download='".addslashes($info['download'])."' WHERE modulename='$modulename'";
					db_query($sql);
					$sql = "UNLOCK TABLES";
					db_query($sql);
					// Remove any old hooks (install will reset them)
					module_wipehooks();
					$fname = $modulename."_install";
					if ($fname() === false) {
						return false;
					}
					invalidatedatacache("inject-$modulename");

				}else{
					$sql = "UNLOCK TABLES";
					db_query($sql);
				}
			}
		}
		tlschema();
		$injected_modules[$force][$modulename]=true;
		return true;
	}else{
	 	output("`n`\$Module `^%s`\$ was not found in the modules directory.`n",$modulename);
		$injected_modules[$force][$modulename]=false;
		return false;
	}
}

/*
 * Returns the status of a module as a bitfield
 *
 * @param string $modulename The module name
 * @param string $version The version to check for (false for don't care)
 * @return int The status codes for the module
 */
function module_status($modulename, $version=false) {
	global $injected_modules;

	$modulename = modulename_sanitize($modulename);
	$modulefilename = "modules/$modulename.php";
	$status = MODULE_NO_INFO;
	if (file_exists($modulefilename)) {
		$sql = "SELECT active,filemoddate,infokeys,version FROM " . db_prefix("modules") . " WHERE modulename='$modulename'";
		$result = db_query_cached($sql, "inject-$modulename", 3600);
		if (db_num_rows($result) > 0) {
			// The module is installed
			$status = MODULE_INSTALLED;
			$row = db_fetch_assoc($result);
			if ($row['active']) {
				// Module is here and active
				$status |= MODULE_ACTIVE;
				// In this case, the module could have been force injected or
				// not.  We still want to mark it either way.
				if (array_key_exists($modulename, $injected_modules[0]) &&
						$injected_modules[0][$modulename])
					$status |= MODULE_INJECTED;
				if (array_key_exists($modulename, $injected_modules[1]) &&
						$injected_modules[1][$modulename])
					$status |= MODULE_INJECTED;
			} else {
				// Force-injected modules can be injected but not active.
				if (array_key_exists($modulename, $injected_modules[1]) &&
						$injected_modules[1][$modulename])
					$status |= MODULE_INJECTED;
			}
			// Check the version number
			if ($version===false) {
				$status |= MODULE_VERSION_OK;
			} else {
				if (module_compare_versions($row['version'], $version) < 0) {
					$status |= MODULE_VERSION_TOO_LOW;
				} else {
					$status |= MODULE_VERSION_OK;
				}
			}
		} else {
			// The module isn't installed
			$status = MODULE_NOT_INSTALLED;
		}
	} else {
		// The module file doesn't exist.
		$status = MODULE_FILE_NOT_PRESENT;
	}
	return $status;
}


/**
 * Determines if a module is activated
 *
 * @param string $modulename The module name
 * @return bool If the module is active or not
 */
function is_module_active($modulename){
	return (module_status($modulename) & MODULE_ACTIVE);
}

/**
 * Determines if a module is installed
 *
 * @param string $modulename The module name
 * @param string $version The version to check for
 * @return bool If the module is installed
 */
function is_module_installed($modulename,$version=false){
	// Status will say the version is okay if we don't care about the
	// version or if the version is actually correct
	return (module_status($modulename, $version) &
			(MODULE_INSTALLED|MODULE_VERSION_OK));
}

/**
 * Checks if the module requirements are satisfied.  Should a module require
 * other modules to be installed and active, then optionally makes them so
 *
 * @param array $reqs Requirements of a module from _getmoduleinfo()
 * @return bool If successful or not
 */
function module_check_requirements($reqs, $forceinject=false){
	// Since we can inject here, we need to save off the module we're on
	global $mostrecentmodule;

	$oldmodule = $mostrecentmodule;
	$result = true;

	if (!is_array($reqs)) return false;

	// Check the requirements.
	reset($reqs);
	foreach ($reqs as $key => $val) {
		$info = explode("|",$val);
		if (!is_module_installed($key,$info[0])) {
			return false;
		}
		// This is actually cheap since we cache the result
		$status = module_status($key);
		// If it's not injected and we should force it, do so.
		if (!($status & MODULE_INJECTED) && $forceinject) {
			$result = $result && injectmodule($key);
		}
	}

	$mostrecentmodule = $oldmodule;
	return $result;
}

/**
 * Blocks a module from being able to hook for the rest of the page hit.
 * Please note, any hooks already executed by the blocked module will
 * not be undone, so this function is pretty flaky all around.
 *
 * The only way to use this safely would be to block/unblock modules from
 * the everyhit hook and make sure to shortcircuit for any page other than
 * the one you care about.
 *
 * @param mixed $modulename The name of the module you wish to block or true if you want to block all modules.
 * @return void
 */
$block_all_modules = false;
$blocked_modules = array();
function blockmodule($modulename) {
	global $blocked_modules, $block_all_modules, $currenthook;

	if ($modulename === true) {
		$block_all_modules = true;
		return;
	}
	$blocked_modules[$modulename]=1;
}

/**
 * Unblocks a module from being able to hook for the rest of the page hit.
 * Please note, any hooks already blocked for the module being unblocked
 * have been lost, so this function is pretty flaky all around.
 *
 * The only way to use this safely would be to block/unblock modules from
 * the everyhit hook and make sure to shortcircuit for any page other than
 * the one you care about.
 *
 * @param mixed $modulename The name of the module you wish to unblock or true if you want to unblock all modules.
 * @return void
 */
$unblocked_modules = array();
function unblockmodule($modulename) {
	global $unblocked_modules, $block_all_modules;

	if ($modulename === true) {
		$block_all_modules = false;
		return;
	}
	$unblocked_modules[$modulename]=1;
}

$module_preload = array();
/**
 * Preloads data for multiple modules in one shot rather than
 * having to make SQL calls for each hook, when many of the hooks
 * are found on every page.
 * @param array $hooknames Names of hooks whose attached modules should be preloaded.
 * @return bool Success
 */
function mass_module_prepare($hooknames){
	if (!file_exists('dbconnect.php')) return;
	sort($hooknames);
	$Pmodules = db_prefix("modules");
	$Pmodule_hooks = db_prefix("module_hooks");
	$Pmodule_settings = db_prefix("module_settings");
	$Pmodule_userprefs = db_prefix("module_userprefs");

	global $modulehook_queries;
	global $module_preload;
	global $module_settings;
	global $module_prefs;
	global $session;

	//collect the modules who attach to these hooks.
	$sql =
		"SELECT
			$Pmodule_hooks.modulename,
			$Pmodule_hooks.location,
			$Pmodule_hooks.func,
			$Pmodule_hooks.whenactive
		FROM
			$Pmodule_hooks
		INNER JOIN
			$Pmodules
		ON	$Pmodules.modulename = $Pmodule_hooks.modulename
		WHERE
			active = 1
		AND	location IN ('".join("', '",$hooknames)."')
		ORDER BY
			$Pmodule_hooks.location,
			$Pmodule_hooks.priority,
			$Pmodule_hooks.modulename";
	$result = db_query_cached($sql,"moduleprepare-".md5(join($hooknames)));
	$modulenames = array();
	while ($row = db_fetch_assoc($result)){
		$modulenames[$row['modulename']] = $row['modulename'];
		if (!isset($module_preload[$row['location']])) {
			$module_preload[$row['location']] = array();
			$modulehook_queries[$row['location']] = array();
		}
		//a little black magic trickery: formatting entries in
		//$modulehook_queries the same way that db_query_cached
		//returns query results.
		array_push($modulehook_queries[$row['location']],$row);
		$module_preload[$row['location']][$row['modulename']] = $row['func'];
	}
	//SQL IN() syntax for the modules involved here.
	$modulelist = "'".join("', '",$modulenames)."'";

	//Load the settings for the modules on these hooks.
	$sql =
		"SELECT
			modulename,
			setting,
			value
		FROM
			$Pmodule_settings
		WHERE
			modulename IN ($modulelist)";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		$module_settings[$row['modulename']][$row['setting']] = $row['value'];
	}

	//Load the current user's prefs for the modules on these hooks.
	$sql =
		"SELECT
			modulename,
			setting,
			userid,
			value
		FROM
			$Pmodule_userprefs
		WHERE
			modulename IN ($modulelist)
		AND	userid = ".(int)$session['user']['acctid'];
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		$module_prefs[$row['userid']][$row['modulename']][$row['setting']] = $row['value'];
	}
	return true;
}


function module_compare_versions($a,$b){
	//this function returns -1 when $a < $b, 1 when $a > $b, and 0 when $a == $b
	//insert alternate version detection and comparison algorithms here.

	//default case, typecast as float
	$a = (float)$a;
	$b = (float)$b;
	return ($a < $b ? -1 : ($a > $b ? 1 : 0) );
}

function activate_module($module){
	if (!is_module_installed($module)){
		if (!install_module($module)){
			return false;
		}
	}
	$sql = "UPDATE " . db_prefix("modules") . " SET active=1 WHERE modulename='$module'";
	db_query($sql);
	invalidatedatacache("inject-$module");
	massinvalidate("moduleprepare");
	if (db_affected_rows() <= 0){
		return false;
	}else{
		return true;
	}
}

function deactivate_module($module){
	if (!is_module_installed($module)){
		if (!install_module($module)){
			return false;
		}else{
			//modules that weren't installed go to deactivated state by default in install_module
			return true;
		}
	}
	$sql = "UPDATE " . db_prefix("modules") . " SET active=0 WHERE modulename='$module'";
	db_query($sql);
	invalidatedatacache("inject-$module");
	massinvalidate("moduleprepare");
	if (db_affected_rows() <= 0){
		return false;
	}else{
		return true;
	}
}

function uninstall_module($module){
	if (injectmodule($module,true)) {
		$fname = $module."_uninstall";
		output("Running module uninstall script`n");
		tlschema("module-{$module}");
		$fname();
		tlschema();

		output("Deleting module entry`n");
		$sql = "DELETE FROM " . db_prefix("modules") .
			" WHERE modulename='$module'";
		db_query($sql);

		output("Deleting module hooks`n");
		module_wipehooks();

		output("Deleting module settings`n");
		$sql = "DELETE FROM " . db_prefix("module_settings") .
			" WHERE modulename='$module'";
		db_query($sql);
		invalidatedatacache("modulesettings-$module");

		output("Deleting module user prefs`n");
		$sql = "DELETE FROM " . db_prefix("module_userprefs") .
			" WHERE modulename='$module'";
		db_query($sql);

		output("Deleting module object prefs`n");
		$sql = "DELETE FROM " . db_prefix("module_objprefs") .
			" WHERE modulename='$module'";
		db_query($sql);
		invalidatedatacache("inject-$module");
		massinvalidate("moduleprepare");
		return true;
	} else {
		return false;
	}
}

function install_module($module, $force=true){
 	global $mostrecentmodule, $session;
	$name = $session['user']['name'];
	if (!$name) $name = '`@System`0';

	$fileModDate = date('Y-m-d H:i:s', filemtime(MODULE_DIR . "/$module.php"));
	require_once("lib/sanitize.php");
	if (modulename_sanitize($module)!=$module){
		output("Error, module file names can only contain alpha numeric characters and underscores before the trailing .php`n`nGood module names include 'testmodule.php', 'joesmodule2.php', while bad module names include, 'test.module.php' or 'joes module.php'`n");
		return false;
	}else{
		// If we are forcing an install, then whack the old version.
		if ($force) {
			$sql = "DELETE FROM " . db_prefix("modules") . " WHERE modulename='$module'";
			db_query($sql);
		}
		// We want to do the inject so that it auto-upgrades any installed
		// version correctly.
		if (injectmodule($module,true)) {
			// If we're not forcing and this is already installed, we are done
			if (!$force && is_module_installed($module))
				return true;
			$info = get_module_info($module);
			//check installation requirements
			if (!module_check_requirements($info['requires'])){
				output("`\$Module could not installed -- it did not meet its prerequisites.`n");
				return false;
			}else{
					$keys = "|".join("|", array_keys($info))."|";
				$sql = "INSERT INTO " . db_prefix("modules") . " (modulename,formalname,moduleauthor,active,filename,filemoddate,installdate,installedby,category,infokeys,version,download,description) VALUES ('$mostrecentmodule','".addslashes($info['name'])."','".addslashes($info['author'])."',0,'{$mostrecentmodule}.php','$fileModDate','".date("Y-m-d H:i:s")."','".addslashes($name)."','".addslashes($info['category'])."','$keys','".addslashes($info['version'])."','".addslashes($info['download'])."', '".addslashes($info['description'])."')";
				db_query($sql);
				$fname = $mostrecentmodule."_install";
				if (isset($info['settings']) && count($info['settings']) > 0) {
					foreach($info['settings'] as $key=>$val){
						if (is_array($val)) {
							$x = explode("|", $val[0]);
						} else {
							$x = explode("|",$val);
						}
						if (isset($x[1])){
                            $x[1] = trim($x[1]);
							set_module_setting($key,$x[1]);
							debug("Setting $key to default {$x[1]}");
						}
					}
				}
				if ($fname() === false) {
					return false;
				}
				output("`^Module installed.  It is not yet active.`n");
				invalidatedatacache("inject-$mostrecentmodule");
				massinvalidate("moduleprepare");
				return true;
			}
		} else {
			output("`\$Module could not be injected.");
			output("Module not installed.");
			output("This is probably due to the module file having a parse error or not existing in the filesystem.`n");
			return false;
		}
	}

}

/**
  * Evaluates a PHP Expression
  *
  * @param string $condition The PHP condition to evaluate
  * @return bool The result of the evaluated expression
  */
function module_condition($condition) {
	global $session;
	$result = eval($condition);
	return (bool)$result;
}


function get_module_info($shortName)
{
	global $mostrecentmodule;
	
	$moduleinfo = [];
	
	// Save off the mostrecent module.
	$mod = $mostrecentmodule;
	
	if(!injectmodule($shortName, true)) {
		return [];
	}
	$fname = $shortName."_getmoduleinfo";
	if (function_exists($fname)){
		tlschema("module-$shortName");
		$moduleinfo = $fname();
		tlschema();
		// Don't pick up this text unless we need it.
		if (!isset($moduleinfo['name']) ||
		!isset($moduleinfo['category']) ||
		!isset($moduleinfo['author']) ||
		!isset($moduleinfo['version'])) {
			$ns = translate_inline("Not specified","common");
		}
		if (!isset($moduleinfo['name']))
		$moduleinfo['name']="$ns ($shortName)";
		if (!isset($moduleinfo['category']))
		$moduleinfo['category']="$ns ($shortName)";
		if (!isset($moduleinfo['author']))
		$moduleinfo['author']="$ns ($shortName)";
		if (!isset($moduleinfo['version']))
		$moduleinfo['version']="0.0";
		if (!isset($moduleinfo['download']))
		$moduleinfo['download'] = "";
		if (!isset($moduleinfo['description']))
		$moduleinfo['description'] = "";
	}
	if (!is_array($moduleinfo) || count($moduleinfo)<2){
		$mf = translate_inline("Missing function","common");
		$moduleinfo = array(
			"name"=>"$mf ({$shortName}_getmoduleinfo)",
			"version"=>"0.0",
			"author"=>"$mf ({$shortName}_getmoduleinfo)",
			"category"=>"$mf ({$shortName}_getmoduleinfo)",
			"download"=>"",
		);
	}
	$mostrecentmodule = $mod;
	if (!isset($moduleinfo['requires']))
	$moduleinfo['requires'] = array();
	return $moduleinfo;
}


function moduleStatusAll() {
	$modules = ['active' => [], 'inactive' => []];
	$sql = "SELECT category, modulename, active FROM modules
					ORDER BY category, active, modulename";
	//db_query_cached($sql, 'installed-modules');
	$result = db_query($sql);
	foreach ($result as $row) {
		$category = $row['category'];
		$module = $row['modulename'];
		$modules[$category][] = $module;
		if ($row['active'] == 1) {
			$modules['active'][] = $module;
		}
		else {
			$modules['inactive'][] = $module;
		}
	}
	debug($modules, true);
}

function modulesByCategory()
{
	$modules = [];
	$modulesPrefix = db_prefix('modules');
	$sql = "SELECT * FROM $modulesPrefix
					ORDER BY category, active, modulename";
	$result = db_query($sql);
	foreach ($result as $row) {
		$category = $row['category'];
		$module = $row['modulename'];
		if ($row['active'] == 1) {
			$modules[$category]['active'][$module] = $row;
			continue;
		}
		$modules[$category]['inactive'][$module] = $row;
	}
	return $modules;
}

/**
 * Retrieves the statuses of all modules, both installed and uninstalled.
 *
 * This function queries the database to retrieve information about installed modules,
 * their categories, and activation status. It also scans the 'modules' folder to identify
 * uninstalled modules. The function returns an array containing information about installed
 * and uninstalled modules, including their categories, activation statuses, and counts.
 *
 * @throws \Error If the 'modules' folder cannot be opened.
 * @return array An associative array containing the following keys:
 *   - 'installedcategories': An array of categories with installed modules.
 *   - 'installedcount': The total count of installed modules.
 *   - 'installedmodules': An associative array with module names as keys and their activation statuses as values.
 *   - 'uninstalledcount': The total count of uninstalled modules.
 *   - 'uninstalledmodules': An array of uninstalled module names.
 * @since 1.0.0
 */
function getAllModuleStatuses()
{
	$categories = [];
	$modules = ['_all' => []];
	$modulesPrefix = db_prefix('modules');
	$uninstalled = [];
	$count = 0;
	
	$sql = "SELECT modulename as name, category, active FROM $modulesPrefix
					ORDER BY category, active, modulename";
	$result = @db_query($sql);
	foreach ($result as $row) {
		$modules['_all'][] = $row['name'];
		$modules[$row['category']][$row['name']] = $row['active'];
	}
	
	$handle = opendir('modules');
	if (!$handle) {
		throw new Error('Could not open the modules folder!');
		die();
	}
	while (false !== ($file = readdir($handle))){
		$fileName = pathinfo($file, PATHINFO_FILENAME);
		if ($file[0] == '.') continue;
		if (!preg_match("/p$/", $file)) continue;
		if (in_array($fileName, $modules['_all'])) continue;
		$count++;
		$uninstalled[] = $fileName;
	}
	closedir($handle);
	
	return [
		'installedcategories' => $categories,
		'installedcount' => count($modules),
		'installedmodules' => $modules,
		'uninstalledcount' => count($uninstalled),
		'uninstalledmodules' => $uninstalled,
	];
}

function get_module_install_status() {
	return getAllModuleStatuses();
}
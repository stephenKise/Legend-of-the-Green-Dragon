<?php

/**
 * An event that should be triggered
 *
 * @param string $hookname The name of the event to raise
 * @param array $args Arguments that should be passed to the event handler
 * @param bool $allowinactive Allow inactive modules
 * @param bool $only Only this module?
 * @return array The args modified by the event handlers
 */
$currenthook = "";
function modulehook(
	$hookname,
	$args = false,
	$allowinactive = false,
	$only = false
) {
	if (!file_exists('dbconnect.php')) return $args ?: [];
	global $navsection, $mostrecentmodule;
	global $blocked_modules, $block_all_modules, $unblocked_modules;
	global $output, $session, $modulehook_queries;
	global $currenthook;
	$lasthook = $currenthook;
	$currenthook = $hookname;
	static $hookcomment = array();
	if ($args===false) $args = array();
	$active = "";
	if (!$allowinactive) $active = " ". db_prefix("modules") .".active=1 AND";
	if (!is_array($args)){
		$where = $mostrecentmodule;
		if (!$where) {
			global $SCRIPT_NAME;
			$where = $SCRIPT_NAME;
		}
		debug("Args parameter to modulehook $hookname from $where is not an array.");
	}
	if (isset($session['user']['superuser']) && $session['user']['superuser'] & SU_DEBUG_OUTPUT && !isset($hookcomment[$hookname])){
		rawoutput("<!--Module Hook: $hookname; allow inactive: ".($allowinactive?"true":"false")."; only this module: ".($only!==false?$only:"any module"));
		if (!is_array($args)) {
			$arg = $args . " (NOT AN ARRAY!)";
			rawoutput("  arg: $arg");
		} else {
			reset($args);
			foreach($args as $key => $val) {
				$arg = $key." = ";
				if (is_array($val)){
					$arg.="array(".count($val).")";
				}elseif (is_object($val)){
					$arg.="object(".get_class($val).")";
				}else{
					$arg.=htmlentities(substr($val,0,25), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
				}
				rawoutput("  arg: $arg");
			}
		}
		rawoutput("  -->");
		$hookcomment[$hookname]=true;
	}
	if (isset($modulehook_queries[$hookname]) //This data was pre fetched in mass_module_prepare
		&& $allowinactive == false //We only ever prefetch for active modules, if we're doing inactive, do the regular query.
		){
		$result = $modulehook_queries[$hookname];
	}else{
		$sql =
			"SELECT
				" . db_prefix("module_hooks") . ".modulename,
				" . db_prefix("module_hooks") . ".location,
				" . db_prefix("module_hooks") . ".func,
				" . db_prefix("module_hooks") . ".whenactive
			FROM
				" . db_prefix("module_hooks") . "
			INNER JOIN
				" . db_prefix("modules") . "
			ON	" . db_prefix("modules") . ".modulename = " . db_prefix("module_hooks") . ".modulename
			WHERE
				$active
				" . db_prefix("module_hooks") . ".location='$hookname'
			ORDER BY
				" . db_prefix("module_hooks") . ".priority,
				" . db_prefix("module_hooks") . ".modulename";
		// $result = db_query_cached($sql,"hook-".$hookname);
		$result = db_query($sql);
	}
	// $args is an array passed by value and we take the output and pass it
	// back through
	// Try at least and fix up a bogus arg so it doesn't cause additional
	// problems later.
	if (!is_array($args)) {
		$args = array('bogus_args'=>$args);
	}

	// Save off the mostrecent module since having that change can change
	// behaviour especially if a module calls modulehooks itself or calls
	// library functions which cause them to be called.
	$mod = $mostrecentmodule;
	while ($row = db_fetch_assoc($result)){
		// If we are only running hooks for a specific module, skip all
		// others.
		if ($only !== false && $row['modulename']!=$only) continue;
		// Skip any module invocations which should be blocked.

		if (!array_key_exists($row['modulename'],$blocked_modules)){
			$blocked_modules[$row['modulename']] = false;
		}
		if (!array_key_exists($row['modulename'],$unblocked_modules)){
			$unblocked_modules[$row['modulename']] = false;
		}
		if (($block_all_modules || $blocked_modules[$row['modulename']]) &&
				!$unblocked_modules[$row['modulename']]) {
			continue;
		}

		if (injectmodule($row['modulename'], $allowinactive)) {
			$oldnavsection = $navsection;
			tlschema("module-{$row['modulename']}");
			// Pass the args into the function and reassign them to the
			// result of the function.
			// Note: each module gets the previous module's modified return
			// value if more than one hook here.
			// Order of operations could become an issue, modules are called
			// in alphabetical order by their module name (not display name).

			// Test the condition code
			if (!array_key_exists('whenactive',$row)) $row['whenactive'] = '';
			$cond = trim($row['whenactive']);
			if ($cond == "" || module_condition($cond) == true) {
				// call the module's hook code
				$outputbeforehook = $output;
				$output="";
				/*******************************************************/
				$starttime = getmicrotime();
				/*******************************************************/
				if (function_exists($row['func'])) {
					$res = $row['func']($hookname, $args);
				} else {
					trigger_error("Unknown function {$row['func']} for hoookname $hookname in module {$row['module']}.", E_USER_WARNING);
				}
				/*******************************************************/
				$endtime = getmicrotime();
				if (($endtime - $starttime >= 1.00 && ($session['user']['superuser'] & SU_DEBUG_OUTPUT))){
					debug("Slow Hook (".round($endtime-$starttime,2)."s): $hookname - {$row['modulename']}`n");
				}
				/*******************************************************/
				$outputafterhook = $output;
				$output=$outputbeforehook;
				// test to see if we had any output and if the module allows
				// us to collapse it
				$testout = trim(sanitize_html($outputafterhook));
				if (!is_array($res)) {
					trigger_error("<b>{$row['func']}</b> did not return an array in the module <b>{$row['modulename']}</b> for hook <b>$hookname</b>.",E_USER_WARNING);
					$res = $args;
				}
				if ($testout >"" &&
						$hookname!="collapse{" &&
						$hookname!="}collapse" &&
						$hookname!="collapse-nav{" &&
						$hookname!="}collapse-nav" &&
						!array_key_exists('nocollapse',$res)) {
					//restore the original output's reference
					modulehook("collapse{",
						array("name"=>'a-'.$row['modulename']));
					$output .= $outputafterhook;
					modulehook("}collapse");
				} else {
					$output .= $outputafterhook;
				}
				// Clear the collapse flag
				unset($res['nocollapse']);
				//handle return arguments.
				if (is_array($res)) $args = $res;
			}

			//revert the translation namespace
			tlschema();
			//revert nav section after we're done here.
			$navsection = $oldnavsection;
		}
	}

	$mostrecentmodule=$mod;
	$currenthook = $lasthook;

	// And hand them back so they can be used.
	return $args;
}

function module_wipehooks() {
	global $mostrecentmodule;
	//lock the module hooks table.
	$sql = "LOCK TABLES ".db_prefix("module_hooks")." WRITE";
	db_query($sql);

	//invalidate data caches for module hooks associated with this module.
	$sql = "SELECT location FROM ".db_prefix("module_hooks")." WHERE modulename='$mostrecentmodule'";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		invalidatedatacache("hook-".$row['location']);
	}
	invalidatedatacache("moduleprepare");

	debug("Removing all hooks for $mostrecentmodule");
	$sql = "DELETE FROM " . db_prefix("module_hooks"). " WHERE modulename='$mostrecentmodule'";
	db_query($sql);
	//unlock the module hooks table.
	$sql = "UNLOCK TABLES";
	db_query($sql);

	$sql = "DELETE FROM " . db_prefix("module_event_hooks") . " WHERE modulename='$mostrecentmodule'";
	db_query($sql);

}

function module_drophook($hookname,$functioncall=false){
	global $mostrecentmodule;
	if ($functioncall===false)
		$functioncall=$mostrecentmodule."_dohook";
	$sql = "DELETE FROM " . db_prefix("module_hooks") . " WHERE modulename='$mostrecentmodule' AND location='".addslashes($hookname)."' AND func='".addslashes($functioncall)."'";
	db_query($sql);
	invalidatedatacache("hook-".$hookname);
	invalidatedatacache("moduleprepare");
}

/**
  * Called by modules to register themselves for a game module hook point, with default priority.
  * Modules with identical priorities will execute alphabetically.  Modules can only have one hook on a given hook name,
  * even if they call this function multiple times, unless they specify different values for the functioncall argument.
  *
  * @param string $hookname The hook to receive a notification for
  * @param string $functioncall The function that should be called, if not specified, use {modulename}_dohook() as the function
  * @param string $whenactive An expression that should be evaluated before triggering the event, if not specified, none.
   */
function module_addhook($hookname,$functioncall=false,$whenactive=false){
	module_addhook_priority($hookname,50,$functioncall,$whenactive);
}

/**
  * Called by modules to register themselves for a game module hook point, with a given priority -- lower numbers execute first.
  * Modules with identical priorities will execute alphabetically.  Modules can only have one hook on a given hook name,
  * even if they call this function multiple times, unless they specify different values for the functioncall argument.
  *
  * @param string $hookname The hook to receive a notification for
  * @param integer $priority The priority for this hooking -- lower numbers execute first.  < 50 means earlier-than-normal execution, > 50 means later than normal execution.  Priority only affects execution order compared to other events registered on the same hook, all events on a given hook will execute before the game resumes execution.
  * @param string $functioncall The function that should be called, if not specified, use {modulename}_dohook() as the function
  * @param string $whenactive An expression that should be evaluated before triggering the event, if not specified, none.
   */
function module_addhook_priority($hookname,$priority=50,$functioncall=false,$whenactive=false){
	global $mostrecentmodule;
	module_drophook($hookname,$functioncall);

	if ($functioncall===false) $functioncall=$mostrecentmodule."_dohook";
	if ($whenactive === false) $whenactive = '';

	debug("Adding a hook at $hookname for $mostrecentmodule to $functioncall which is active on condition '$whenactive'");
	//we want to do a replace in case there's any garbage left in this table which might block new clean data from going in.
	//normally that won't be the case, and so this doesn't have any performance implications.
	$sql = "REPLACE INTO " . db_prefix("module_hooks") . " (modulename,location,func,whenactive,priority) VALUES ('$mostrecentmodule','".addslashes($hookname)."','".addslashes($functioncall)."','".addslashes($whenactive)."','".addslashes($priority)."')";
	db_query($sql);
	invalidatedatacache("hook-".$hookname);
	invalidatedatacache("moduleprepare");
}

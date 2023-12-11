<?php
require_once("lib/installer/installer_sqlstatements.php");
require_once("lib/installer/installer_functions.php");
require_once("lib/installer/installer_default_settings.php");
output("`@`c`bBuilding the Tables`b`c");
output("`2I'm now going to build the tables.");
output("If this is an upgrade, your current tables will be brought in line with the current version.");
output("If it's an install, the necessary tables will be placed in your database.`n");
output("`n`@Table Synchronization Logs:`n");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
$descriptors = descriptors($DB_PREFIX);
require_once("lib/tabledescriptor.php");
reset($descriptors);
foreach ($descriptors as $tableName => $descriptor) {
	output("`3Synchronizing table `#$tableName`3..`n");
	synctable($tableName, $descriptor, true);
	if ($session['dbinfo']['upgrade'] == false)
		db_query("TRUNCATE TABLE $tableName;");
}
rawoutput("</div>");
output("`n`2The tables now have new fields and columns added, I'm going to begin importing data now.`n");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
$doSQL = false;
reset($sql_upgrade_statements);
foreach ($sql_upgrade_statements as $key => $val) {
	if ($doSQL) {
		output("`3Version `#%s`3: %s SQL statements...`n", $key, count($val));
		if (count($val) > 0) {
			output("`^Doing: `6");
			reset($val);
			$count = 0;
			foreach ($val as $id => $sql) {
				$onlyupgrade = 0;
				if (substr($sql, 0, 2) == '1|') {
					$sql = substr($sql, 2);
					$onlyupgrade = 1;
				}
				// Skip any statements that should only be run during
				// upgrades from previous versions.
				if (!$session['dbinfo']['upgrade'] && $onlyupgrade) {
					continue;
				}
				$count++;
				if ($count%10==0 && $count!=count($val))
				output_notl("`6$count...");
				if (!db_query($sql)) {
					output("`n`\$Error: `^'%s'`7 executing `#'%s'`7.`n",
					db_error(), $sql);
				}
			}
			output("$count.`n");
		}
	}
	if ($key == $session['fromversion'] ||
	$session['dbinfo']['upgrade'] == false) $doSQL=true;
}
rawoutput("</div>");
output("Now I'm going to insert default settings that you don't have.");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
foreach ($defaultSettings as $setting => $value) {
	if(!isset($settings[$setting]) && getsetting($setting, $value) == $value) {
		output_notl("Setting $setting to default value of $value`n");
	}
}
rawoutput("</div>");
	/*
output("`n`2Now I'll install the recommended modules.");
output("Please note that these modules will be installed, but not activated.");
output("Once installation is complete, you should use the Module Manager found in the superuser grotto to activate those modules you wish to use.");
reset($recommended_modules);
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
while (list($key,$modulename)=each($recommended_modules)){
output("`3Installing `#$modulename`\$`n");
install_module($modulename, false);
}
rawoutput("</div>");
*/
if (!getSession('skipmodules')) {
  output("`n`2Now I'll install and configure your modules.");
  rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
  foreach($session['moduleoperations'] as $modName => $val) {
	  $ops = explode(',', $val);
	  reset($ops);
		foreach ($ops as $trash => $op) {
		  switch ($op) {
			  case "uninstall":
				  output("`3Uninstalling `#$modName`3: ");
				  if (uninstall_module($modName))
					  output("`@OK!`0`n");
					else
					  output("`\$Failed!`0`n");
				  break;
			  case "install":
				  output("`3Installing `#$modName`3: ");
				  if (install_module($modName))
					  output("`@OK!`0`n");
				  else
					  output("`\$Failed!`0`n");
				  install_module($modName);
				  break;
			  case "activate":
				  output("`3Activating `#$modName`3: ");
				  if (activate_module($modName))
					  output("`@OK!`0`n");
					else
					  output("`\$Failed!`0`n");
				  break;
			  case "deactivate":
				  output("`3Deactivating `#$modName`3: ");
				  if (deactivate_module($modName))
					  output("`@OK!`0`n");
				  else
					  output("`\$Failed!`0`n");
				  break;
			  case "donothing":
				  break;
		  }
	  }
	  $session['moduleoperations'][$modName] = "donothing";
  }
  rawoutput("</div>");
}
output("`n`2Finally, I'll clean up old data.`n");
rawoutput("<div style='width: 100%; height: 150px; max-height: 150px; overflow: auto;'>");
reset($descriptors);
foreach ($descriptors as $tableName => $descriptor) {
	output("`3Cleaning up `#$tableName`3...`n");
	synctable($tableName, $descriptor);
}
rawoutput("</div>");
output("`n`n`^You're ready for the next step.");
?>

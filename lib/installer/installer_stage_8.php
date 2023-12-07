<?php
require_once('lib/installer/installer_functions.php');
if (array_key_exists('modules', $_POST)) {
	$session['moduleoperations'] = $_POST['modules'];
	$session['stagecompleted'] = $stage;
	header('Location: installer.php?stage=' . ($stage + 1));
	exit();
}
elseif (array_key_exists('moduleoperations', $session)
	&& is_array($session['moduleoperations']))
	$session['stagecompleted'] = $stage;
else
	$session['stagecompleted'] = $stage - 1;
output("`@`c`bManage Modules`b`c");
output("Legend of the Green Dragon supports an extensive module system.");
output("Modules are small self-contained files that perform a specific function or event within the game.");
output("For the most part, modules are independant of each other, meaning that one module can be installed, uninstalled, activated, and deactivated without negative impact on the rest of the game.");
output("Not all modules are ideal for all sites, for example, there's a module called 'Multiple Cities,' which is intended only for large sites with many users online at the same time.");
output("`n`n`^If you are not familiar with Legend of the Green Dragon, and how the game is played, it is probably wisest to choose the default set of modules to be installed.");
output("`n`n`@There is an extensive community of users who write modules for LoGD at <a href='http://dragonprime.net/'>http://dragonprime.net/</a>.",true);
$ramLimit = ini_get('memory_limit');
if (return_bytes($ramLimit) < 12582912
	&& $ramLimit != -1
	&& !$session['overridememorylimit']
	&& !$session['dbinfo']['upgrade']) {
	// enter this ONLY if it's not an upgrade and if the limit is really too low
	output("`n`n`\$Warning: Your PHP memory limit is set to a very low level.");
	output("Smaller servers should not be affected by this during normal gameplay but for this installation step you should assign at least 12 Megabytes of RAM for your PHP process.");
	output("For now we will skip this step, but before installing any module, make sure to increase you memory limit.");
	output("`nYou can proceed at your own risk. Be aware that a blank screen indicates you *must* increase the memory limit.");
	output("`n`nTo override click again on \"Set Up Modules\".");
	$session['stagecompleted'] = 8;
	$session['overridememorylimit'] = true;
	$session['skipmodules'] = true;
}
else {
	if (isset($session['overridememorylimit'])
		&& $session['overridememorylimit']) {
		output("`4`n`nYou have been warned... you are now working on your own risk.`n`n");
		$session['skipmodules'] = false;
	}
	$submit = translate_inline('Save Module Settings');
	$install = translate_inline('Select Recommended Modules');
	$reset = translate_inline('Reset Values');
	$allMods = [];
	$result = false;
	if ($session['dbinfo']['upgrade']) {
		$sql = "SELECT * FROM ".db_prefix("modules")." ORDER BY category,active DESC,formalname";
		$result = @db_query($sql);
	}
	if ($result !== false) {
		while ($row = db_fetch_assoc($result)) {
			if (!array_key_exists($row['category'], $allMods)) {
				$allMods[$row['category']] = [];
			}
			$row['installed'] = true;
			$allMods[$row['category']][$row['modulename']] = $row;
		}
	}
	$uninstalled = [];
	$invalidmodule = [
		'version'=>'',
		'author'=>'',
		'category'=>'Invalid Modules',
		'download'=>'',
		'description'=>'',
		'invalid'=>true,
	];
	foreach ($uninstalled as $key => $modName) {
		//test if the file is a valid module or a lib file/whatever that got in, maybe even malcode that does not have module form
		$moduleNameLower = strtolower($modName);
		$file = strtolower(file_get_contents("modules/$modName.php"));
		if (strpos($file, "{$moduleNameLower}_getmoduleinfo") === false ||
			strpos($file, "{$moduleNameLower}_install") === false ||
			strpos($file, "{$moduleNameLower}_uninstall") === false) {
			//here the files has neither do_hook nor getinfo, which means it won't execute as a module here --> block it + notify the admin who is the manage modules section
			$invalidMod = appoencode(translate_inline('`$- Invalid Module!'));
			$modInfo = array_merge(
				$invalidmodule,
				['name'=>"{$modName}.php {$invalidMod}"]
			);
		}
		else
			$modInfo = get_module_info($modName);
		//end of testing
		$row = [
			'installed' => false,
			'active' => false,
			'category' => $modInfo['category'],
			'modulename' => $modName,
			'formalname' => $modInfo['name'],
			'description' => $modInfo['description'],
			'moduleauthor' => $modInfo['author'],
			'invalid' => (isset($modInfo['invalid']) ? $modInfo['invalid'] : false)
		];
		if (!array_key_exists($row['category'], $allMods)) {
			$allMods[$row['category']] = [];
		}
		$allMods[$row['category']][$row['modulename']] = $row;
	}
	if (count($allMods) == 0) {
		$session['skipmodules'] = true;
		$session['stagecompleted'] = $stage;
		header('Location: installer.php?stage=' . ($stage + 1));
		exit();
	}
	output_notl("`0");
	rawoutput("<form action='installer.php?stage=$stage' method='POST'>");
	rawoutput("<input type='submit' value='$submit' class='button'>");
	rawoutput("<input type='button' onClick='chooseRecommendedModules();' class='button' value='$install' class='button'>");
	rawoutput("<input type='reset' value='$reset' class='button'><br>");
	rawoutput("<table cellpadding='1' cellspacing='1'>");
	ksort($allMods);
	reset($allMods);
	$x = 0;
	foreach ($allMods as $categoryName => $categoryItems) {
		rawoutput("<tr class='trhead'><td colspan='6'>".tl($categoryName)."</td></tr>");
		rawoutput("<tr class='trhead'><td>".tl("Uninstalled")."</td><td>".tl("Installed")."</td><td>".tl("Activated")."</td><td>".tl("Recommended")."</td><td>".tl("Module Name")."</td><td>".tl("Author")."</td></tr>");
		reset($categoryItems);
		foreach ($categoryItems as $modName => $modInfo) {
			$x++;
			$trColor = ($x % 2 ? 'trlight' : 'trdark'); 
			//if we specified things in a previous hit on this page, let's update the modules array here as we go along.
			$modInfo['realactive'] = $modInfo['active'];
			$modInfo['realinstalled'] = $modInfo['installed'];
			if (array_key_exists('moduleoperations', $session)
				&& is_array($session['moduleoperations'])
				&& array_key_exists($modName, $session['moduleoperations'])) {
				$ops = explode(',' ,$session['moduleoperations'][$modName]);
				reset($ops);
				foreach ($ops as $trash => $op) {
					switch ($op) {
						case 'uninstall':
							$modInfo['installed'] = false;
							$modInfo['active'] = false;
							break;
						case 'install':
					  case 'deactivate':
							$modInfo['installed'] = true;
							$modInfo['active'] = false;
							break;
						case 'activate':
							$modInfo['installed'] = true;
							$modInfo['active'] = true;
							break;
						case 'donothing':
							break;
					}
				}
			}
			rawoutput("<tr class='$trColor'>");
			if ($modInfo['realactive']) {
				$uninstallop = "uninstall";
				$installop = "deactivate";
				$activateop = "donothing";
			}
			elseif ($modInfo['realinstalled']) {
				$uninstallop = "uninstall";
				$installop = "donothing";
				$activateop = "activate";
			}
			else {
				$uninstallop = "donothing";
				$installop = "install";
				$activateop = "install,activate";
			}
			$uninstallcheck = false;
			$installcheck = false;
			$activatecheck = false;
			if ($modInfo['active']) 
				$activatecheck = true;
			elseif ($modInfo['installed']) 
				$installcheck = true;
			else
				$uninstallcheck = true;
			
			if (isset($modInfo['invalid']) && $modInfo['invalid'] == true) {
				rawoutput("<td><input type='radio' name='modules[$modName]' id='uninstall-$modName' value='$uninstallop' checked disabled></td>");
				rawoutput("<td><input type='radio' name='modules[$modName]' id='install-$modName' value='$installop' disabled></td>");
				rawoutput("<td><input type='radio' name='modules[$modName]' id='activate-$modName' value='$activateop' disabled></td>");
			}
			else {
				rawoutput("<td><input type='radio' name='modules[$modName]' id='uninstall-$modName' value='$uninstallop'".($uninstallcheck?" checked":"")."></td>");
				rawoutput("<td><input type='radio' name='modules[$modName]' id='install-$modName' value='$installop'".($installcheck?" checked":"")."></td>");
				rawoutput("<td><input type='radio' name='modules[$modName]' id='activate-$modName' value='$activateop'".($activatecheck?" checked":"")."></td>");
			}
			output_notl("<td>".(in_array($modName, $defaultMods)?tl("`^Yes`0"):tl("`\$No`0"))."</td>",true);
			require_once('lib/sanitize.php');
			rawoutput(
				"<td><span title=\"" .
				(isset($modInfo['description']) && $modInfo['description'] ?
					$modInfo['description'] : sanitize($modInfo['formalname'])) .
				"\">"
			);
			output_notl("`@");
			if (isset($modInfo['invalid']) && $modInfo['invalid'] == true)
				rawoutput($modInfo['formalname']);
			 else 
				output($modInfo['formalname']);
			output_notl(" [`%$modName`@]`0");
			rawoutput("</span></td><td>");
			output_notl("`#{$modInfo['moduleauthor']}`0", true);
			rawoutput("</td>");
			rawoutput("</tr>");
		}
	}
	rawoutput("</table>");
	rawoutput("<br><input type='submit' value='$submit' class='button'>");
	rawoutput("<input type='button' onClick='chooseRecommendedModules();' class='button' value='$install' class='button'>");
	rawoutput("<input type='reset' value='$reset' class='button'>");
	rawoutput("</form>");
	rawoutput("<script language='JavaScript'>
		function chooseRecommendedModules(){
			var thisItem;
			var selectedCount = 0;"
	);
	reset($defaultMods);
	foreach ($defaultMods as $key => $val) {
		rawoutput("thisItem = document.getElementById('activate-$val'); ");
		rawoutput("if (!thisItem.checked) { selectedCount++; thisItem.checked=true; }\n");
	}
	rawoutput("alert('I selected '+selectedCount+' modules that I recommend, but which were not already selected.');
}");
	if (!$session['dbinfo']['upgrade'])
		rawoutput("chooseRecommendedModules();");
	rawoutput("</script>");
}
?>